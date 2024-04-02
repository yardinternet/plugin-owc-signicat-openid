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

use OWCSignicatOpenID\Interfaces\Providers\AppServiceProviderInterface;
use OWCSignicatOpenID\Interfaces\Services\BlockServiceInterface;
use OWCSignicatOpenID\Interfaces\Services\LifeCycleServiceInterface;
use OWCSignicatOpenID\Interfaces\Services\ResourceServiceInterface;
use OWCSignicatOpenID\Interfaces\Services\RouteServiceInterface;
use OWCSignicatOpenID\Interfaces\Services\SettingsServiceInterface;

/**
 * App service provider (registers general plugins functionality).
 *
 * @since 0.0.1
 */
class AppServiceProvider extends ServiceProvider implements AppServiceProviderInterface
{
    public function __construct(
        LifeCycleServiceInterface $life_cycle_service,
        ResourceServiceInterface $resource_service,
        SettingsServiceInterFace $settings_service,
        BlockServiceInterface $block_service,
        RouteServiceInterface $route_service
    ) {
        $this->services = [
            'life_cycle' => $life_cycle_service,
            'resource'   => $resource_service,
            'settings'   => $settings_service,
            'block'      => $block_service,
            'route'      => $route_service,
        ];

        $this->register_hooks();
    }

    protected function register_hooks()
    {
    }
}
