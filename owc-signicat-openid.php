<?php

/**
 * OWC Signicat OpenID
 *
 * @package OWC_Signicat_OpenID
 *
 * @author  Yard | Digital Agency
 *
 * @since   1.1.1
 *
 * Plugin Name:       OWC | Signicat OpenID
 * Plugin URI:        https://github.com/yardinternet/plugin-signicat-openid
 * Description:       Log into the Signicat Broker with OpenID
 * Version:           1.3.2
 * Author:            Yard | Digital Agency
 * Author URI:        https://www.yard.nl
 * License:           EUPL
 * License URI:       https://github.com/yardinternet/plugin-owc-signicat-openid/blob/main/LICENSE.txt
 * Text Domain:       owc-signicat-openid
 * Domain Path:       /languages
 */

declare(strict_types=1);

/**
 * If this file is called directly, abort.
 */
if ( ! defined( 'WPINC' )) {
	die;
}

// Define constants.
define( 'OWC_SIGNICAT_OPENID_VERSION', '1.3.2' );
define( 'OWC_SIGNICAT_OPENID_REQUIRED_WP_VERSION', '6.0' );
define( 'OWC_SIGNICAT_OPENID_FILE', __FILE__ );
define( 'OWC_SIGNICAT_OPENID_SLUG', basename( OWC_SIGNICAT_OPENID_FILE, '.php' ) );
define( 'OWC_SIGNICAT_OPENID_DIR_PATH', plugin_dir_path( OWC_SIGNICAT_OPENID_FILE ) );
define( 'OWC_SIGNICAT_OPENID_PLUGIN_URL', plugins_url( '/', OWC_SIGNICAT_OPENID_FILE ) );

if (file_exists( OWC_SIGNICAT_OPENID_DIR_PATH . '/vendor/autoload.php' )) {
	require_once OWC_SIGNICAT_OPENID_DIR_PATH . '/vendor/autoload.php';
}

add_action(
	'after_setup_theme',
	function () {
		$init = new OWCSignicatOpenID\Bootstrap();
	}
);
