<?php
/**
 * Composable interface
 *
 * @package OWC_Signicat_OpenID
 * @author  Yard | Digital Agency
 * @since   0.0.1
 */

declare ( strict_types = 1 );

namespace OWCSignicatOpenID;

/**
 * Segregated interface of something that should be composed.
 */
interface Composable
{
	/**
	 * Compose the object graph.
	 *
	 * @since 0.0.1
	 */
	public function compose();
}
