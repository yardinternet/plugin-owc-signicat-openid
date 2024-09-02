<?php

declare(strict_types=1);

namespace OWCSignicatOpenID\Services;

use OWCSignicatOpenID\Interfaces\Services\IdentityProviderServiceInterface;
use OWCSignicatOpenID\Interfaces\Services\ModalServiceInterface;
use OWCSignicatOpenID\Interfaces\Services\OpenIDServiceInterface;
use OWCSignicatOpenID\Interfaces\Services\ViewServiceInterface;

class ModalService extends Service implements ModalServiceInterface
{
    private const ASSETS_HANDLE = 'owc-signicat-openid-modal';

    protected IdentityProviderServiceInterface $identityProviderService;
    protected ViewServiceInterface $viewService;
    protected OpenIDServiceInterface $openIDService;

    public function __construct(
        IdentityProviderServiceInterface $identityProviderService,
        ViewServiceInterface $viewService,
        OpenIDServiceInterface $openIDService
    ) {
        $this->identityProviderService = $identityProviderService;
        $this->viewService = $viewService;
        $this->openIDService = $openIDService;
    }

    public function register()
    {
		add_action('wp_body_open', [$this, 'addModalHtml']);
        add_action('wp_enqueue_scripts', [$this, 'enqueueScripts']);
    }

    public function addModalHtml()
    {
        if (! $this->shouldLoadModal()) {
			return;
        }

        echo $this->viewService->render(
            'modal',
            []
        );
    }

    public function enqueueScripts()
    {
        if (! $this->shouldLoadModal()) {
            return;
        }

        $script_asset_path = OWC_SIGNICAT_OPENID_DIR_PATH . 'dist/modal.asset.php';
        if (! file_exists($script_asset_path)) {
            throw new \Error(
                'You need to run `npm run watch` or `npm run build` to be able to use this plugin first.'
            );
        }

        $script_asset = require $script_asset_path;

        wp_register_script(
            self::ASSETS_HANDLE,
            OWC_SIGNICAT_OPENID_PLUGIN_URL . 'dist/modal.js',
            $script_asset['dependencies'],
            $script_asset['version'],
            true
        );

        wp_localize_script(
            self::ASSETS_HANDLE,
            'owcSignicatOIDCModalSettings',
            [
                'sessionTTL' => 0.2,
            ]
        );

        wp_enqueue_script(self::ASSETS_HANDLE);
        wp_enqueue_style(self::ASSETS_HANDLE, OWC_SIGNICAT_OPENID_PLUGIN_URL . '/dist/modal.css', [], $script_asset['version']);
    }

    private function shouldLoadModal(): bool
    {
        $shouldLoadModal = false;
        foreach ($this->identityProviderService->getEnabledIdentityProviders() as $identityProvider) {
            if ($this->openIDService->hasActiveSession($identityProvider)) {
                $shouldLoadModal = true;

                break;
            }
        }

        return $shouldLoadModal;
    }
}
