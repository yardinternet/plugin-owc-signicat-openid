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
		$this->cache = $cache;
		$this->settings = $settings;
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
		$enabledIdps = $this->cache->get(self::CACHE_KEY);
		if (is_array($enabledIdps)) {
			return $enabledIdps;
		}

		$configUrl = $this->settings->getSetting('configuration_url');

		if (null === $configUrl) {
			return [];
		}
		$host = wp_parse_url($configUrl, PHP_URL_HOST);

		$idpEndpoint = "https://$host/broker/idps";
		$response = wp_safe_remote_get($idpEndpoint, [
			'headers' => [
				'Accept' => 'application/json',
			],
		]);

		if (is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response)) {
			return [];
		}
		$body = wp_remote_retrieve_body($response);

		$enabledIdps = json_decode($body, true);
		if (! is_array($enabledIdps)) {
			return [];
		}

		$enabledIdps = wp_list_pluck($enabledIdps, 'internalName');

		$enabledIdps = array_filter(
			$this->idps,
			fn (IdentityProvider $idp): bool => in_array($idp->getSlug(), $enabledIdps, true)
		);
		$this->cache->set(self::CACHE_KEY, $enabledIdps, self::CACHE_TTL);

		return $enabledIdps;
	}

	public function getIdentityProvider(string $slug ): ?IdentityProvider
	{
		$activeIdps = $this->getEnabledIdentityProviders();

		return $activeIdps[ $slug ] ?? null;
	}
}
