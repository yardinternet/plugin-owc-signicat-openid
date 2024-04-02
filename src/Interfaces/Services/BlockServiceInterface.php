<?php

declare(strict_types=1);
/**
 * Block service interface.
 *
 * @package OWC_Signicat_OpenID
 *
 * @author  Yard | Digital Agency
 *
 * @since   0.0.1
 */

namespace OWCSignicatOpenID\Interfaces\Services;

/**
 * Exit when accessed directly.
 */
if (! defined('ABSPATH')) {
    exit;
}


/**
 * Cache service interface.
 *
 * @since 0.0.1
 */
interface BlockServiceInterface extends ServiceInterface
{

    public function addBlock();
}
