<?php
/**
 * Register life cycle service.
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

// use OWCSignicatOpenID\Interfaces\Services\LifeCycleServiceInterface;

/**
 * Register life cycle service.
 *
 * @since 0.0.1
 */
class LifeCycleService extends Service
{
	/**
	 * @inheritDoc
	 */
	public function register()
	{
		register_activation_hook(
			OWC_SIGNICAT_OPENID_FILE,
			array( $this, 'install' )
		);

		register_deactivation_hook(
			OWC_SIGNICAT_OPENID_FILE,
			array( $this, 'deactivate' )
		);

		register_uninstall_hook(
			OWC_SIGNICAT_OPENID_FILE,
			array( __CLASS__, 'uninstall' )
		);
	}

	/**
	 * Plugin install callback.
	 *
	 * @since 0.0.1
	 *
	 * @return void;
	 */
	public function install()
	{
		// Do something.
	}

	/**
	 * Plugin deactivation callback.
	 *
	 * @since 0.0.1
	 *
	 * @return void;
	 */
	public function deactivate()
	{
		// Do something.
	}

	/**
	 * Plugin uninstall callback.
	 *
	 * @since 0.0.1
	 *
	 * @return void;
	 */
	public static function uninstall()
	{
		// Do something.
	}
}
