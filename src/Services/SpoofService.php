<?php

declare (strict_types=1);

namespace OWCSignicatOpenID\Services;

use OWC\IdpUserData\UserDataInterface;
use OWCSignicatOpenID\UserData\SpoofDigiDUserData;
use OWCSignicatOpenID\Services\SpoofSettingsService;

class SpoofService extends Service
{
    protected SpoofSettingsService $settings;

    public function __construct(
        SpoofSettingsService $settings
    ) {
        $this->settings = $settings;
    }

    public function register()
    {
        if ($this->settings->getSetting('enable_simulator') !== '1') {
            return;
        }

        $bsn = $this->settings->getSetting('bsn');
        $levelOfAssurance = $this->settings->getSetting('levelOfAssurance');

        if (! is_numeric($bsn)) {
            return;
        }

        add_filter('owc_digid_is_logged_in', fn (bool $isLoggedIn): bool => true, 999);

        add_filter('owc_digid_userdata', function (?UserDataInterface $userData) use ($bsn, $levelOfAssurance) {
            return new SpoofDigiDUserData(compact('bsn', 'levelOfAssurance'));
        }, 999);
    }
}
