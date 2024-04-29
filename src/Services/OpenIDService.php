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
use Facile\OpenIDClient\Service\AuthorizationService;
use Facile\OpenIDClient\Service\Builder\IntrospectionServiceBuilder;
use Facile\OpenIDClient\Service\Builder\RevocationServiceBuilder;
use Facile\OpenIDClient\Service\Builder\UserInfoServiceBuilder;
use Odan\Session\SessionInterface;
use OWCSignicatOpenID\Interfaces\Services\OpenIDServiceInterface;
use OWCSignicatOpenID\Interfaces\Services\SettingsServiceInterface;
use Psr\Http\Message\ServerRequestInterface;

use RuntimeException;

class OpenIDService extends Service implements OpenIDServiceInterface
{
    protected ClientInterface $oidc_client;
    protected AuthorizationService $authorization_service;
    protected SessionInterface $session;
    protected SettingsServiceInterface $settings;

    public function __construct(
        ClientInterface $oidc_client,
        AuthorizationService $authorization_service,
        SessionInterface $session,
        SettingsServiceInterface $settings
    ) {
        $this->oidc_client = $oidc_client;
        $this->authorization_service = $authorization_service;
        $this->session = $session;
        $this->settings = $settings;
    }

    public function register(): void
    {
        add_action('owc_signicat_openid_user_info', [$this, 'get_user_info']);
    }

    public function authenticate(array $idpScopes = [], string $redirectUrl): void
    {
        $stateID = bin2hex(random_bytes(12));
        if (! $this->session->isStarted()) {
            $this->session->start();
        }
        $this->session->set('state', [
            'stateID' => $stateID,
            'idpScopes' => $idpScopes,
            'redirectUrl' => $redirectUrl,
            'refererUrl' => wp_get_referer(),
        ]);
        $this->session->save();

        $idpScopes = array_map(
            fn (string $idpScope): string => sprintf('idp_scoping:%s', $idpScope),
            $idpScopes
        );
        $scopes = ['openid', ...$idpScopes];

        if ($this->settings->get_setting('enable_simulator')) {
            $scopes[] = 'idp_scoping:simulator';
        }

        $scopes = array_intersect(
            $scopes,
            $this->oidc_client->getIssuer()->getMetadata()->getScopesSupported()
        );

        $params = [
            'scope' => implode(' ', $scopes),
            'state' => $stateID,
        ];
        $redirect_authorization_uri = $this->authorization_service->getAuthorizationUri($this->oidc_client, $params);

        header('Location: ' . $redirect_authorization_uri);
        exit();
    }

    /**
     * Handle the OpenID redirect.
     *
     * @since 0.0.1
     *
     * @throws RuntimeException Unauthorized;
     */
    public function handle_redirect(ServerRequestInterface $server_request): void
    {
        try {
            $callback_params = $this->authorization_service->getCallbackParams($server_request, $this->oidc_client);
        } catch (OAuth2Exception $e) {
            //throw $th;
        }

        $this->session->start();
        $state = $this->session->get('state');
        $this->session->remove('state');
        if ($callback_params['state'] !== $state['stateID']) {
            throw new RuntimeException('Unauthorized');
        }

        $token_set = $this->authorization_service->callback($this->oidc_client, $callback_params);
        if (null === $token_set->getIdToken()) {
            throw new RuntimeException('Unauthorized');
        }

        //TODO: check issuer/scope

        $this->session->set('token_set', $token_set);
        $this->session->save();

        wp_safe_redirect($state['redirectUrl']);
        exit;
    }

    /**
     * Refresh the tokens with the refresh token.
     *
     * @since 0.0.1
     *
     * @throws RuntimeException Unauthorized;
     */
    public function refresh(): void
    {
        if (! $this->session->isStarted()) {
            $this->session->start();
        }

        if (! $this->session->has('token_set') || null === $this->session->get('token_set')->getRefreshToken()) {
            wp_send_json_success(
                [
                    'message' => 'Missing refresh token',
                ],
                \WP_Http::INTERNAL_SERVER_ERROR
            );
        }

        $service = (new IntrospectionServiceBuilder())->build();
        // $params = $service->introspect($this->oidc_client, $this->session->get('token_set')->getAccessToken());

        // if ($params['active']) {
        //     wp_send_json_success(
        //         [
        //             'message' => 'Session still active`',
        //         ],
        //         \WP_Http::OK
        //     );
        // }

        $tokenSet = $this->authorization_service->refresh($this->oidc_client, $this->session->get('token_set')->getRefreshToken());

        $this->session->set('token_set', $tokenSet);
        //$this->session->save();
        wp_send_json_success(
            [
                'message' => 'Session refreshed',
            ],
            \WP_Http::OK
        );
    }

    /**
     * Get user info.
     *
     * @since 0.0.1
     *
     * @return array
     */
    public function get_user_info(): array
    {
        if (! $this->session->isStarted()) {
            $this->session->start();
        }

        $introspect = $this->introspect();
        if (empty($introspect['active'])) {
            return [];
        }

        $user_info_service = (new UserInfoServiceBuilder())->build();

        return $user_info_service->getUserInfo($this->oidc_client, $this->session->get('token_set'));
    }

    /**
     * Logout and end the session.
     *
     * @since 0.0.1
     *
     * @throws RuntimeException You are not logged in
     */
    public function logout(): void
    {
        if (! $this->session->isStarted()) {
            $this->session->start();
        }
        if (! $this->session->has('token_set')) {
            throw new RuntimeException('You are not logged in');
        }
        $revocation_service = (new RevocationServiceBuilder())->build();
        $revocation_service->revoke($this->oidc_client, $this->session->get('token_set')->getAccessToken());
        $this->session->destroy();
    }

    public function introspect(): array
    {
        if (! $this->session->has('token_set')) {
            return [];
        }
        $service = (new IntrospectionServiceBuilder())->build();

        return $service->introspect($this->oidc_client, $this->session->get('token_set')->getAccessToken());
    }
}
