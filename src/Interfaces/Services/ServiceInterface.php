<?php
/**
 * Service interface.
 *
 * @package OWC_Signicat_OpenID
 * @author  Yard | Digital Agency
 * @since   0.0.1
 */

namespace OWCSignicatOpenID\Interfaces\Services;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' )) {
	exit;
}

/**
 * Service interface.
 *
 * @since 0.0.1
 */
interface ServiceInterface
{
	public function register();
	public function boot();
}
