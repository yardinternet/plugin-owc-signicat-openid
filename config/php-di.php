<?php

use Facile\JoseVerifier\JWK\JwksProviderBuilder;
use Facile\OpenIDClient\Client\ClientBuilder;
use Facile\OpenIDClient\Client\ClientInterface;
use Facile\OpenIDClient\Client\Metadata\ClientMetadata;
use Facile\OpenIDClient\Issuer\IssuerBuilder;
use Facile\OpenIDClient\Issuer\Metadata\Provider\MetadataProviderBuilder;
use Facile\OpenIDClient\Service\AuthorizationService;
use Facile\OpenIDClient\Service\Builder\AuthorizationServiceBuilder;
use Odan\Session\PhpSession;
use OWCSignicatOpenID\Block;
use OWCSignicatOpenID\Interfaces\Providers\AppServiceProviderInterface;
use OWCSignicatOpenID\Interfaces\Services\CacheServiceInterface;
use OWCSignicatOpenID\Interfaces\Services\LifeCycleServiceInterface;
use OWCSignicatOpenID\Interfaces\Services\OpenIDServiceInterface;
use OWCSignicatOpenID\Interfaces\Services\ResourceServiceInterface;
use OWCSignicatOpenID\Logger;
use OWCSignicatOpenID\Modal;
use OWCSignicatOpenID\Providers\AppServiceProvider;
use OWCSignicatOpenID\Screen;
use OWCSignicatOpenID\Services\CacheService;
use OWCSignicatOpenID\Services\LifeCycleService;
use OWCSignicatOpenID\Services\OpenIDService;
use OWCSignicatOpenID\Services\ResourceService;
use Psr\Log\LogLevel;

return array(
	'blocks.eherkenning'               => array(
		function ( $container ) {
				return new Block\eHerkenning(
					$container['hooks.oidc'],
					$container['session']
				);
		},
	),
	'blocks.eidas'                     => array(
		function ( $container ) {
			return new Block\eIDAS(
				$container['hooks.oidc'],
				$container['session']
			);
		},
	),
	'cache'                            => array(
		function (): CacheServiceInterface {
			return new CacheService();
		},
	),
	'hooks.oidc'                       => array(
		function ( $container ): OpenIDServiceInterface {
			return new OpenIDService(
				$container['oidc_client'],
				$container['oidc_service'],
				$container['logger'],
				$container['session']
			);
		},
	),
	'logger'                           => array(
		function ( $container ) {
			return new Logger( $container['logger.level'] );
		},
	),
	'logger.level'                     => array(
		function () {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				$level = LogLevel::WARNING;
			}

			return $level ?? '';
		},
	),
	'modal'                            => array(
		function ($container ) {
			return new Modal( $container['session'] );
		},
	),
	'oidc_client'                      => array(
		function ( $container ): ClientInterface {
			$configuration_url = get_option( 'owc_signicat_openid_configuration_url_settings' );
			$client_id         = get_option( 'owc_signicat_openid_client_id_settings' );
			$client_secret     = get_option( 'owc_signicat_openid_client_secret_settings' );
			$path_redirect     = get_option( 'owc_signicat_openid_path_redirect_settings' );

			if ( ! $configuration_url ) {
				$container['logger']->log( 'Configuration URL is empty', LogLevel::WARNING );
			}

			$cache = $container->get( 'cache' );

			// Cache configuration.
			$metadata_provider_builder = ( new MetadataProviderBuilder() )
			->setCache( $cache )
			->setCacheTtl( 86400 * 30 ); // Cache 30 days
			$jwks_provider_builder     = ( new JwksProviderBuilder() )
			->setCache( $cache )
			->setCacheTtl( 86400 ); // Cache 1 day
			$issuer_builder            = ( new IssuerBuilder() )
			->setMetadataProviderBuilder( $metadata_provider_builder )
			->setJwksProviderBuilder( $jwks_provider_builder );

			// Set issuer and build the client.
			$issuer          = $issuer_builder->build( $configuration_url );
			$client_metadata = ClientMetadata::fromArray(
				array(
					'client_id'     => sanitize_text_field( $client_id ),
					'client_secret' => sanitize_text_field( $client_secret ),
					'redirect_uris' => array(
						sanitize_text_field( wp_unslash( get_site_url() ) . '/' . $path_redirect ),
					),
				)
			);

			return ( new ClientBuilder() )
			->setIssuer( $issuer )
			->setClientMetadata( $client_metadata )
			->build();
		},
	),
	'oidc_service'                     => array(
		function (): AuthorizationService {
			return ( new AuthorizationServiceBuilder() )->build();
		},
	),
	'session'                          => array(
		function (): PhpSession {
			$session = new PhpSession();
			$session->setOptions(
				array(
					'name'            => 'OWC_Signicat_OpenID',
					'cookie_secure'   => true,
					'cookie_httponly' => true,
				)
			);
			$session->start();

			return $session;
		},
	),
	'screen.settings'                  => array(
		function () {
			return new Screen\Settings();
		},
	),

	// Providers.
	AppServiceProviderInterface::class => \DI\autowire( AppServiceProvider::class ),

	// Services.
	LifeCycleServiceInterface::class   => \DI\autowire( LifeCycleService::class ),
	ResourceServiceInterface::class    => \DI\autowire( ResourceService::class ),
);
