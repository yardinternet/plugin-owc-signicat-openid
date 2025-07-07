<?php
/**
 * Service interface.
 *
 * @package OWC_Signicat_OpenID
 *
 * @author  Yard | Digital Agency
 *
 * @since   0.0.1
 */

namespace OWCSignicatOpenID\Interfaces\Services;

interface ServiceInterface
{
	public function register();
	public function boot();
}
