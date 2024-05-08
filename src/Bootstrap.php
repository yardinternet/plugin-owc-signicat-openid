<?php
/**
 * OWC Signicat OpenID
 *
 * @package OWC_Signicat_OpenID
 *
 * @author  Yard | Digital Agency
 *
 * @since   0.0.1
 */

namespace OWCSignicatOpenID;

use OWCSignicatOpenID\Interfaces\Providers\AppServiceProviderInterface;
use OWCSignicatOpenID\Interfaces\Providers\SettingsServiceProviderInterface;
use Psr\Container\ContainerInterface;

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
     */
    private array $providers;

    /**
     * Plugin constructor.
     *
     * @since 0.0.1
     */
    public function __construct()
    {
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
    protected function get_providers(): array
    {
        $providers = [
            SettingsServiceProviderInterface::class,
            AppServiceProviderInterface::class,
        ];
        $registeredProviders = [];
        foreach ($providers as $provider) {
            try {
                $registeredProviders[] = $this->container->get($provider);
            } catch(\Exception $e) {

            }
        }

        return $registeredProviders;
    }

    protected function register_providers(): void
    {
        foreach ($this->providers as $provider) {
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
    protected function boot_providers(): void
    {
        foreach ($this->providers as $provider) {
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
    protected function build_container(): ContainerInterface
    {
        $builder = new \DI\ContainerBuilder();
        $builder->addDefinitions(__DIR__ . './../config/php-di.php');
        $builder->useAnnotations(true);
        $container = $builder->build();

        return $container;
    }
}
