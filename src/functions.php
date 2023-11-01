<?php
/**
 * Helper functions.
 *
 * @package OWC_Signicat_OpenID
 * @author  Yard | Digital Agency
 * @since   0.0.1
 */

declare ( strict_types = 1 );

namespace OWCSignicatOpenID;

/**
 * Retrieve the main plugin instance.
 *
 * @since 0.0.1
 * @return Plugin
 */
function plugin(): Plugin {
	static $instance;

	if (null === $instance) {
		$instance = new Plugin();
	}

	return $instance;
}

/**
 * Autoload mapped classes.
 *
 * @since 0.0.1
 * @param string $class_name Class name.
 */
function autoloader_classmap( string $class_name ) {
	$class_map = array(
		'PclZip' => ABSPATH . 'wp-admin/includes/class-pclzip.php',
	);

	if ( isset( $class_map[ $class_name ] ) ) {
		require_once $class_map[ $class_name ];
	}
}
