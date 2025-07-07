<?php

declare(strict_types=1);

namespace OWCSignicatOpenID\Interfaces\Services;

interface SettingsServiceInterface extends ServiceInterface
{
	public function getSetting(string $setting );
}
