<?php
/**
 * Service provider interface.
 *
 * @package OWC_Signicat_OpenID
 * @author  Yard | Digital Agency
 * @since   0.0.1
 */

namespace OWCSignicatOpenID\Interfaces\Providers;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' )) {
	exit;
}

/**
 * Service provider interface.
 *
 * @since 0.0.1
 */
interface ServiceProviderInterface
{
	/**
	 * Register provider.
	 *
	 * @since 0.0.1
	 */
	public function register();
}
