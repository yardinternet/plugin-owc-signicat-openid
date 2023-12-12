<?php
/**
 * Plugin settings screen.
 *
 * @package OWC_Signicat_OpenID
 * @author  Yard | Digital Agency
 * @since   0.0.1
 */

declare ( strict_types = 1 );

namespace OWCSignicatOpenID\Screen;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' )) {
	exit;
}

use Cedaro\WP\Plugin\AbstractHookProvider;
use Error;

/**
 * Class to activate the plugin.
 *
 * @since 0.0.1
 */
class Settings extends AbstractHookProvider
{
	public function register_hooks()
	{
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
	}

	/**
	 * Add a settings page.
	 *
	 * @since 0.0.1
	 */
	public function add_settings_page(): void
	{
		add_options_page(
			'owc-signicat-openid',
			esc_html__( 'Signicat OpenID', 'owc-signicat-openid' ),
			'manage_options',
			'owc-signicat-openid',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register settings fields.
	 *
	 * @since 0.0.1
	 */
	public function register_settings(): void
	{
		register_setting( 'owc_signicat_openid_settings_group', 'owc_signicat_openid_configuration_url_settings' );
		register_setting( 'owc_signicat_openid_settings_group', 'owc_signicat_openid_client_id_settings' );
		register_setting( 'owc_signicat_openid_settings_group', 'owc_signicat_openid_client_secret_settings' );
		register_setting( 'owc_signicat_openid_settings_group', 'owc_signicat_openid_path_login_settings' );
		register_setting( 'owc_signicat_openid_settings_group', 'owc_signicat_openid_path_logout_settings' );
		register_setting( 'owc_signicat_openid_settings_group', 'owc_signicat_openid_path_redirect_settings' );
		register_setting( 'owc_signicat_openid_settings_group', 'owc_signicat_openid_path_refresh_settings' );
	}

	/**
	 * Render the settings page.
	 *
	 * @since 0.0.1
	 */
	public function render_settings_page(): void
	{
		include $this->plugin->get_path( 'resources/views/settings.php' );
	}
}
