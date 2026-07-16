<?php

declare(strict_types=1);

namespace OWCSignicatOpenID\Services;

use OWCSignicatOpenID\IdentityProvider;
use OWCSignicatOpenID\Interfaces\Services\CacheServiceInterface;
use OWCSignicatOpenID\Interfaces\Services\IdentityProviderServiceInterface;
use OWCSignicatOpenID\Interfaces\Services\SettingsServiceInterface;

class IdentityProviderService extends Service implements IdentityProviderServiceInterface
{
	private const CACHE_KEY = 'owc_signicat_openid_idps';
	private const CACHE_TTL = HOUR_IN_SECONDS;

	protected CacheServiceInterface $cache;

	protected SettingsServiceInterface $settings;

	/** @var IdentityProvider[] */
	protected array $idps;

	public function __construct(CacheServiceInterface $cache, SettingsServiceInterface $settings )
	{
		$this->cache    = $cache;
		$this->settings = $settings;
	}

	/**
	 * The enabled-IDPs cache is derived from the broker's IDP list and the
	 * service_index_* settings, so it must be invalidated whenever either
	 * could have changed. Otherwise a newly configured service (e.g. eIDAS)
	 * stays hidden from the form editor until the cache expires on its own.
	 */
	public function register(): void
	{
		add_action( 'updated_option', $this->maybeClearCache( ... ) );
	}

	public function maybeClearCache(string $option ): void
	{
		if ('owc_signicat_openid_configuration_url_settings' === $option || str_starts_with( $option, 'owc_signicat_openid_service_index_' )) {
			$this->cache->delete( self::CACHE_KEY );
		}
	}

	public function setIdps(array $idps ): void
	{
		foreach ($idps as $idp) {
			$this->idps[ $idp['slug'] ] = new IdentityProvider( $idp );
		}
	}

	/**
	 * @return IdentityProvider[]
	 */
	public function getEnabledIdentityProviders(): array
	{
		$enabledIdps = $this->cache->get( self::CACHE_KEY );

		if (is_array( $enabledIdps )) {
			return $enabledIdps;
		}

		$enabledIdps = $this->fetchBrokerIDPs();

		if (array() === $enabledIdps) {
			return array();
		}

		$enabledIdps = wp_list_pluck( $enabledIdps, 'internalName' );

		$enabledIdps = array_filter(
			$this->idps,
			function (IdentityProvider $idp ) use ( $enabledIdps ): bool {
				if ( ! in_array( $idp->getBrokerSlug(), $enabledIdps, true )) {
					return false;
				}

				// IDPs that piggyback on another broker IDP (e.g. eIDAS on eHerkenning)
				// are only usable once their catalogue service index is configured,
				// otherwise they can't be distinguished from the underlying broker IDP.
				if ($idp->getBrokerSlug() !== $idp->getSlug()) {
					$serviceIndex = $this->settings->getSetting( 'service_index_' . $idp->getSlug() );
					$serviceIndex = is_string( $serviceIndex ) ? trim( $serviceIndex ) : '';

					return '' !== $serviceIndex;
				}

				return true;
			}
		);

		if (count( $enabledIdps ) === 0) {
			return array();
		}

		$this->cache->set( self::CACHE_KEY, $enabledIdps, self::CACHE_TTL );

		return $enabledIdps;
	}

	/**
	 * Fetch the list of enabled IDPs from the Signicat broker API endpoint.
	 *
	 * @return array<int, array{displayName: string, internalName: string, icon: string}>
	 */
	protected function fetchBrokerIDPs(): array
	{
		$configUrl = $this->settings->getSetting( 'configuration_url' );

		if (null === $configUrl) {
			return array();
		}

		$host = wp_parse_url( $configUrl, PHP_URL_HOST );

		if ( ! is_string( $host ) || '' === $host) {
			return array();
		}

		$idpEndpoint = "https://$host/broker/idps";
		$response    = wp_safe_remote_get(
			$idpEndpoint,
			array(
				'headers' => array(
					'Accept' => 'application/json',
				),
			)
		);

		if (is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response )) {
			return array();
		}

		$body = wp_remote_retrieve_body( $response );

		$enabledIdps = json_decode( $body, true );

		return is_array( $enabledIdps ) ? $enabledIdps : array();
	}

	public function getIdentityProvider(string $slug ): ?IdentityProvider
	{
		$activeIdps = $this->getEnabledIdentityProviders();

		return $activeIdps[ $slug ] ?? null;
	}
}
