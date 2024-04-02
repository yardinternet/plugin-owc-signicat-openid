<?php

declare(strict_types=1);

namespace OWCSignicatOpenID\Services;

use OWCSignicatOpenID\Interfaces\Services\SettingsServiceInterface;
use OWCSignicatOpenID\Interfaces\Services\ViewServiceInterface;

class SettingsService extends Service implements SettingsServiceInterface
{

    protected ViewServiceInterface $view_service;

    public function __construct(ViewServiceInterface $view_service)
    {
        $this->view_service = $view_service;
    }

    public function register()
    {
        add_action('admin_init', [ $this, 'register_settings' ]);
        add_action('admin_menu', [ $this, 'add_settings_page' ]);
    }

    public function add_settings_page(): void
    {
        add_options_page(
            'owc-signicat-openid',
            esc_html__('Signicat OpenID', 'owc-signicat-openid'),
            'manage_options',
            'owc-signicat-openid',
            [ $this, 'render_settings_page' ]
        );
    }

    /**
     * Register settings fields.
     *
     * @since 0.0.1
     */
    public function register_settings(): void
    {
        register_setting('owc_signicat_openid_settings_group', 'owc_signicat_openid_configuration_url_settings');
        register_setting('owc_signicat_openid_settings_group', 'owc_signicat_openid_client_id_settings');
        register_setting('owc_signicat_openid_settings_group', 'owc_signicat_openid_client_secret_settings');
        register_setting('owc_signicat_openid_settings_group', 'owc_signicat_openid_path_login_settings');
        register_setting('owc_signicat_openid_settings_group', 'owc_signicat_openid_path_logout_settings');
        register_setting('owc_signicat_openid_settings_group', 'owc_signicat_openid_path_redirect_settings');
        register_setting('owc_signicat_openid_settings_group', 'owc_signicat_openid_path_refresh_settings');
    }

    /**
     * Render the settings page.
     *
     * @since 0.0.1
     */
    public function render_settings_page(): void
    {
        $data = [
            'configuration_url' => get_option('owc_signicat_openid_configuration_url_settings'),
            'client_id'         => get_option('owc_signicat_openid_client_id_settings'),
            'client_secret'     => get_option('owc_signicat_openid_client_secret_settings'),
            'path_login'        => get_option('owc_signicat_openid_path_login_settings'),
            'path_logout'       => get_option('owc_signicat_openid_path_logout_settings'),
            'path_redirect'     => get_option('owc_signicat_openid_path_redirect_settings'),
            'path_refresh'      => get_option('owc_signicat_openid_path_refresh_settings'),
        ];
        echo $this->view_service->render('settings', $data);
    }

    public function get_setting(string $setting)
    {
        return get_option("owc_signicat_openid_{$setting}_settings");
    }
}
