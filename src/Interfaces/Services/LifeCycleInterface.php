<?php
/**
 * Life cycle service interface.
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
 * Life cycle service interface.
 *
 * @since 0.0.1
 */
interface LifeCycleServiceInterface extends ServiceInterface
{
	public function install();
	public function deactivate();
	public static function uninstall();
}
