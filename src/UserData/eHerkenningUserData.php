<?php

declare(strict_types=1);

namespace OWCSignicatOpenID\UserData;

use OWC\IdpUserData\eHerkenningUserDataInterface;

class eHerkenningUserData extends UserData implements eHerkenningUserDataInterface
{
	protected string $kvk              = '';
	protected string $vestigingsNummer = '';
	protected string $rsin             = '';

	public function getKvk(): string
	{
		return $this->kvk;
	}

	public function getVestigingsNummer(): string
	{
		return $this->vestigingsNummer;
	}

	public function getRsin(): string
	{
		return $this->rsin;
	}
}
