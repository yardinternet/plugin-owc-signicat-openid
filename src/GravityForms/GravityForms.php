<?php
/**
 * GravityForms.
 *
 * @package OWC_Signicat_OpenID
 * @author  Yard | Digital Agency
 * @since   0.0.1
 */

declare ( strict_types = 1 );

namespace OWCSignicatOpenID\GravityForms;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' )) {
	exit;
}

/**
 * GravityForms class.
 *
 * @since 0.0.1
 */
class GravityForms
{
	protected Segment $session;

	/**
	 * GravityForms constructor.
	 */
	public function __construct(Segment $session )
	{
		$this->session = $session;
	}

	/**
	 * Register custom fields.
	 */
	public function registerFields(): void
	{
		GF_Fields::register( new eHerkenningField() );
	}

	/**
	 * Add countdown if the session is still active.
	 */
	public function addCountdown($form_tag, $form ): string
	{
		$expiry     = ( new VerifyToken() )->verifyToken();
		$pathLogout = get_option( 'signicat_openid_path_logout_settings' );

		$logout = '';

		if ( ! empty( $expiry ) && $expiry > time()) {
			$logout = view(
				'partials/countdown.php',
				array(
					'currentTime' => date( 'Y-m-d H:i:s' ),
					'expiryTime'  => date( 'Y-m-d H:i:s', $expiry ),
					'logoutLink'  => get_site_url( null, $pathLogout ),
				)
			);
		}

		$form_tag = str_replace( "<form ", "" . $logout . "<form ", $form_tag );

		return $form_tag;
	}
}
