<?php

declare(strict_types=1);

namespace OWCSignicatOpenID\Services;

use OWCSignicatOpenID\Interfaces\Services\BlockServiceInterface;
use OWCSignicatOpenID\Interfaces\Services\OpenIDServiceInterface;
use OWCSignicatOpenID\Interfaces\Services\ViewServiceInterface;

class BlockService extends Service implements BlockServiceInterface
{
    protected OpenIDServiceInterface $openIDClient;
    protected ViewServiceInterface $viewService;

    public function __construct(OpenIDServiceInterface $openIDClient, ViewServiceInterface $viewService)
    {
        $this->openIDClient = $openIDClient;
        $this->viewService = $viewService;
    }

    public function register()
    {
        add_action('init', [$this, 'registerBlocks']);
        add_action('enqueue_block_editor_assets', [$this, 'registerBlockVariations']);
        add_filter('block_categories_all', [$this, 'registerBlockCategory'], 10, 2);
    }
    public function registerBlocks()
    {
        register_block_type_from_metadata(
            OWC_SIGNICAT_OPENID_DIR_PATH . '/dist/openid',
            [
                'render_callback' => [$this, 'renderBlock'],
            ]
        );
    }

    public function registerBlockCategory(array $blockCategories, \WP_Block_Editor_Context $blockEditorContext): array
    {
        $blockCategories[] = [
            'slug'  => 'owc-signicat-openid',
            'title' => 'Signicat OpenID',
        ];

        return $blockCategories;
    }

    public function registerBlockVariations()
    {
        wp_enqueue_script(
            'owc-signicat-openid-block-variations',
            OWC_SIGNICAT_OPENID_PLUGIN_URL . 'dist/openid/variations.js', //TODO: correct path
            [ 'wp-blocks', 'wp-dom-ready' ],
            OWC_SIGNICAT_OPENID_VERSION,
            false
        );
    }

    public function renderBlock(array $attributes, string $block_content, \WP_Block $block): string
    {
        $image = OWC_SIGNICAT_OPENID_PLUGIN_URL . "resources/img/logo-{$attributes['idp']}.svg"; //FIXME

        $url = add_query_arg(
            [
                'idp' => $attributes['idp'],
                'redirect_url' => $attributes['redirectUrl'],
            ],
            '/sso-login' // TODO: uit settings halen
        );

        return $this->viewService->render('block', ['url' => $url, 'image' => $image]);
    }
}
