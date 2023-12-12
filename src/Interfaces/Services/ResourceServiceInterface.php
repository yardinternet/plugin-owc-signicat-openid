<?php
/**
 * Resource service interface.
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
 * Resource service interface.
 *
 * @since 0.0.1
 */
interface ResourceServiceInterface extends ServiceInterface
{
	public function register_admin_scripts( $hook_suffix );
	public function register_front_scripts();
	public function render_sites_monitor_block();
	public function render_sites_monitor_list_block( $block_attributes );
	public function register_block_scripts();
}
