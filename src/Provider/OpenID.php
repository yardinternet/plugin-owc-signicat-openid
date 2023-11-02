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
use Jumbojett\OpenIDConnectClient;
use Jumbojett\OpenIDConnectClientException;
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
	 * Session.
	 *
	 * @var Session
	 */
	protected $session;

	private OpenIDConnectClient $oidc;

	private Segment $segment;

	/**
	 * Constructor.
	 *
	 * @since 0.0.1
	 *
	 * @param LoggerInterface $logger               Logger.
	 * @param Session         $session             Session.
	 */
	public function __construct(
		LoggerInterface $logger,
		Session $session
	) {
		$this->logger  = $logger;
		$this->session = $session;
	}

	/**
	 * Register hooks.
	 *
	 * @since 0.0.1
	 */
	public function register_hooks(): void
	{
		$this->segment = $this->session->getSegment( 'sopenid' );

		$this->oidc = new OpenIDConnectClient(
			get_option( 'owc_signicat_openid_broker_url_settings' ),
			get_option( 'owc_signicat_openid_client_id_settings' ),
			get_option( 'owc_signicat_openid_client_secret_settings' )
		);

		$this->oidc->addScope( 'openid' ); // idp_scoping:eherkenning
		$this->oidc->setRedirectURL( '' );

		if (defined( 'WP_DEBUG' ) && WP_DEBUG === true) {
			$this->oidc->setHttpUpgradeInsecureRequests( false );
			$this->oidc->setVerifyHost( false );
			$this->oidc->setVerifyPeer( false );
		}

		$this->register_routes( $this->oidc );
	}

	/**
	 * Register the SSO routes.
	 *
	 * @since 0.0.1
	 */
	protected function register_routes( $oidc ): void
	{
		$path_login  = get_option( 'owc_signicat_openid_path_login_settings' );
		$path_logout = get_option( 'owc_signicat_openid_path_logout_settings' );

		add_action(
			'parse_request',
			function ($wp ) use ($oidc, $path_login, $path_logout ) {
				if ($wp->request === $path_login) {
					if (empty( $this->segment->get( 'sopenid.user-info' ) )) {
						$this->authenticate( $oidc );
					} else {
						wp_safe_redirect( home_url() );
						exit;
					}
				}

				if ($wp->request === $path_logout) {
					if ( ! empty( $this->segment->get( 'sopenid.user-info' ) )) {
						$this->logout();
					} else {
						$this->authenticate( $oidc );
					}
				}
			}
		);
	}

	/**
	 * Authenticate and set session.
	 *
	 * @since 0.0.1
	 */
	protected function authenticate( $oidc ): void
	{
		try {
			$oidc->authenticate();
		} catch (OpenIDConnectClientException $e) {
			$this->logger->info(
				'Could not connect to Signicat Identity Broker',
				array(
					'exception' => $e,
				)
			);
		}

		$user_info     = $oidc->request_user_info();
		$access_token  = $oidc->get_access_token();
		$refresh_token = $oidc->get_refresh_token();

		$this->segment->set( 'sopenid.user-info', $user_info );
		$this->segment->set( 'sopenid.access-token', $access_token );
		$this->segment->set( 'sopenid.refresh-token', $refresh_token );
	}

	/**
	 * Verify token validity.
	 *
	 * @since 0.0.1
	 *
	 * @param string $token
	 * @return void
	 */
	public function verify_token( $token )
	{
		$access_token = $this->segment->get( 'sopenid.access-token' );

		$data = $this->oidc->introspectToken( $access_token );

		if ( ! $data->active) {
			// the token is no longer usable
		}
	}

	/**
	 * Logout and end the session.
	 *
	 * @since 0.0.1
	 */
	protected function logout(): void
	{
		$this->segment->clear();
	}
}
