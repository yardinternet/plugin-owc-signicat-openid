<?php

declare(strict_types=1);

namespace OWCSignicatOpenID\Services;

use Facile\OpenIDClient\Client\ClientInterface;
use OWCSignicatOpenID\IdentityProvider;
use OWCSignicatOpenID\Interfaces\Services\IdentityProviderServiceInterface;

class IdentityProviderService extends Service implements IdentityProviderServiceInterface
{
    protected ClientInterface $client;

    /** @var IdentityProvider[] */
    protected array $idps;

    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    public function setIdps(array $idps)
    {
        foreach ($idps as $idp) {
            $this->idps[] = new IdentityProvider($idp['slug'], $idp['name']);
        }
    }

    /**
     * @return IdentityProvider[]
     */
    public function getActiveIdentityProviders(): array
    {
        $scopes = $this->client->getIssuer()->getMetadata()->getScopesSupported();
        $idps = array_filter(
            $this->idps,
            fn (IdentityProvider $idp): bool => in_array($idp->getScope(), $scopes, true)
        );

        return $idps;
    }

    public function getActiveIdentityProvider(string $slug): ?IdentityProvider
    {
        $activeIdps = $this->getActiveIdentityProviders();
        $filteredIdps = array_filter(
            $activeIdps,
            fn (IdentityProvider $idp): bool => $idp->getSlug() === $slug
        );
        if (count($filteredIdps) !== 1) {
            return null;
        }

        return reset($filteredIdps);
    }

}
