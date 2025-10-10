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
use OWCSignicatOpenID\Interfaces\Providers\AppServiceProviderInterface;
use OWCSignicatOpenID\Interfaces\Providers\SettingsServiceProviderInterface;
use OWCSignicatOpenID\Interfaces\Services\BlockServiceInterface;
use OWCSignicatOpenID\Interfaces\Services\CacheServiceInterface;
use OWCSignicatOpenID\Interfaces\Services\GravityFormsServiceInterface;
use OWCSignicatOpenID\Interfaces\Services\IdentityProviderServiceInterface;
use OWCSignicatOpenID\Interfaces\Services\LifeCycleServiceInterface;
use OWCSignicatOpenID\Interfaces\Services\ModalServiceInterface;
use OWCSignicatOpenID\Interfaces\Services\OpenIDServiceInterface;
use OWCSignicatOpenID\Interfaces\Services\RouteServiceInterface;
use OWCSignicatOpenID\Interfaces\Services\SettingsServiceInterface;
use OWCSignicatOpenID\Interfaces\Services\ViewServiceInterface;
use OWCSignicatOpenID\Logger;
use OWCSignicatOpenID\Providers\AppServiceProvider;
use OWCSignicatOpenID\Providers\SettingsServiceProvider;
use OWCSignicatOpenID\Services\BlockService;
use OWCSignicatOpenID\Services\CacheService;
use OWCSignicatOpenID\Services\GravityFormsService;
use OWCSignicatOpenID\Services\IdentityProviderService;
use OWCSignicatOpenID\Services\LifeCycleService;
use OWCSignicatOpenID\Services\ModalService;
use OWCSignicatOpenID\Services\OpenIDService;
use OWCSignicatOpenID\Services\RouteService;
use OWCSignicatOpenID\Services\SettingsService;
use OWCSignicatOpenID\Services\ViewService;
use OWCSignicatOpenID\UserData\DigiDUserData;
use OWCSignicatOpenID\UserData\eHerkenningUserData;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

return array(
	'idps'                                  => array(
		array(
			'slug'          => 'digid',
			'name'          => 'DigiD',
			'mapping'       => array(
				'sub' => 'bsn',
			),
			'userDataClass' => DigiDUserData::class,
		),
		array(
			'slug'          => 'eherkenning',
			'name'          => 'eHerkenning',
			'mapping'       => array(
				'urn:etoegang:1.9:EntityConcernedID:KvKnr' => 'kvk',
				'urn:etoegang:1.9:EntityConcernedID:RSIN'  => 'rsin',

				// Version 2
				'chamber_of_commerce'                      => 'kvk',
				'eherkenning_vestigingsnr'                 => 'vestigingsNummer',
				'eherkenning_rsin'                         => 'rsin',
			),
			'userDataClass' => eHerkenningUserData::class,
		),
	),
	'idps_errors'                           => function () {
		add_action(
			'init',
			function () {
				$file = sprintf( '%s/idps_errors.php', __DIR__ );

				return file_exists( $file ) ? require_once $file : array();
			}
		);
	},
	LoggerInterface::class                  => fn (ContainerInterface $container ): LoggerInterface => new Logger( $container->get( 'logger.level' ) ),
	'logger.level'                          => fn (): string => ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ? LogLevel::WARNING : '',
	MetadataProviderBuilder::class          => fn (ContainerInterface $container ): MetadataProviderBuilder => ( new MetadataProviderBuilder() )->setCache( $container->get( CacheServiceInterface::class ) )->setCacheTtl( MONTH_IN_SECONDS ),
	JwksProviderBuilder::class              => fn (ContainerInterface $container ): JwksProviderBuilder => ( new JwksProviderBuilder() )->setCache( $container->get( CacheServiceInterface::class ) )->setCacheTtl( DAY_IN_SECONDS ),
	ClientMetadataInterface::class          => function (ContainerInterface $container ): ClientMetadata {
		$settings = $container->get( SettingsServiceInterface::class );

		return ClientMetadata::fromArray(
			array(
				'client_id'     => sanitize_text_field( $settings->getSetting( 'client_id' ) ),
				'client_secret' => sanitize_text_field( $settings->getSetting( 'client_secret' ) ),
				'redirect_uris' => array(
					sanitize_text_field( get_site_url( null, $settings->getSetting( 'path_redirect' ) ) ),
				),
			)
		);
	},
	IssuerInterface::class                  => function (ContainerInterface $container ): IssuerInterface {
		return ( new IssuerBuilder() )
			->setMetadataProviderBuilder( $container->get( MetadataProviderBuilder::class ) )
			->setJwksProviderBuilder( $container->get( JwksProviderBuilder::class ) )
			->build( $container->get( SettingsServiceInterface::class )->getSetting( 'configuration_url' ) ?: '' );
	},
	ClientInterface::class                  => fn (ContainerInterface $container ): ClientInterface => ( new ClientBuilder() )
		->setIssuer( $container->get( IssuerInterface::class ) )
		->setClientMetadata( $container->get( ClientMetadataInterface::class ) )
		->build(),
	AuthorizationService::class             => fn (): AuthorizationService => ( new AuthorizationServiceBuilder() )->build(),
	'session_options'                       => array(
		'name'            => 'OWC_Signicat_OpenID',
		'cookie_secure'   => true,
		'cookie_httponly' => true,
	),
	SessionInterface::class                 => function (ContainerInterface $container ): PhpSession {
		$session = new PhpSession();
		$session->setOptions( $container->get( 'session_options' ) );

		return $session;
	},

	// Providers.
	AppServiceProviderInterface::class      => \DI\autowire( AppServiceProvider::class ),
	SettingsServiceProviderInterface::class => \DI\autowire( SettingsServiceProvider::class ),

	// Services.
	LifeCycleServiceInterface::class        => \DI\autowire( LifeCycleService::class ),
	SettingsServiceInterface::class         => \DI\autowire( SettingsService::class ),
	BlockServiceInterface::class            => \DI\autowire( BlockService::class ),
	CacheServiceInterface::class            => \DI\autowire( CacheService::class ),
	ViewServiceInterface::class             => \DI\autowire( ViewService::class ),
	RouteServiceInterface::class            => \DI\autowire( RouteService::class ),
	OpenIDServiceInterface::class           => \DI\autowire( OpenIDService::class ),
	GravityFormsServiceInterface::class     => \DI\autowire( GravityFormsService::class ),
	ModalServiceInterface::class            => \DI\autowire( ModalService::class ),
	IdentityProviderServiceInterface::class => \DI\autowire( IdentityProviderService::class )->method( 'setIdps', DI\get( 'idps' ) ),
);
