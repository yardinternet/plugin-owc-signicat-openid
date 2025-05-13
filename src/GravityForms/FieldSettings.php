<?php

declare(strict_types=1);

namespace OWCSignicatOpenID\GravityForms;

use OWCSignicatOpenID\ContainerManager;
use OWCSignicatOpenID\Services\ViewService;

class FieldSettings
{
    public function addFieldSettings($position, $formId): void
    {
        if (! class_exists('GFAPI') || 0 !== $position) {
            return;
        }

        echo ContainerManager::getContainer()->get(ViewService::class)->render('scope-select');
    }

    public function addFieldSettingsSelectScript(): void
    {
        echo ContainerManager::getContainer()->get(ViewService::class)->render('scope-select-script');
    }
}
