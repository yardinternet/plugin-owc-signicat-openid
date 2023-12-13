<?php
/**
 * OpenID service interface.
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
 * OpenID service interface.
 *
 * @since 0.0.1
 */
interface OpenIDServiceInterface extends ServiceInterface
{
	public function get_user_info();
}
