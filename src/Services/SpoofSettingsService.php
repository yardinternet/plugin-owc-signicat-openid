<?php

declare(strict_types=1);

namespace OWCSignicatOpenID\Services;

use OWCSignicatOpenID\Interfaces\Services\SettingsServiceInterface;
use OWCSignicatOpenID\Interfaces\Services\ViewServiceInterface;

class SpoofSettingsService extends Service implements SettingsServiceInterface
{
    protected ViewServiceInterface $view_service;

    protected array $settings = [
        'enable_simulator', 'bsn', 'levelOfAssurance'
    ];

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
            'owc-signicat-simulator',
            esc_html__('Signicat Simulator', 'owc-signicat-openid'),
            'manage_options',
            'owc-signicat-simulator',
            [ $this, 'render_settings_page' ]
        );
    }

    public function register_settings(): void
    {
        foreach ($this->settings as $setting) {
            register_setting('owc_signicat_simulator_settings_group', "owc_signicat_simulator_{$setting}_settings");
        }
    }

    public function render_settings_page(): void
    {
        foreach ($this->settings as $setting) {
            $data[$setting] = $this->getSetting($setting);
        }
        echo $this->view_service->render('spoof-settings', $data);
    }

    public function getSetting(string $setting)
    {
        return get_option("owc_signicat_simulator_{$setting}_settings");
    }
}
