<?php

declare(strict_types=1);

namespace OWCSignicatOpenID\Interfaces\Services;

use OWCSignicatOpenID\IdentityProvider;

interface IdentityProviderServiceInterface extends ServiceInterface
{
    /** @var IdentityProvider[] */
    public function getActiveIdentityProviders(): array;

    public function getActiveIdentityProvider(string $slug): ?IdentityProvider;
}
