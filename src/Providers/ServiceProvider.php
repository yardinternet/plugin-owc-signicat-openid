<?php
/**
 * Register service provider.
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
if ( ! defined( 'ABSPATH' )) {
	exit;
}

use OWCSignicatOpenID\Interfaces\Providers\ServiceProviderInterface;
use OWCSignicatOpenID\Interfaces\Services\ServiceInterface;

/**
 * Register service provider.
 *
 * @since 0.0.1
 */
class ServiceProvider implements ServiceProviderInterface
{
	protected array $services = array();

	public function register(): void
	{
		foreach ($this->services as $service) {
			$service->register();
		}
	}

	public function boot(): void
	{
		foreach ($this->services as $service) {
			if (false === $service instanceof ServiceInterface) {
				continue;
			}
			$service->boot();
		}
	}
}
