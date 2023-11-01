<?php
/**
 * OWC Signicat OpenID
 *
 * @package OWC_Signicat_OpenID
 * @author  Yard | Digital Agency
 * @since   0.0.1
 *
 * Plugin Name:       OWC | Signicat OpenID
 * Plugin URI:        https://github.com/yardinternet/plugin-signicat-openid
 * Description:       Log into the Signicat Broker with OpenID
 * Version:           0.0.1
 * Author:            Yard | Digital Agency
 * Author URI:        https://www.yard.nl
 * License:           EUPL
 * License URI:       https://github.com/yardinternet/plugin-signicat-openid/LICENSE.txt
 * Text Domain:       owc-signicat-openid
 * Domain Path:       /languages
 */

declare ( strict_types = 1 );

namespace OWCSignicatOpenID;

/**
 * If this file is called directly, abort.
 */
if ( ! defined( 'WPINC' )) {
	die;
}

// Define constants.
define( 'OWC_SIGNICAT_OPENID_VERSION', '0.0.1' );
define( 'OWC_SIGNICAT_OPENID_REQUIRED_WP_VERSION', '6.0' );
define( 'OWC_SIGNICAT_OPENID_FILE', __FILE__ );
define( 'OWC_SIGNICAT_OPENID_DIR_PATH', plugin_dir_path( OWC_SIGNICAT_OPENID_FILE ) );
define( 'OWC_SIGNICAT_OPENID_PLUGIN_URL', plugins_url( '/', OWC_SIGNICAT_OPENID_FILE ) );

if (file_exists( OWC_SIGNICAT_OPENID_DIR_PATH . '/vendor/autoload.php' )) {
	require_once OWC_SIGNICAT_OPENID_DIR_PATH . '/vendor/autoload.php';
}

// Autoload mapped classes.
spl_autoload_register( __NAMESPACE__ . '\autoloader_classmap' );

// Load the WordPress plugin administration API.
require_once ABSPATH . 'wp-admin/includes/plugin.php';

// Create a container and register a service provider.
$owc_signicat_openid_container = new Container();
$owc_signicat_openid_container->register( new ServiceProvider() );


// Initialize the plugin and inject the container.
$owc_signicat_openid = plugin()
	->set_basename( plugin_basename( __FILE__ ) )
	->set_directory( plugin_dir_path( __FILE__ ) )
	->set_file( __DIR__ . '/owc-signicat-openid.php' )
	->set_slug( 'owc-signicat-openid' )
	->set_url( plugin_dir_url( __FILE__ ) )
	->set_container( $owc_signicat_openid_container )
	->register_hooks( $owc_signicat_openid_container->get( 'hooks.activation' ) )
	->register_hooks( $owc_signicat_openid_container->get( 'hooks.deactivation' ) );

add_action( 'plugins_loaded', array( $owc_signicat_openid, 'compose' ), 5 );
