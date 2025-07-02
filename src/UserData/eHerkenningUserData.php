<?php

declare(strict_types=1);

namespace OWCSignicatOpenID\UserData;

use OWC\IdpUserData\eHerkenningUserDataInterface;

class eHerkenningUserData extends UserData implements eHerkenningUserDataInterface
{
    protected string $kvk;

    protected ?string $rsin = null;

    protected ?string $bsn = null;

    protected ?string $vestigingsNummer = null;

    public function getKvk(): string
    {
        return $this->kvk;
    }

    public function getBsn(): ?string
    {
        return $this->bsn;
    }

    public function getVestigingsNummer(): ?string
    {
        return $this->vestigingsNummer;
    }

    public function getRsin(): ?string
    {
        return $this->rsin;
    }
}
