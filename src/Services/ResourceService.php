<?php
/**
 * Register resource service.
 *
 * @package OWC_Signicat_OpenID
 * @author  Yard | Digital Agency
 * @since   0.0.1
 */

namespace WPSitesMonitor\Services;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' )) {
	exit;
}

use WPSitesMonitor\Interfaces\Services\ResourceServiceInterface;

/**
 * Register resource service.
 *
 * @since 1.2.0
 */
class ResourceService extends Service implements ResourceServiceInterface
{
	/**
	 * @inheritDoc
	 */
	public function register()
	{
		add_action( 'admin_enqueue_scripts', array( $this, 'register_admin_scripts' ) );
		add_action( 'init', array( $this, 'register_block_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_front_scripts' ) );
	}

	/**
	 * Register the admin scripts.
	 *
	 * @since 0.0.1
	 *
	 * @param string $hook_suffix
	 * @throws \Error Run npm build;
	 * @return void;
	 */
	public function register_admin_scripts( $hook_suffix )
	{
		// only load the scripts on the plugin settings page
		if ('settings_page_wp-sites-monitor' !== $hook_suffix) {
			return;
		}

		$script_asset_path = WP_SITES_MONITOR_DIR_PATH . 'dist/admin.asset.php';

		if ( ! file_exists( $script_asset_path )) {
			throw new \Error(
				'You need to run `npm run watch` or `npm run build` to be able to use this plugin first.'
			);
		}

		$script_asset = require $script_asset_path;

		wp_enqueue_style(
			wp_sites_monitor_prefix( 'admin-css' ),
			wp_sites_monitor_asset_url( 'admin.css' ),
			array( 'wp-components' ),
			$script_asset['version']
		);

		wp_register_script(
			wp_sites_monitor_prefix( 'admin-js' ),
			wp_sites_monitor_asset_url( 'admin.js' ),
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		wp_localize_script(
			wp_sites_monitor_prefix( 'admin-js' ),
			'wpsmSettings',
			array(
				'nonce'          => wp_create_nonce( 'wp_rest' ),
				'wpsm_ajax_base' => esc_url_raw( rest_url( 'wpsm/v1' ) ),
			)
		);

		wp_enqueue_script( wp_sites_monitor_prefix( 'admin-js' ) );
	}

	/**
	 * Register the frontend scripts.
	 *
	 * @since 0.0.1
	 *
	 * @return void;
	 */
	public function register_front_scripts()
	{
		$script_asset_path = WP_SITES_MONITOR_DIR_PATH . 'dist/front.asset.php';
		$script_asset      = require $script_asset_path;

		wp_enqueue_style(
			wp_sites_monitor_prefix( 'front-css' ),
			wp_sites_monitor_asset_url( 'front.css' ),
			array( 'wp-components' ),
			$script_asset['version']
		);

		if (sm_fs()->can_use_premium_code__premium_only()) {
			wp_register_script(
				wp_sites_monitor_prefix( 'front-js' ),
				wp_sites_monitor_asset_url( 'front-premium.js' ),
				$script_asset['dependencies'],
				$script_asset['version'],
				true
			);
		}

		/**
		 * If there is no valid license OR the user does not have the premium version installed.
		 */
		if ( ! sm_fs()->can_use_premium_code() || ! sm_fs()->is_premium() ) {
			wp_register_script(
				wp_sites_monitor_prefix( 'front-js' ),
				wp_sites_monitor_asset_url( 'front.js' ),
				$script_asset['dependencies'],
				$script_asset['version'],
				true
			);
		}

		$localize = array(
			'isPremium' => sm_fs()->can_use_premium_code(),
		);
		wp_localize_script( wp_sites_monitor_prefix( 'front-js' ), 'wpsmSettings', $localize );
		wp_enqueue_script( wp_sites_monitor_prefix( 'front-js' ) );
	}

	/**
	 * Render the sites monitor div on the frontend.
	 *
	 * @since 0.0.1
	 *
	 * @return string|null;
	 */
	public function render_sites_monitor_block()
	{
		$id = get_the_ID();

		if ($id) {
			return '<div class="wp-sites-monitor-front" data-id="' . esc_attr( $id ) . '"></div>';
		}

		return null;
	}

	/**
	 * Render the sites monitor list div on the frontend.
	 *
	 * @since 0.0.1
	 *
	 * @return string;
	 */
	public function render_sites_monitor_list_block( $block_attributes )
	{
		// Encode the attributes as a JSON string using wp_json_encode.
		$data_attributes = wp_json_encode( $block_attributes );

		return '<div class="wp-sites-monitor-list-front" data-attributes="' . esc_attr( $data_attributes ) . '"></div>';
	}

	/**
	 * Register blocks.
	 *
	 * @since 0.0.1
	 *
	 * @return void;
	 */
	public function register_block_scripts()
	{
		// Block editor is not available.
		if ( ! function_exists( 'register_block_type_from_metadata' )) {
			return;
		}

		register_block_type_from_metadata(
			WP_SITES_MONITOR_DIR_PATH . 'dist/sites-monitor',
			array(
				'render_callback' => array( $this, 'render_sites_monitor_block' ),
			)
		);

		register_block_type_from_metadata(
			WP_SITES_MONITOR_DIR_PATH . 'dist/sites-monitor-list',
			array(
				'render_callback' => array( $this, 'render_sites_monitor_list_block' ),
			)
		);

		// Pass the wpsmSettings variable to sites-monitor-list block in the editor.
		$localize = array(
			'isPremium' => sm_fs()->can_use_premium_code(),
		);
		wp_localize_script( 'wp-sites-monitor-sites-monitor-list-editor-script', 'wpsmSettings', $localize );
	}
}
