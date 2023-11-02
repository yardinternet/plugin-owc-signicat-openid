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

use Aura\Session\Session;
use Aura\Session\SessionFactory;
use Pimple\Container as PimpleContainer;
use Pimple\ServiceProviderInterface;
use Psr\Log\LogLevel;

use OWCSignicatOpenID\Logger;
use OWCSignicatOpenID\Provider;
use OWCSignicatOpenID\View;

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

		$container['hooks.oidc'] = function ( $container ) {
			return new Provider\OpenID(
				$container['logger'],
				$container['session']
			);
		};

		$container['hooks.uninstall'] = function () {
			return new Provider\Uninstall();
		};

		$container['logger'] = function ( $container ) {
			return new Logger( $container['logger.level'] );
		};

		$container['logger.level'] = function () {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				$level = LogLevel::WARNING;
			}

			return $level ?? '';
		};

		$container['session'] = function (): Session {
			$session_factory = new SessionFactory();
			$session         = $session_factory->newInstance( $_COOKIE );
			$session->setCookieParams(
				array(
					'secure'   => true,
					'httponly' => true,
				)
			);

			return $session;
		};

		$container['view.settings'] = function () {
			return new View\Settings();
		};
	}
}
