<?php
/**
 * Service Provider class.
 *
 * @package OWC_Signicat_OpenID
 * @author  Yard | Digital Agency
 * @since   0.0.1
 */

declare ( strict_types = 1 );

namespace OWCSignicatOpenID;

use Pimple\Container as PimpleContainer;
use Pimple\ServiceProviderInterface;

use OWCSignicatOpenID\Provider;

/**
 * Plugin service provider class.
 *
 * @since 0.0.1
 */
class ServiceProvider implements ServiceProviderInterface
{
	/**
	 * Register services.
	 *
	 * @param PimpleContainer $container Container instance.
	 */
	public function register( PimpleContainer $container ) {
		$container['hooks.activation'] = function () {
			return new Provider\Activation();
		};

		$container['hooks.deactivation'] = function () {
			return new Provider\Deactivation();
		};

		$container['hooks.uninstall'] = function () {
			return new Provider\Uninstall();
		};
	}
}
