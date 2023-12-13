<?php
/**
 * Cache service interface.
 *
 * @package OWC_Signicat_OpenID
 * @author  Yard | Digital Agency
 * @since   0.0.1
 */

namespace OWCSignicatOpenID\Interfaces\Services;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' )) {
	exit;
}

use Psr\SimpleCache\CacheInterface;

/**
 * Cache service interface.
 *
 * @since 0.0.1
 */
interface CacheServiceInterface extends CacheInterface
{
}
