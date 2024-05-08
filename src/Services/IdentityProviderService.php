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
            $this->idps[$idp['slug']] = new IdentityProvider($idp);
        }
    }

    /**
     * @return IdentityProvider[]
     */
    public function getEnabledIdentityProviders(): array
    {
        $scopes = $this->client->getIssuer()->getMetadata()->getScopesSupported();
        $idps = array_filter(
            $this->idps,
            fn (IdentityProvider $idp): bool => in_array($idp->getScope(), $scopes, true)
        );

        return $idps;
    }

    public function getIdentityProvider(string $slug): ?IdentityProvider
    {
        $activeIdps = $this->getEnabledIdentityProviders();

        return $activeIdps[$slug] ?? null;
    }
}
