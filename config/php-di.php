<?php

use Facile\JoseVerifier\JWK\JwksProviderBuilder;
use Facile\OpenIDClient\Client\ClientBuilder;
use Facile\OpenIDClient\Client\ClientInterface;
use Facile\OpenIDClient\Client\Metadata\ClientMetadata;
use Facile\OpenIDClient\Client\Metadata\ClientMetadataInterface;
use Facile\OpenIDClient\Issuer\IssuerBuilder;
use Facile\OpenIDClient\Issuer\IssuerInterface;
use Facile\OpenIDClient\Issuer\Metadata\Provider\MetadataProviderBuilder;
use Facile\OpenIDClient\Service\AuthorizationService;
use Facile\OpenIDClient\Service\Builder\AuthorizationServiceBuilder;
use Odan\Session\PhpSession;
use Odan\Session\SessionInterface;
use OWCSignicatOpenID\Block;
use OWCSignicatOpenID\Interfaces\Providers\AppServiceProviderInterface;
use OWCSignicatOpenID\Interfaces\Services\BlockServiceInterface;
use OWCSignicatOpenID\Interfaces\Services\CacheServiceInterface;
use OWCSignicatOpenID\Interfaces\Services\LifeCycleServiceInterface;
use OWCSignicatOpenID\Interfaces\Services\OpenIDServiceInterface;
use OWCSignicatOpenID\Interfaces\Services\ResourceServiceInterface;
use OWCSignicatOpenID\Interfaces\Services\RouteServiceInterface;
use OWCSignicatOpenID\Interfaces\Services\SettingsServiceInterface;
use OWCSignicatOpenID\Interfaces\Services\ViewServiceInterface;
use OWCSignicatOpenID\Logger;
use OWCSignicatOpenID\Modal;
use OWCSignicatOpenID\Providers\AppServiceProvider;
use OWCSignicatOpenID\Services\BlockService;
use OWCSignicatOpenID\Services\CacheService;
use OWCSignicatOpenID\Services\LifeCycleService;
use OWCSignicatOpenID\Services\OpenIDService;
use OWCSignicatOpenID\Services\ResourceService;
use OWCSignicatOpenID\Services\RouteService;
use OWCSignicatOpenID\Services\SettingsService;
use OWCSignicatOpenID\Services\ViewService;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

return [
    'blocks' => [
        'eherkenning',
        'eidas',
    ],

    // 'blocks.eherkenning'               => [
    //     function ($container) {
    //         return new Block\eHerkenning(
    //             $container['hooks.oidc'],
    //             $container['session']
    //         );
    //     },
    // ],
    // 'blocks.eidas'                     => [
    //     function ($container) {
    //         return new Block\eIDAS(
    //             $container['hooks.oidc'],
    //             $container['session']
    //         );
    //     },
    // ],

    LoggerInterface::class             => fn (ContainerInterface $container): LoggerInterface => new Logger($container->get('logger.level')),
    'logger.level'                     => fn (): string => (defined('WP_DEBUG') && WP_DEBUG) ? LogLevel::WARNING : '',
    Modal::class                       => fn (ContainerInterface $container): Modal => new Modal($container->get(SessionInterface::class)),
    MetadataProviderBuilder::class     => fn (ContainerInterface $container): MetadataProviderBuilder => (new MetadataProviderBuilder)->setCache($container->get(CacheServiceInterface::class))->setCacheTtl(MONTH_IN_SECONDS),
    JwksProviderBuilder::class         => fn (ContainerInterface $container): JwksProviderBuilder => (new JwksProviderBuilder())->setCache($container->get(CacheServiceInterface::class))->setCacheTtl(DAY_IN_SECONDS),
    ClientMetadataInterface::class     => function (ContainerInterface $container): ClientMetadata {
        $settings = $container->get(SettingsServiceInterface::class);

        return ClientMetadata::fromArray(
            [
                'client_id'     => sanitize_text_field($settings->get_setting('client_id')),
                'client_secret' => sanitize_text_field($settings->get_setting('client_secret')),
                'redirect_uris' => [
                    sanitize_text_field(get_site_url(null, $settings->get_setting('path_redirect'))),
                ],
            ]
        );
    },
    IssuerInterface::class             => function (ContainerInterface $container): IssuerInterface {
        return (new IssuerBuilder())
            ->setMetadataProviderBuilder($container->get(MetadataProviderBuilder::class))
            ->setJwksProviderBuilder($container->get(JwksProviderBuilder::class))
            ->build($container->get(SettingsServiceInterface::class)->get_setting('configuration_url'));
    },
    ClientInterface::class             => fn (ContainerInterface $container): ClientInterface => (new ClientBuilder())
        ->setIssuer($container->get(IssuerInterface::class))
        ->setClientMetadata($container->get(ClientMetadataInterface::class))
        ->build(),
    AuthorizationService::class                     => fn (): AuthorizationService => (new AuthorizationServiceBuilder())->build(),
    SessionInterface::class                         => function (): PhpSession {
        $session = new PhpSession();
        $session->setOptions(
            [
                'name'            => 'OWC_Signicat_OpenID',
                'cookie_secure'   => true,
                'cookie_httponly' => true,
            ]
        );
        $session->start();

        return $session;
    },

    // Providers.
    AppServiceProviderInterface::class => \DI\autowire(AppServiceProvider::class),

    // Services.
    LifeCycleServiceInterface::class   => \DI\autowire(LifeCycleService::class),
    ResourceServiceInterface::class    => \DI\autowire(ResourceService::class),
    SettingsServiceInterface::class    => \DI\autowire(SettingsService::class),
    BlockServiceInterface::class       => \DI\autowire(BlockService::class),
    CacheServiceInterface::class       => \DI\autowire(CacheService::class),
    ViewServiceInterface::class        => \DI\autowire(ViewService::class),
    RouteServiceInterface::class       => \DI\autowire(RouteService::class),
    OpenIDServiceInterface::class      => \DI\autowire(OpenIDService::class),
];
