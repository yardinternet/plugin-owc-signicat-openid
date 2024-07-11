<?php

declare(strict_types=1);

namespace OWCSignicatOpenID\Services;

use OWCSignicatOpenID\Interfaces\Services\BlockServiceInterface;
use OWCSignicatOpenID\Interfaces\Services\IdentityProviderServiceInterface;
use OWCSignicatOpenID\Interfaces\Services\OpenIDServiceInterface;
use OWCSignicatOpenID\Interfaces\Services\ViewServiceInterface;

class BlockService extends Service implements BlockServiceInterface
{
    private const BLOCK_CATEGORY = 'owc-signicat-openid';

    protected OpenIDServiceInterface $openIDClient;
    protected ViewServiceInterface $viewService;
    protected IdentityProviderServiceInterface $identityProviderService;

    public function __construct(
        OpenIDServiceInterface $openIDClient,
        ViewServiceInterface $viewService,
        IdentityProviderServiceInterface $idpService
    ) {
        $this->openIDClient = $openIDClient;
        $this->viewService = $viewService;
        $this->identityProviderService = $idpService;
    }

    public function register()
    {
        add_action('init', [$this, 'registerBlocks']);
        add_filter('block_categories_all', [$this, 'registerBlockCategory'], 10, 2);
    }
    public function registerBlocks()
    {
        $variations = [];
        $isFirstVariation = true;
        foreach ($this->identityProviderService->getEnabledIdentityProviders() as $identityProvider) {
            $variations[] = [
                'name' => $identityProvider->getSlug(),
                'title' => $identityProvider->getName(),
                'description' => sprintf('%s login knop', $identityProvider->getName()),
                'attributes' => ['idp' => $identityProvider->getSlug()],
                'isActive' => ['idp'],
                'isDefault' => $isFirstVariation,
            ];
            $isFirstVariation = false;
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
        $identityProvider = $this->identityProviderService->getIdentityProvider($attributes['idp']);
        //TODO: afbreken als idp niet gevonden wordt?
        $image = $identityProvider->getLogoUrl();
		$redirectUrl = $attributes['redirectUrl'] ?? wp_unslash($_SERVER['REQUEST_URI']);
        $buttonText = $attributes['buttonText'] ?? '';
        $url = $this->openIDClient->getLoginUrl($identityProvider, $redirectUrl);

        return $this->viewService->render('block', ['url' => $url, 'image' => $image, 'buttonText' => $buttonText]);
    }
}
