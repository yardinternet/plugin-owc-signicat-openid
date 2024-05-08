<?php
/**
 * App service provider (registers general plugins functionality).
 *
 * @package OWC_Signicat_OpenID
 *
 * @author  Yard | Digital Agency
 *
 * @since   0.0.1
 */

namespace OWCSignicatOpenID\Providers;

/**
 * Exit when accessed directly.
 */
if (! defined('ABSPATH')) {
    exit;
}

use OWCSignicatOpenID\Interfaces\Providers\SettingsServiceProviderInterface;
use OWCSignicatOpenID\Interfaces\Services\SettingsServiceInterface;

/**
 * App service provider (registers general plugins functionality).
 *
 * @since 0.0.1
 */
class SettingsServiceProvider extends ServiceProvider implements SettingsServiceProviderInterface
{
    public function __construct(
        SettingsServiceInterFace $settingsService
    ) {
        $this->services = [
            'settings'   => $settingsService,
        ];
    }
}
