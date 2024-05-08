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

declare (strict_types = 1);

namespace OWCSignicatOpenID\Services;

use Facile\OpenIDClient\Client\ClientInterface;
use Facile\OpenIDClient\Exception\OAuth2Exception;

use function Facile\OpenIDClient\parse_callback_params;
use Facile\OpenIDClient\Service\AuthorizationService;
use Facile\OpenIDClient\Service\Builder\IntrospectionServiceBuilder;
use Facile\OpenIDClient\Service\Builder\RevocationServiceBuilder;
use Facile\OpenIDClient\Service\Builder\UserInfoServiceBuilder;
use Odan\Session\SessionInterface;
use OWCSignicatOpenID\IdentityProvider;
use OWCSignicatOpenID\Interfaces\Services\IdentityProviderServiceInterface;
use OWCSignicatOpenID\Interfaces\Services\OpenIDServiceInterface;
use OWCSignicatOpenID\Interfaces\Services\SettingsServiceInterface;
use Psr\Http\Message\ServerRequestInterface;

use RuntimeException;
use WP_Error;

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
    }

    public function register(): void
    {
        add_filter('owc_signicat_openid_user_info', [$this, 'retrieveUserInfo'], 10, 2);
    }

    public function retrieveUserInfo(array $userInfo, string $idpSlug): array
    {
        $idp = $this->identityProviderService->getIdentityProvider($idpSlug);
        if (null === $idp) {
            return $userInfo;
        }

        return $this->getUserInfo($idp);
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
            $this->session->getFlash()->add($exception->getError(), $exception->getDescription());
            wp_safe_redirect(get_site_url());
            exit;
        }

        $this->maybeStartSession();

        $state = $this->popState($stateId);

        $token_set = $this->authorizationService->callback($this->client, $callback_params);
        if (null === $token_set->getIdToken()) {
            throw new RuntimeException('Unauthorized');
        }
        $identityProvider = $state['identityProvider'];

        $this->session->set($identityProvider->getSlug(), $token_set);
        $this->session->save();

        wp_safe_redirect($state['redirectUrl']);
        exit;
    }

    public function refresh(IdentityProvider $identityProvider)
    {
        $this->maybeStartSession();

        if (! $this->session->has($identityProvider->getSlug()) || null === $this->session->get($identityProvider->getSlug())->getRefreshToken()) {
            return new WP_Error('missing_refresh_token', 'Missing refresh token');
        }
        if ($this->hasActiveSession($identityProvider)) {
            return true;
        }

        $tokenSet = $this->authorizationService->refresh($this->client, $this->session->get($identityProvider->getSlug())->getRefreshToken());

        $this->session->set($identityProvider->getSlug(), $tokenSet);

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

        return $userInfoService->getUserInfo($this->client, $this->session->get($identityProvider->getSlug()));
    }

    public function revoke(IdentityProvider $identityProvider): void
    {
        $this->maybeStartSession();
        if (! $this->session->has($identityProvider->getSlug())) {
            throw new RuntimeException('You are not logged in');
        }
        $revocationService = (new RevocationServiceBuilder())->build();
        $revocationService->revoke($this->client, $this->session->get($identityProvider->getSlug())->getAccessToken());
        $this->session->remove($identityProvider->getSlug());
    }

    public function introspect(IdentityProvider $identityProvider): array
    {
        if (! $this->session->has($identityProvider->getSlug())) {
            return [];
        }
        $introspectionService = (new IntrospectionServiceBuilder())->build();

        return $introspectionService->introspect($this->client, $this->session->get($identityProvider->getSlug())->getAccessToken());
    }

    public function hasActiveSession(IdentityProvider $identityProvider): bool
    {
        $this->maybeStartSession();
        $introspect = $this->introspect($identityProvider);

        return ! empty($introspect['active']);
    }

    private function maybeStartSession()
    {
        if (! $this->session->isStarted()) {
            $this->session->start();
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

}
