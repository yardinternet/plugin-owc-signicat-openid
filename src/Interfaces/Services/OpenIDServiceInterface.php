<?php
/**
 * OpenID service interface.
 *
 * @package OWC_Signicat_OpenID
 *
 * @author  Yard | Digital Agency
 *
 * @since   0.0.1
 */

namespace OWCSignicatOpenID\Interfaces\Services;

use OWCSignicatOpenID\IdentityProvider;
use Psr\Http\Message\ServerRequestInterface;

interface OpenIDServiceInterface extends ServiceInterface
{
    public function getUserInfo(IdentityProvider $identityProvider): array;

    public function revoke(IdentityProvider $identityProvider): void;

    public function getLoginUrl(IdentityProvider $identityProvider, string $redirectUrl = null, string $refererUrl = null): string;

    public function getLogoutUrl(IdentityProvider $identityProvider = null, string $redirectUrl = null, string $refererUrl = null): string;

    public function authenticate(IdentityProvider $identityProvider, string $redirectUrl, string $refererUrl = null);

    public function redirectToLogout(IdentityProvider $identityProvider, string $redirectUrl, string $refererUrl = null);

    public function refresh(IdentityProvider $identityProvider);

    public function introspect(IdentityProvider $identityProvider): array;

    public function handleCallback(ServerRequestInterface $server_request): void;

    public function hasActiveSession(IdentityProvider $identityProvider): bool;
}
