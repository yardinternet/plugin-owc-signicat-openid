<?php

declare(strict_types=1);

namespace OWCSignicatOpenID\UserData;

use OWC\IdpUserData\eHerkenningUserDataInterface;

class eHerkenningUserData extends UserData implements eHerkenningUserDataInterface
{
    protected string $kvk;

    public function getKvk(): string
    {
        return $this->kvk;
    }
}
