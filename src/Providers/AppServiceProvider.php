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
use OWCSignicatOpenID\Interfaces\Services\GravityFormsServiceInterface;
use OWCSignicatOpenID\Interfaces\Services\LifeCycleServiceInterface;
use OWCSignicatOpenID\Interfaces\Services\ModalServiceInterface;
use OWCSignicatOpenID\Interfaces\Services\RouteServiceInterface;

/**
 * App service provider (registers general plugins functionality).
 *
 * @since 0.0.1
 */
class AppServiceProvider extends ServiceProvider implements AppServiceProviderInterface
{
    public function __construct(
        LifeCycleServiceInterface $lifeCycleService,
        BlockServiceInterface $blockService,
        RouteServiceInterface $routeService,
        GravityFormsServiceInterface $gravityFormsService,
        ModalServiceInterface $modalService
    ) {
        $this->services = [
            'life_cycle' => $lifeCycleService,
            'block'      => $blockService,
            'route'      => $routeService,
            'gravity_forms' => $gravityFormsService,
            'modal'     => $modalService,
        ];

        $this->register_hooks();
    }

    protected function register_hooks()
    {
    }
}
