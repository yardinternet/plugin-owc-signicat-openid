<?php

declare (strict_types=1);

namespace OWCSignicatOpenID\Services;

use OWC\IdpUserData\UserDataInterface;
use OWCSignicatOpenID\UserData\SpoofDigiDUserData;
use OWCSignicatOpenID\Interfaces\Services\IdentityProviderServiceInterface;

class SpoofService extends Service
{
    protected IdentityProviderServiceInterface $identityProviderService;

    public function __construct(
        IdentityProviderServiceInterface $identityProviderService
    ) {
        $this->identityProviderService = $identityProviderService;
    }

    public function register()
    {
        add_filter('owc_digid_is_logged_in', fn (bool $isLoggedIn): bool => true, 999);

        add_filter('owc_digid_userdata', function (?UserDataInterface $userData) {
            return new SpoofDigiDUserData([]);
        }, 999);
    }
}
