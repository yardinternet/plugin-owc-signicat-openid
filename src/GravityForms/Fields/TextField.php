<?php
/**
 * GravityForms TextField.
 *
 * @package OWC_Signicat_OpenID
 * @author  Yard | Digital Agency
 * @since   0.0.1
 */

declare ( strict_types = 1 );

namespace OWCSignicatOpenID\GravityForms\Fields;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' )) {
	exit;
}

/**
 * GravityForms TextField class.
 *
 * @since 0.0.1
 */
class TextField extends AbstractField
{
	public function __construct(object $field, array $value )
	{
		parent::__construct( $field, $value );
	}

	/**
	 * Get the structured input.
	 */
	protected function getInputField(): string
	{
		return view( 'admin/no-certificates.php' );
	}

	/**
	 * Render the input.
	 */
	public function render(): string
	{
		if ($this->is_admin || ! \rgar( $this->getInput(), 'isHidden' )) {
			return "{$this->getSpanField()}
                        {$this->getInputField()}
                    </span>";
		}
	}
}
