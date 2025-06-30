<?php

declare(strict_types=1);

namespace OWCSignicatOpenID\Services;

use OWCSignicatOpenID\Interfaces\Services\SettingsServiceInterface;
use OWCSignicatOpenID\Interfaces\Services\ViewServiceInterface;

class SettingsService extends Service implements SettingsServiceInterface
{
	protected ViewServiceInterface $view_service;

	protected array $settings = array(
		'configuration_url',
		'client_id',
		'client_secret',
		'path_login',
		'path_logout',
		'path_redirect',
		'enable_simulator',
	);

	public function __construct(ViewServiceInterface $view_service )
	{
		$this->view_service = $view_service;
	}

	public function register()
	{
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
	}

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

	public function register_settings(): void
	{
		foreach ($this->settings as $setting) {
			register_setting( 'owc_signicat_openid_settings_group', "owc_signicat_openid_{$setting}_settings" );
		}
	}

	public function render_settings_page(): void
	{
		foreach ($this->settings as $setting) {
			$data[ $setting ] = $this->getSetting( $setting );
		}
		echo $this->view_service->render( 'settings', $data );
	}

	public function getSetting(string $setting )
	{
		return get_option( "owc_signicat_openid_{$setting}_settings" );
	}
}
