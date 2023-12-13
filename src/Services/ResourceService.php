<?php
/**
 * Register resource service.
 *
 * @package OWC_Signicat_OpenID
 * @author  Yard | Digital Agency
 * @since   0.0.1
 */

namespace OWCSignicatOpenID\Services;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' )) {
	exit;
}

use OWCSignicatOpenID\Interfaces\Services\ResourceServiceInterface;

/**
 * Register resource service.
 *
 * @since 1.2.0
 */
class ResourceService extends Service implements ResourceServiceInterface
{
	/**
	 * @inheritDoc
	 */
	public function register()
	{
	}
}
