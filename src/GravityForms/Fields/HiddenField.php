<?php
/**
 * GravityForms HiddenField.
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
 * GravityForms HiddenField class.
 *
 * @since 0.0.1
 */
class HiddenField extends AbstractField
{
	/**
	 * Render the input.
	 *
	 * @return string
	 */
	public function render(): string
	{
		if ($this->is_admin || ! \rgar( $this->getInput(), 'isHidden' )) {
			if ($this->is_sub_label_above) {
				return "{$this->getSpanField()}
						{$this->getLabelField()}
                        {$this->getInputField()}
                    </span>";
			} else {
				return "{$this->getSpanField()}
                        {$this->getInputField()}
						{$this->getLabelField()}
                    </span>";
			}
		} else {
			return '';
		}
	}

	/**
	 * Get the structured input.
	 *
	 * @return string
	 */
	protected function getInputField(): string
	{
		return "<input
                    type='hidden'
                    name='input_{$this->field->id}.{$this->fieldID}'
                    id='input_{$this->field->id}_{$this->field->formId}_{$this->fieldID}'
                    value='{$this->getValue()}'
                />";
	}
}
