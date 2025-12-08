<?php

declare(strict_types=1);

namespace OWCSignicatOpenID\UserData;

use OWC\IdpUserData\DigiDUserDataInterface;

class DigiDUserData extends UserData implements DigiDUserDataInterface
{
	protected ?string $nin;
	protected ?string $sub;

	/**
	 * Prefers the NIN claim, which is the only valid and compliant source for BSN
	 * in modern DigiD / NL eID implementations. Older identity flows sometimes
	 * encoded the BSN in the `sub` claim, which is no longer permitted. The `sub`
	 * value is therefore used only as a legacy fallback for outdated integrations.
	 */
	public function getBsn(): string
	{
		return $this->nin ?? ( $this->sub ?? '' );
	}
}
