<?php
/**
 * App service provider (registers general plugins functionality).
 *
 * @package OWC_Signicat_OpenID
 * @author  Yard | Digital Agency
 * @since   0.0.1
 */

namespace OWCSignicatOpenID\Providers;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' )) {
	exit;
}

use OWCSignicatOpenID\Interfaces\Providers\AppServiceProviderInterface;
use OWCSignicatOpenID\Interfaces\Services\BlockServiceInterface;
use OWCSignicatOpenID\Interfaces\Services\GravityFormsServiceInterface;
use OWCSignicatOpenID\Interfaces\Services\LifeCycleServiceInterface;
use OWCSignicatOpenID\Interfaces\Services\ModalServiceInterface;
use OWCSignicatOpenID\Interfaces\Services\RouteServiceInterface;

/**
 * App service provider (registers general plugins functionality).
 *
 * @since 0.0.1
 */
class AppServiceProvider extends ServiceProvider implements AppServiceProviderInterface
{
	private const ASSETS_HANDLE = 'owc-signicat-openid-editor';

	public function __construct(
		LifeCycleServiceInterface $lifeCycleService,
		BlockServiceInterface $blockService,
		RouteServiceInterface $routeService,
		GravityFormsServiceInterface $gravityFormsService,
		ModalServiceInterface $modalService
	) {
		$this->services = array(
			'life_cycle'    => $lifeCycleService,
			'block'         => $blockService,
			'route'         => $routeService,
			'gravity_forms' => $gravityFormsService,
			'modal'         => $modalService,
		);

		$this->registerHooks();
	}

	protected function registerHooks(): void
	{
		add_action( 'admin_enqueue_scripts', $this->enqueueAssets( ... ) );
	}

	public function enqueueAssets(): void
	{
		$script_asset_path = OWC_SIGNICAT_OPENID_DIR_PATH . 'dist/editor.asset.php';
		$script_asset      = require $script_asset_path;

		wp_enqueue_script(
			self::ASSETS_HANDLE,
			OWC_SIGNICAT_OPENID_PLUGIN_URL . 'dist/editor.js',
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		wp_enqueue_style(
			self::ASSETS_HANDLE,
			OWC_SIGNICAT_OPENID_PLUGIN_URL . 'dist/editor.css',
			array(),
			$script_asset['version']
		);
	}
}
