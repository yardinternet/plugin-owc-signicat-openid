<?php
/**
 * OWC Signicat OpenID
 *
 * @package OWC_Signicat_OpenID
 * @author  Yard | Digital Agency
 * @since   0.0.1
 */

namespace OWCSignicatOpenID;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' )) {
	exit;
}

use Psr\Container\ContainerInterface;
use OWCSignicatOpenID\Interfaces\Providers\ApiServiceProviderInterface;
use OWCSignicatOpenID\Interfaces\Providers\AppServiceProviderInterface;
use OWCSignicatOpenID\Interfaces\Providers\MonitorServiceProviderInterface;
use OWCSignicatOpenID\Interfaces\Providers\SettingsServiceProviderInterface;
use OWCSignicatOpenID\Interfaces\Providers\SiteServiceProviderInterface;

require_once __DIR__ . '/helpers.php';

/**
 * Bootstrap providers and containers.
 */
final class Bootstrap
{
	/**
	 * Dependency Injection container.
	 *
	 * @since 0.0.1
	 *
	 * @var ContainerInterface
	 */
	private ContainerInterface $container;

	/**
	 * Dependency providers.
	 *
	 * @since 0.0.1
	 *
	 * @var array
	 */
	private array $providers;

	/**
	 * Plugin constructor.
	 *
	 * @since 0.0.1
	 */
	public function __construct() {
		$this->container = $this->build_container();
		$this->providers = $this->get_providers();
		$this->register_providers();
		$this->boot_providers();
	}

	/**
	 * Gets all providers
	 *
	 * @since 0.0.1
	 *
	 * @return array
	 */
	protected function get_providers(): array {
		$providers = array(
			ApiServiceProviderInterface::class,
			AppServiceProviderInterface::class,
			MonitorServiceProviderInterface::class,
			SettingsServiceProviderInterface::class,
			SiteServiceProviderInterface::class,
		);
		foreach ( $providers as &$provider ) {
			$provider = $this->container->get( $provider );
		}
		return $providers;
	}

	/**
	 * Registers all providers.
	 *
	 * @since 0.0.1
	 *
	 * @return void
	 */
	protected function register_providers(): void {
		foreach ( $this->providers as $provider ) {
			$provider->register();
		}
	}

	/**
	 * Boots all providers.
	 *
	 * @since 0.0.1
	 *
	 * @return void
	 */
	protected function boot_providers(): void {
		foreach ( $this->providers as $provider ) {
			$provider->boot();
		}
	}

	/**
	 * Builds the container.
	 *
	 * @since 0.0.1
	 *
	 * @return ContainerInterface
	 */
	protected function build_container(): ContainerInterface {
		$builder = new \DI\ContainerBuilder();
		$builder->addDefinitions( __DIR__ . './../config/php-di.php' );
		$builder->useAnnotations( true );
		$container = $builder->build();
		return $container;
	}
}
