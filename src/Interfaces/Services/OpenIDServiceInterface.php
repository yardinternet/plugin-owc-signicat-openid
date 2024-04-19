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

use Psr\Http\Message\ServerRequestInterface;

interface OpenIDServiceInterface extends ServiceInterface
{
    public function get_user_info(): array;

    public function logout(): void;

    public function authenticate(array $idpScopes = [], string $redirectUrl);

    public function refresh(): void;

    public function introspect(): array;

    public function handle_redirect(ServerRequestInterface $server_request): void;
}
