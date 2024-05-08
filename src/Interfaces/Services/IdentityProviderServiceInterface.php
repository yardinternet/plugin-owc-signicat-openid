<?php

declare(strict_types=1);

namespace OWCSignicatOpenID\Interfaces\Services;

use OWCSignicatOpenID\IdentityProvider;

interface IdentityProviderServiceInterface extends ServiceInterface
{
    /** @var IdentityProvider[] */
    public function getEnabledIdentityProviders(): array;

    public function getIdentityProvider(string $slug): ?IdentityProvider;
}
