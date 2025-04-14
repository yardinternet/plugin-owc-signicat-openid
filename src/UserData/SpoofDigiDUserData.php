<?php

declare(strict_types=1);

namespace OWCSignicatOpenID\UserData;

class SpoofDigiDUserData extends DigiDUserData
{
    public function getBsn(): string
    {
        $this->bsn = '359981744';
        $this->levelOfAssurance = 'over 9000';

        return '359981744';
    }
}
