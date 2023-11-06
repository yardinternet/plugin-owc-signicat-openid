<?php
/**
 * Service Provider class.
 *
 * @package OWC_Signicat_OpenID
 * @author  Yard | Digital Agency
 * @since   0.0.1
 */

declare ( strict_types = 1 );

namespace OWCSignicatOpenID;

use Facile\JoseVerifier\JWK\JwksProviderBuilder;
use Facile\OpenIDClient\Client\ClientInterface;
use Facile\OpenIDClient\Client\ClientBuilder;
use Facile\OpenIDClient\Client\Metadata\ClientMetadata;
use Facile\OpenIDClient\Issuer\Metadata\Provider\MetadataProviderBuilder;
use Facile\OpenIDClient\Issuer\IssuerBuilder;
use Facile\OpenIDClient\Service\AuthorizationService;
use Facile\OpenIDClient\Service\Builder\AuthorizationServiceBuilder;
use Odan\Session\PhpSession;
use Pimple\Container as PimpleContainer;
use Pimple\ServiceProviderInterface;
use Psr\Log\LogLevel;
use Psr\SimpleCache\CacheInterface;

use OWCSignicatOpenID\Block;
use OWCSignicatOpenID\Logger;
use OWCSignicatOpenID\Provider;
use OWCSignicatOpenID\Screen;

/**
 * Plugin service provider class.
 *
 * @since 0.0.1
 */
class ServiceProvider implements ServiceProviderInterface
{
	/**
	 * Register services.
	 *
	 * @param PimpleContainer $container Container instance.
	 */
	public function register( PimpleContainer $container ) {
		$container['blocks.eherkenning'] = function ( $container ) {
			return new Block\eHerkenning(
				$container['hooks.oidc'],
				$container['session']
			);
		};

		$container['blocks.eidas'] = function ( $container ) {
			return new Block\eIDAS(
				$container['hooks.oidc'],
				$container['session']
			);
		};

		$container['cache'] = function (): CacheInterface {
			return new Cache();
		};

		$container['hooks.activation'] = function () {
			return new Provider\Activation();
		};

		$container['hooks.deactivation'] = function () {
			return new Provider\Deactivation();
		};

		$container['hooks.oidc'] = function ( $container ) {
			return new Provider\OpenID(
				$container['oidc_client'],
				$container['oidc_service'],
				$container['logger'],
				$container['session']
			);
		};

		$container['hooks.uninstall'] = function () {
			return new Provider\Uninstall();
		};

		$container['logger'] = function ( $container ) {
			return new Logger( $container['logger.level'] );
		};

		$container['logger.level'] = function () {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				$level = LogLevel::WARNING;
			}

			return $level ?? '';
		};

		$container['oidc_client'] = function ( $container ): ClientInterface {
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
				->setCacheTtl( 86400 * 30 ); // Cache metadata for 30 days
			$jwks_provider_builder     = ( new JwksProviderBuilder() )
				->setCache( $cache )
				->setCacheTtl( 86400 ); // Cache JWKS for 1 day
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
		};

		$container['oidc_service'] = function (): AuthorizationService {
			return ( new AuthorizationServiceBuilder() )->build();
		};

		$container['session'] = function (): PhpSession {
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
		};

		$container['screen.settings'] = function () {
			return new Screen\Settings();
		};
	}
}
