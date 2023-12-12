<?php
/**
 * Modal provider.
 *
 * @package OWC_Signicat_OpenID
 * @author  Yard | Digital Agency
 * @since   0.0.1
 */

declare ( strict_types = 1 );

namespace OWCSignicatOpenID;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' )) {
	exit;
}

use Cedaro\WP\Plugin\AbstractHookProvider;
use Error;
use Odan\Session\PhpSession;

/**
 * Modal class.
 *
 * @since 0.0.1
 */
class Modal extends AbstractHookProvider
{
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
	 * @param PhpSession $session             Session.
	 */
	public function __construct(
		PhpSession $session
	) {
		$this->session = $session;
	}

	/**
	 * Register hooks.
	 *
	 * @since 0.0.1
	 *
	 * @return void
	 */
	public function register_hooks(): void
	{
		add_action( 'wp_enqueue_scripts', array( $this, 'register_modal_scripts' ) );
	}

	/**
	 * Register modal scripts.
	 *
	 * @since 0.0.1
	 *
	 * @throws Error Run npm watch or build
	 * @return void
	 */
	public function register_modal_scripts(): void
	{
		$script_asset_path = $this->plugin->get_path( 'dist/modal.asset.php' );

		if ( ! file_exists( $script_asset_path )) {
			throw new Error(
				'You need to run `npm run watch` or `npm run build` to be able to use this plugin first.'
			);
		}

		$script_asset = require $script_asset_path;

		wp_register_script(
			'sopenid-modal-script',
			$this->plugin->get_url( 'dist/modal.js' ),
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		// Get the expiration from the session.
		$exp = $this->session->get( 'exp' ) ?? 0;

		// Get the refresh and logout uri's from the settings.
		$path_refresh = sanitize_text_field( get_option( 'owc_signicat_openid_path_refresh_settings' ) );
		$path_logout  = sanitize_text_field( get_option( 'owc_signicat_openid_path_logout_settings' ) );

		wp_localize_script(
			'sopenid-modal-script',
			'sopenidSettings',
			array(
				'exp'         => $exp,
				'refresh_uri' => trailingslashit( get_site_url() ) . $path_refresh,
				'logout_uri'  => trailingslashit( get_site_url() ) . $path_logout,
			)
		);

		wp_enqueue_script( 'sopenid-modal-script' );
	}
}
