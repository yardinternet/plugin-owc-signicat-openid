<?php
/**
 * Register base service.
 *
 * @package OWC_Signicat_OpenID
 * @author  Yard | Digital Agency
 * @since   0.0.1
 */

namespace OWCSignicatOpenID\Services;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' )) {
	exit;
}

use OWCSignicatOpenID\Interfaces\Services\ServiceInterface;

/**
 * Register base service.
 */
class Service implements ServiceInterface
{
	/**
	 * Register the service.
	 *
	 * @since 0.0.1
	 *
	 * @return void
	 */
	public function register()
	{
	}

	/**
	 * Called when all services are registered.
	 *
	 * @since 0.0.1
	 *
	 * @return void
	 */
	public function boot()
	{
	}
}
