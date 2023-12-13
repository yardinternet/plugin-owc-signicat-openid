<?php
/**
 * App service provider (registers general plugins functionality).
 *
 * @package OWC_Signicat_OpenID
 * @author  Yard | Digital Agency
 * @since   0.0.1
 */

namespace OWCSignicatOpenID\Providers;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' )) {
	exit;
}

use OWCSignicatOpenID\Interfaces\Providers\AppServiceProviderInterface;
use OWCSignicatOpenID\Interfaces\Services\LifeCycleServiceInterface;
use OWCSignicatOpenID\Interfaces\Services\ResourceServiceInterface;

/**
 * App service provider (registers general plugins functionality).
 *
 * @since 0.0.1
 */
class AppServiceProvider extends ServiceProvider implements AppServiceProviderInterface
{
	public function __construct(
		LifeCycleServiceInterface $life_cycle_service,
		ResourceServiceInterface $resource_service
	) {
		$this->services = array(
			'life_cycle' => $life_cycle_service,
			'resource'   => $resource_service,
		);

		$this->register_hooks();
	}

	protected function register_hooks() {
	}
}
