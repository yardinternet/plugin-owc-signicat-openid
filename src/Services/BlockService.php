<?php

declare(strict_types=1);

namespace OWCSignicatOpenID\Services;

use OWCSignicatOpenID\Interfaces\Services\BlockServiceInterface;
use OWCSignicatOpenID\Interfaces\Services\IdentityProviderServiceInterface;
use OWCSignicatOpenID\Interfaces\Services\OpenIDServiceInterface;
use OWCSignicatOpenID\Interfaces\Services\SettingsServiceInterface;
use OWCSignicatOpenID\Interfaces\Services\ViewServiceInterface;

class BlockService extends Service implements BlockServiceInterface
{
    private const BLOCK_CATEGORY = 'owc-signicat-openid';

    protected OpenIDServiceInterface $openIDClient;
    protected ViewServiceInterface $viewService;
    protected SettingsServiceInterface $settings;
    protected IdentityProviderServiceInterface $idpService;

    public function __construct(
        OpenIDServiceInterface $openIDClient,
        ViewServiceInterface $viewService,
        SettingsServiceInterface $settings,
        IdentityProviderServiceInterface $idpService
    ) {
        $this->openIDClient = $openIDClient;
        $this->viewService = $viewService;
        $this->settings = $settings;
        $this->idpService = $idpService;
    }

    public function register()
    {
        add_action('init', [$this, 'registerBlocks']);
        add_filter('block_categories_all', [$this, 'registerBlockCategory'], 10, 2);
    }
    public function registerBlocks()
    {
        $variations = [];
        $firstVariation = true;
        foreach($this->idpService->getActiveIdentityProviders() as $idp) {
            $variations[] = [
                'name' => $idp->getSlug(),
                'title' => $idp->getName(),
                'description' => sprintf('%s login knop', $idp->getName()),
                'attributes' => ['idp' => $idp->getSlug()],
                'isActive' => ['idp'],
                'isDefault' => $firstVariation,
            ];
            $firstVariation = false;
        }

        register_block_type_from_metadata(
            OWC_SIGNICAT_OPENID_DIR_PATH . '/dist/openid',
            [
                'render_callback' => [$this, 'renderBlock'],
                'category'        => self::BLOCK_CATEGORY,
                'variations'      => $variations,
            ]
        );
    }

    public function registerBlockCategory(array $blockCategories, \WP_Block_Editor_Context $blockEditorContext): array
    {
        $blockCategories[] = [
            'slug'  => self::BLOCK_CATEGORY,
            'title' => 'Signicat OpenID',
        ];

        return $blockCategories;
    }

    public function renderBlock(array $attributes, string $block_content, \WP_Block $block): string
    {
        $identityProvider = $this->idpService->getActiveIdentityProvider($attributes['idp']);
        //TODO: afbreken als idp niet gevonden wordt?
        $image = $identityProvider->getLogoUrl();

        $url = add_query_arg(
            [
                'idp' => $attributes['idp'],
                'redirect_url' => $attributes['redirectUrl'] ?? wp_unslash($_SERVER['REQUEST_URI']),
            ],
            get_site_url(null, $this->settings->get_setting('path_login'))
        );

        return $this->viewService->render('block', ['url' => $url, 'image' => $image]);
    }
}
