<?php

/**
 * OpenID service interface.
 *
 * @package OWC_Signicat_OpenID
 * @author  Yard | Digital Agency
 * @since   0.0.1
 */

namespace OWCSignicatOpenID\Interfaces\Services;

use OWCSignicatOpenID\IdentityProvider;
use Psr\Http\Message\ServerRequestInterface;

interface OpenIDServiceInterface extends ServiceInterface
{
	public function getScopesSupported(): ?array;

	public function getEnabledIdentityProviders(): array;

	public function getUserInfo(IdentityProvider $identityProvider, string $slot = '' ): array;

	public function revoke(IdentityProvider $identityProvider, string $slot = '' ): string;

	public function getLoginUrl(IdentityProvider $identityProvider, ?string $redirectUrl = null, ?string $refererUrl = null, array $selectedIdpScopes = array(), string $slot = '' ): string;

	public function getLogoutUrl(?IdentityProvider $identityProvider = null, ?string $redirectUrl = null, ?string $refererUrl = null ): string;

	public function authenticate(IdentityProvider $identityProvider, string $redirectUrl, ?string $refererUrl = null, string $slot = '' );

	public function isLegacyImplementation(): bool;

	public function redirectToLogout(IdentityProvider $identityProvider, string $redirectUrl, ?string $refererUrl = null );

	public function flashErrorsByIdp(string $idp): array;

	public function refresh(IdentityProvider $identityProvider, string $slot = '' );

	public function introspect(IdentityProvider $identityProvider, string $slot = '' ): array;

	public function handleCallback(ServerRequestInterface $server_request ): void;

	public function hasActiveSession(IdentityProvider $identityProvider, string $slot = '' ): bool;

	public function flashErrors(): array;
}
