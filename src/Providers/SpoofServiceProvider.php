<?php

namespace OWCSignicatOpenID\Providers;

use OWCSignicatOpenID\Services\SpoofService;
use OWCSignicatOpenID\Services\SpoofSettingsService;

class SpoofServiceProvider extends ServiceProvider
{
    public function __construct(
        SpoofSettingsService $settingsService,
        SpoofService $spoofService
    ) {
        $this->services = [
            'settings'   => $settingsService,
            'spoof' => $spoofService
        ];
    }
}
