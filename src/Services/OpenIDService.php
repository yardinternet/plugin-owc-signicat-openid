<?php
/**
 * OpenID provider.
 *
 * @package OWC_Signicat_OpenID
 *
 * @author  Yard | Digital Agency
 *
 * @since   0.0.1
 */

declare (strict_types=1);

namespace OWCSignicatOpenID\Services;

use Facile\OpenIDClient\Client\ClientInterface;
use Facile\OpenIDClient\Exception\OAuth2Exception;
use Facile\OpenIDClient\Service\AuthorizationService;
use Facile\OpenIDClient\Service\Builder\IntrospectionServiceBuilder;
use Facile\OpenIDClient\Service\Builder\RevocationServiceBuilder;
use Facile\OpenIDClient\Service\Builder\UserInfoServiceBuilder;
use Facile\OpenIDClient\Token\TokenSet;
use Odan\Session\SessionInterface;
use OWC\IdpUserData\UserDataInterface;
use OWCSignicatOpenID\IdentityProvider;
use OWCSignicatOpenID\Interfaces\Services\IdentityProviderServiceInterface;
use OWCSignicatOpenID\Interfaces\Services\OpenIDServiceInterface;
use OWCSignicatOpenID\Interfaces\Services\SettingsServiceInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use WP_Error;

use function Facile\OpenIDClient\parse_callback_params;

class OpenIDService extends Service implements OpenIDServiceInterface
{
    protected ClientInterface $client;
    protected AuthorizationService $authorizationService;
    protected SessionInterface $session;
    protected SettingsServiceInterface $settings;
    protected IdentityProviderServiceInterface $identityProviderService;

    public function __construct(
        ClientInterface $client,
        AuthorizationService $authorizationService,
        SessionInterface $session,
        SettingsServiceInterface $settings,
        IdentityProviderServiceInterface $identityProviderService
    ) {
        $this->client = $client;
        $this->authorizationService = $authorizationService;
        $this->session = $session;
        $this->settings = $settings;
        $this->identityProviderService = $identityProviderService;

        foreach ($this->identityProviderService->getEnabledIdentityProviders() as $identityProvider) {
            add_filter('owc_' . $identityProvider->getSlug() . '_is_logged_in', fn (bool $isLoggedIn): bool => $this->isUserLoggedIn($isLoggedIn, $identityProvider->getSlug()));
            add_filter('owc_' . $identityProvider->getSlug() . '_userdata', fn (?UserDataInterface $userData): ?UserDataInterface => $this->retrieveUserInfo($userData, $identityProvider->getSlug()));
        }
    }

    public function retrieveUserInfo(?UserDataInterface $userInfo, string $idpSlug): ?UserDataInterface
    {
        $idp = $this->identityProviderService->getIdentityProvider($idpSlug);
        if (null === $idp) {
            return $userInfo;
        }

        if ($this->hasActiveSession($idp)) {
            $userDataClass = $idp->getUserDataClass();
            $userInfo = new $userDataClass($this->getUserInfo($idp), $idp->getMapping());
        }
        return $userInfo;
    }

    public function isUserLoggedIn(bool $isUserLoggedIn, string $idpSlug): bool
    {
        $idp = $this->identityProviderService->getIdentityProvider($idpSlug);
        if (null === $idp) {
            return $isUserLoggedIn;
        }

        if ($this->hasActiveSession($idp)) {
            $isUserLoggedIn = true;
        }

        return $isUserLoggedIn;
    }

    public function getLoginUrl(IdentityProvider $identityProvider, string $redirectUrl = null, string $refererUrl = null): string
    {
        $args = array_filter(
            [
                'idp' => $identityProvider->getSlug(),
                'redirectUrl' => $redirectUrl,
                'refererUrl' => $refererUrl,
            ]
        );

        return add_query_arg(
            $args,
            get_site_url(null, $this->settings->getSetting('path_login'))
        );
    }

    public function getLogoutUrl(IdentityProvider $identityProvider = null, string $redirectUrl = null, string $refererUrl = null): string
    {
        $args = array_filter(
            [
                'idp' => $identityProvider ? $identityProvider->getSlug() : null,
                'redirectUrl' => $redirectUrl,
                'refererUrl' => $refererUrl,
            ]
        );

        return add_query_arg(
            $args,
            get_site_url(null, $this->settings->getSetting('path_logout'))
        );
    }

    public function authenticate(IdentityProvider $identityProvider, string $redirectUrl, string $refererUrl = null): void
    {
        $stateID = $this->saveState(
            [
                'identityProvider' => $identityProvider,
                'redirectUrl' => $redirectUrl,
                'refererUrl' => $refererUrl ?? wp_get_referer(),
            ]
        );

        if ($this->settings->getSetting('enable_simulator')) {
            $idpScope = 'idp_scoping:simulator';
        } else {
            $idpScope = $identityProvider->getScope();
        }

        $params = [
            'scope' => implode(' ', ['openid', $idpScope]),
            'state' => $stateID,
        ];
        $redirect_authorization_uri = $this->authorizationService->getAuthorizationUri($this->client, $params);

        header('Location: ' . $redirect_authorization_uri);
        exit();
    }


