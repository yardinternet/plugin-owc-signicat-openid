<?php

namespace OWCSignicatOpenID;

use JsonSerializable;

class IdentityProvider implements JsonSerializable
{
    protected string $slug;
    protected string $name;

    public function __construct(string $slug, string $name)
    {
        $this->slug = $slug;
        $this->name = $name;
    }

    public function jsonSerialize()
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
        ];
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getScope(): string
    {
        return sprintf('idp_scoping:%s', $this->slug);
    }

    public function getLogoUrl(): string
    {
        return OWC_SIGNICAT_OPENID_PLUGIN_URL . sprintf('resources/img/logo-%s.svg', $this->getSlug());
    }
}
