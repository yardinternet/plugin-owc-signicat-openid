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

use Aura\Session\Session;
use Aura\Session\Segment;
use Cedaro\WP\Plugin\AbstractHookProvider;
use Facile\OpenIDClient\Client\ClientInterface;
use Facile\OpenIDClient\Middleware\UserInfoMiddleware;
use Facile\OpenIDClient\Service\AuthorizationService;
use Facile\OpenIDClient\Service\Builder\AuthorizationServiceBuilder;
use Facile\OpenIDClient\Service\Builder\UserInfoServiceBuilder;
use GuzzleHttp\Psr7\ServerRequest;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

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
	 * @var Session
	 */
	protected $session;


	private Segment $segment;

	/**
	 * Constructor.
	 *
	 * @since 0.0.1
	 *
	 * @param ClientInterface      $oidc_client         OIDC Client.
	 * @param AuthorizationService $oidc_service        OIDC Service.
	 * @param LoggerInterface      $logger              Logger.
	 * @param Session              $session             Session.
	 */
	public function __construct(
		ClientInterface $oidc_client,
		AuthorizationService $oidc_service,
		LoggerInterface $logger,
		Session $session
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
					$this->authenticate();
				}

				if ($wp->request === $path_redirect) {
					$server_request = ServerRequest::fromGlobals();
					$this->get_user_info( $server_request );
				}

				if ($wp->request === $path_logout) {
					$this->logout();
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
	 * Get user info.
	 *
	 * @since 0.0.1
	 * @return void;
	 */
	protected function get_user_info( ServerRequestInterface $server_request ): void
	{
		$callback_params = $this->oidc_service->getCallbackParams( $server_request, $this->oidc_client );
		$token_set       = $this->oidc_service->callback( $this->oidc_client, $callback_params );

		$id_token      = $token_set->getIdToken();
		$access_token  = $token_set->getAccessToken();
		$refresh_token = $token_set->getRefreshToken();

		$user_info_service = ( new UserInfoServiceBuilder() )->build();
		$user_info         = $user_info_service->getUserInfo( $this->oidc_client, $token_set );

		if ($id_token) {
			$claims = $token_set->claims();
		} else {
			throw new \RuntimeException( 'Unauthorized' );
		}

		var_dump( $claims );
		die;
	}

	/**
	 * Logout and end the session.
	 *
	 * @since 0.0.1
	 */
	protected function logout( $request, $response )
	{
		var_dump( $request, $response );
		die;
		$revocation_service = ( new RevocationServiceBuilder() )->build();
		$callback_params    = $this->oidc_service->getCallbackParams( $request, $this->oidc_client );
		$token_set          = $this->oidc_service->callback( $this->oidc_client, $callback_params );
		$params             = $revocation_service->revoke( $this->oidc_client, $token_set->getRefreshToken() );

		return $response;
	}
}
