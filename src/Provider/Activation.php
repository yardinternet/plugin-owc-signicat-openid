<?php
/**
 * Plugin activation provider.
 *
 * @package OWC_Signicat_OpenID
 * @author  Yard | Digital Agency
 * @since   0.0.1
 */

declare ( strict_types = 1 );

namespace OWCSignicatOpenID\Provider;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' )) {
	exit;
}

use Cedaro\WP\Plugin\AbstractHookProvider;

/**
 * Class to activate the plugin.
 *
 * @since 0.0.1
 */
class Activation extends AbstractHookProvider
{
	public function register_hooks()
	{
		register_activation_hook(
			$this->plugin->get_file(),
			array( $this, 'activate' )
		);
	}

	public static function activate()
	{
		// Do something.
	}
}
