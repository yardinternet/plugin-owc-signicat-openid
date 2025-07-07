<?php
/**
 * Life cycle service interface.
 *
 * @package OWC_Signicat_OpenID
 *
 * @author  Yard | Digital Agency
 *
 * @since   0.0.1
 */

namespace OWCSignicatOpenID\Interfaces\Services;

interface LifeCycleServiceInterface extends ServiceInterface
{
	public function install();
	public function deactivate();
	public static function uninstall();
}
