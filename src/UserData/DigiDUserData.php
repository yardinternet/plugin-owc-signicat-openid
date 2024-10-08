<?php

declare(strict_types=1);

namespace OWCSignicatOpenID\UserData;

use OWC\IdpUserData\DigiDUserDataInterface;

class DigiDUserData extends UserData implements DigiDUserDataInterface
{
    protected string $sub;

    public function getBsn(): string
    {
        return $this->sub;
    }
}
