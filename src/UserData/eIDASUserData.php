<?php

declare(strict_types=1);

namespace OWCSignicatOpenID\UserData;

use OWC\IdpUserData\eIDASUserDataInterface;

class eIDASUserData extends UserData implements eIDASUserDataInterface
{
	protected ?string $nin              = null;
	protected ?string $sub              = null;
	protected ?string $givenName        = null;
	protected ?string $familyName       = null;
	protected ?string $birthdate        = null;
	protected ?string $personIdentifier = null;

	/**
	 * Returns the BSN when the broker was able to resolve the eIDAS identifier
	 * to a Dutch citizen service number. Falls back to the `sub` claim for
	 * legacy flows. Returns an empty string when no BSN is available.
	 */
	public function getBsn(): string
	{
		return $this->nin ?? ( $this->sub ?? '' );
	}

	public function getGivenName(): string
	{
		return $this->givenName ?? '';
	}

	public function getFamilyName(): string
	{
		return $this->familyName ?? '';
	}

	public function getBirthdate(): string
	{
		return $this->birthdate ?? '';
	}

	/**
	 * Returns the eIDAS PersonIdentifier — the cross-border unique identifier
	 * assigned by the citizen's home country.
	 */
	public function getPersonIdentifier(): string
	{
		return $this->personIdentifier ?? '';
	}
}