    public function redirectToLogout(IdentityProvider $identityProvider, string $redirectUrl, string $refererUrl = null)
    {

    }

    // public function logout(IdentityProvider $identityProvider, string $redirectUrl): void
    // {
    //     $stateID = bin2hex(random_bytes(12));

    //     $logoutUrl = $this->client->getIssuer()->getMetadata()->get('');
    // }

    public function handleCallback(ServerRequestInterface $server_request): void
    {
        $rawCallbackParams = parse_callback_params($server_request);
        $stateId = sanitize_key($rawCallbackParams['state']) ?? null;

        try {
            $callback_params = $this->authorizationService->getCallbackParams($server_request, $this->client);
        } catch (OAuth2Exception $exception) {
            $this->maybeStartSession();
            $this->session->getFlash()->add($exception->getError(), $exception->getDescription());
            $this->session->save();
            wp_safe_redirect(get_site_url());
            exit;
        }

        $this->maybeStartSession();

        $state = $this->popState($stateId);

        $tokenSet = $this->authorizationService->callback($this->client, $callback_params);
        if (null === $tokenSet->getIdToken()) {
            throw new RuntimeException('Unauthorized');
        }
        $identityProvider = $state['identityProvider'];

        $this->setIdpTokenSet($identityProvider, $tokenSet);
        $this->session->save();

        wp_safe_redirect($state['redirectUrl']);
        exit;
    }

    /**
     * @return WP_Error|bool
     */
    public function refresh(IdentityProvider $identityProvider)
    {
        $this->maybeStartSession();

        if (! $this->hasIdpTokenSet($identityProvider) || null === $this->getIdpTokenSet($identityProvider)->getRefreshToken()) {
            return new WP_Error('missing_refresh_token', 'Missing refresh token');
        }
        if ($this->hasActiveSession($identityProvider)) {
            return true;
        }

        $tokenSet = $this->authorizationService->refresh($this->client, $this->getIdpTokenSet($identityProvider)->getRefreshToken());
        $this->setIdpTokenSet($identityProvider, $tokenSet);

        //$this->session->save();
        return true;
    }

    public function getUserInfo(IdentityProvider $identityProvider): array
    {
        $this->maybeStartSession();
        if (! $this->hasActiveSession($identityProvider)) {
            return [];
        }
        $userInfoService = (new UserInfoServiceBuilder())->build();

        return $userInfoService->getUserInfo($this->client, $this->getIdpTokenSet(($identityProvider)));
    }

    public function revoke(IdentityProvider $identityProvider): void
    {
        $this->maybeStartSession();
        if (! $this->hasIdpTokenSet($identityProvider)) {
            throw new RuntimeException('You are not logged in');
        }
        $revocationService = (new RevocationServiceBuilder())->build();
        $revocationService->revoke($this->client, $this->getIdpTokenSet($identityProvider)->getAccessToken());
        $this->removeIdpTokenSet($identityProvider);
    }

    public function introspect(IdentityProvider $identityProvider): array
    {
        if (! $this->hasIdpTokenSet($identityProvider)) {
            return [];
        }
        $introspectionService = (new IntrospectionServiceBuilder())->build();

        return $introspectionService->introspect($this->client, $this->getIdpTokenSet($identityProvider)->getAccessToken());
    }

    public function hasActiveSession(IdentityProvider $identityProvider): bool
    {
        $this->maybeStartSession();
        $introspect = $this->introspect($identityProvider);

        return ! empty($introspect['active']);
    }

    public function flashErrors(): array
    {
        $errors = $this->session->getFlash()->all();
        $this->session->save();

        return $errors;
    }

    public function setFlashError(string $error, string $description): void
    {
        $this->session->getFlash()->add($error, $description);
    }

    private function maybeStartSession()
    {
        if (! $this->session->isStarted()) {
            $this->session->start();
        } elseif (empty($this->session->all()) && ! empty($_SESSION)) {
            // Replace the current session with an existing session already started by another plugin.
            $this->session->replace($_SESSION);
        }
    }

    private function saveState(array $state): string
    {
        $stateID = bin2hex(random_bytes(12));
        $this->maybeStartSession();
        $this->session->set($stateID, $state);
        $this->session->save();

        return $stateID;
    }

    private function popState(string $stateID): array
    {
        if (! $this->session->has($stateID)) {
            throw new RuntimeException('State not found');
        }

        $state = $this->session->get($stateID);
        $this->session->remove($stateID);

        return $state;
    }

    private function hasIdpTokenSet(IdentityProvider $identityProvider): bool
    {
        return $this->session->has('owc_openid_' . $identityProvider->getSlug()) && is_a($this->session->get('owc_openid_' . $identityProvider->getSlug()), TokenSet::class);
    }

    private function getIdpTokenSet(IdentityProvider $identityProvider): ?TokenSet
    {
        return $this->session->get('owc_openid_' . $identityProvider->getSlug());
    }

    private function setIdpTokenSet(IdentityProvider $identityProvider, TokenSet $tokenSet)
    {
        $this->session->set('owc_openid_' . $identityProvider->getSlug(), $tokenSet);
    }

    private function removeIdpTokenSet(IdentityProvider $identityProvider)
    {
        $this->session->remove('owc_openid_' . $identityProvider->getSlug());
    }
}
