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

/**
 * Exit when accessed directly.
 */
if (! defined('ABSPATH')) {
    exit;
}

use Facile\OpenIDClient\Client\ClientInterface;
use Facile\OpenIDClient\Service\AuthorizationService;
use Facile\OpenIDClient\Service\Builder\IntrospectionServiceBuilder;
use Facile\OpenIDClient\Service\Builder\RevocationServiceBuilder;
use Facile\OpenIDClient\Service\Builder\UserInfoServiceBuilder;
use Facile\OpenIDClient\Token\IdTokenVerifierBuilder;
use Odan\Session\SessionInterface;
use OWCSignicatOpenID\Interfaces\Services\OpenIDServiceInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

use RuntimeException;

/**
 * Register OpenID service.
 *
 * @since 0.0.1
 */
class OpenIDService extends Service implements OpenIDServiceInterface
{

    protected LoggerInterface $logger;

    protected ClientInterface $oidc_client;

    protected AuthorizationService $authorization_service;

    protected SessionInterface $session;

    public function __construct(
        ClientInterface $oidc_client,
        AuthorizationService $authorization_service,
        LoggerInterface $logger,
        SessionInterface $session
    ) {
        $this->oidc_client = $oidc_client;
        $this->authorization_service = $authorization_service;
        $this->logger = $logger;
        $this->session = $session;
    }

    /**
     * Register hooks.
     *
     * @since 0.0.1
     */
    public function register(): void
    {
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
        $callback_params = $this->authorization_service->getCallbackParams($server_request, $this->oidc_client);

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
        $service = (new IntrospectionServiceBuilder())->build();
        $params = $service->introspect($this->oidc_client, $this->session->get('token_set')->getAccessToken());

        if ($params['active']) {
            wp_send_json_success(
                [
                    'message' => 'Session still active`',
                ],
                \WP_Http::OK
            );
        }

        if (! $this->session->has('token_set') || null === $this->session->get('token_set')->getRefreshToken()) {
            wp_send_json_success(
                [
                    'message' => 'Missing refresh token',
                ],
                \WP_Http::INTERNAL_SERVER_ERROR
            );
        }

        // Onderstaande workaround kan weg als https://github.com/facile-it/php-openid-client/issues/38 opgelost is
        // $token_set = $this->authorization_service->refresh($this->oidc_client, $current_refresh_token);
        // Begin workaround
        $tokenSet = $this->authorization_service->grant(
            $this->oidc_client,
            [
                'grant_type' => 'refresh_token',
                'refresh_token' => $this->session->get('token_set')->getRefreshToken(),
            ]
        );

        if (null === $tokenSet->getIdToken()) {
            wp_send_json_error(
                [
                    'message' => 'Failed to renew session',
                ],
                \WP_Http::INTERNAL_SERVER_ERROR
            );
        }

        $claims = (new IdTokenVerifierBuilder())->build($this->oidc_client)->verify($tokenSet->getIdToken());
        $tokenSet = $tokenSet->withClaims($claims);
        //Einde workaround

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
        if (! $this->session->has('token_set')) {
            return [];
        }

        $introspect = $this->introspect();
        if (! $introspect['active']) {
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
