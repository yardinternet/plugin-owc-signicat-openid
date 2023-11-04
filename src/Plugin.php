<?php
/**
 * Main plugin class
 *
 * @package OWC_Signicat_OpenID
 * @author  Yard | Digital Agency
 * @since   0.0.1
 */

declare ( strict_types = 1 );

namespace OWCSignicatOpenID;

use Cedaro\WP\Plugin\Plugin as BasePlugin;
use Psr\Container\ContainerInterface;

/**
 * Main plugin class - composition root.
 *
 * @since 0.0.1
 */
class Plugin extends BasePlugin implements Composable
{
	/**
	 * Compose the object graph.
	 *
	 * @since 0.0.1
	 */
	public function compose() {
		$container = $this->get_container();

		/**
		 * Start composing the object graph.
		 *
		 * @since 0.0.1
		 *
		 * @param Plugin             $plugin    Main plugin instance.
		 * @param ContainerInterface $container Dependency container.
		 */
		do_action( 'owc_signicat_openid_compose', $this, $container );

		// Register hook providers.
		$this
			->register_hooks( $container->get( 'blocks.eherkenning' ) )
			->register_hooks( $container->get( 'hooks.oidc' ) );

		if ( is_admin() ) {
			$this
				->register_hooks( $container->get( 'view.settings' ) );
		}

		/**
		 * Finished composing the object graph.
		 *
		 * @since 0.0.1
		 *
		 * @param Plugin             $plugin    Main plugin instance.
		 * @param ContainerInterface $container Dependency container.
		 */
		do_action( 'owc_signicat_openid_composed', $this, $container );
	}
}
