<?php
/**
 * OpenID provider.
 *
 * @package OWC_Signicat_OpenID
 * @author  Yard | Digital Agency
 * @since   0.0.1
 */

declare ( strict_types = 1 );

namespace OWCSignicatOpenID\Provider;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' )) {
	exit;
}

use Cedaro\WP\Plugin\AbstractHookProvider;
use Facile\OpenIDClient\Client\ClientInterface;
use Facile\OpenIDClient\Middleware\UserInfoMiddleware;
use Facile\OpenIDClient\Service\AuthorizationService;
use Facile\OpenIDClient\Service\Builder\AuthorizationServiceBuilder;
use Facile\OpenIDClient\Service\Builder\RevocationServiceBuilder;
use Facile\OpenIDClient\Service\Builder\UserInfoServiceBuilder;
use GuzzleHttp\Psr7\ServerRequest;
use Odan\Session\PhpSession;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * OpenID class.
 *
 * @since 0.0.1
 */
class OpenID extends AbstractHookProvider
{
	/**
	 * Logger.
	 *
	 * @var LoggerInterface
	 */
	protected $logger;

	/**
	 * OIDC Client.
	 *
	 * @var ClientInterface
	 */
	protected $oidc_client;

	/**
	 * OIDC Service.
	 *
	 * @var AuthorizationService;
	 */
	protected $oidc_service;

	/**
	 * Session.
	 *
	 * @var PhpSession
	 */
	protected $session;

	/**
	 * Constructor.
	 *
	 * @since 0.0.1
	 *
	 * @param ClientInterface      $oidc_client         OIDC Client.
	 * @param AuthorizationService $oidc_service        OIDC Service.
	 * @param LoggerInterface      $logger              Logger.
	 * @param PhpSession           $session             Session.
	 */
	public function __construct(
		ClientInterface $oidc_client,
		AuthorizationService $oidc_service,
		LoggerInterface $logger,
		PhpSession $session
	) {
		$this->oidc_client  = $oidc_client;
		$this->oidc_service = $oidc_service;
		$this->logger       = $logger;
		$this->session      = $session;
	}

	/**
	 * Register hooks.
	 *
	 * @since 0.0.1
	 */
	public function register_hooks(): void
	{
		$this->register_routes();
	}

	/**
	 * Register the SSO routes.
	 *
	 * @since 0.0.1
	 */
	protected function register_routes(): void
	{
		$path_login    = sanitize_text_field( get_option( 'owc_signicat_openid_path_login_settings' ) );
		$path_logout   = sanitize_text_field( get_option( 'owc_signicat_openid_path_logout_settings' ) );
		$path_redirect = sanitize_text_field( get_option( 'owc_signicat_openid_path_redirect_settings' ) );

		add_action(
			'parse_request',
			function ( $wp ) use ( $path_login, $path_logout, $path_redirect ) {
				if ($wp->request === $path_login) {
					if ( ! $this->session->has( 'access_token' ) ) {
						$this->authenticate();
					} else {
						wp_safe_redirect( home_url() );
						exit;
					}
				}

				if ($wp->request === $path_redirect) {
					$server_request = ServerRequest::fromGlobals();
					$this->handle_redirect( $server_request );
				}

				if ($wp->request === $path_logout) {
					$this->logout( $server_request );
				}
			}
		);
	}

	/**
	 * Authenticate.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	protected function authenticate(): void
	{
		$auth_service               = ( new AuthorizationServiceBuilder() )->build();
		$redirect_authorization_uri = $auth_service->getAuthorizationUri(
			$this->oidc_client,
		);
		header( 'Location: ' . $redirect_authorization_uri );
		exit();
	}

	/**
	 * Handle the OpenID redirect.
	 *
	 * @since 0.0.1
	 * @throws RuntimeException Unauthorized;
	 *
	 * @return void;
	 */
	protected function handle_redirect( ServerRequestInterface $server_request )
	{
		$callback_params = $this->oidc_service->getCallbackParams( $server_request, $this->oidc_client );
		$token_set       = $this->oidc_service->callback( $this->oidc_client, $callback_params );

		$id_token      = $token_set->getIdToken();
		$access_token  = $token_set->getAccessToken();
		$refresh_token = $token_set->getRefreshToken();

		if ($id_token) {
			$claims = $token_set->claims();
		} else {
			throw new RuntimeException( 'Unauthorized' );
		}

		$this->session->set( 'access_token', $access_token ?? '' );
		$this->session->set( 'refresh_token', $refresh_token ?? '' );
		$this->session->set( 'exp', $claims['exp'] ?? '' );
		$this->session->save();
	}

	/**
	 * Get user info.
	 *
	 * @return void
	 */
	public function get_user_info(): void
	{
		$userInfoService = $container->get( UserInfoService::class );
		$middleware      = new UserInfoMiddleware( $userInfoService );
	}

	/**
	 * Logout and end the session.
	 *
	 * @since 0.0.1
	 * @throws RuntimeException You were not logged in
	 */
	protected function logout()
	{
		$revocation_service = ( new RevocationServiceBuilder() )->build();

		if ( ! $this->session->has( 'access_token' )) {
			throw new RuntimeException( 'You were not logged in' );
		} else {
			$access_token = $this->session->get( 'access_token' );
			$revocation_service->revoke( $this->oidc_client, $access_token );
			$this->session->destroy();
		}
	}
}
