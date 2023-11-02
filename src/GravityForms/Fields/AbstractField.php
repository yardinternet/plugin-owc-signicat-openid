<?php
/**
 * GravityForms AbstractField.
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
 * GravityForms AbstractField class.
 *
 * @since 0.0.1
 */
abstract class AbstractField
{
	/** @var array form object */
	protected $form;

	/** @var object */
	protected $field;

	/** @var array Set default value of input. */
	protected $value;

	/** @var bool */
	protected $is_admin;

	/** @var bool */
	protected $is_sub_label_above;

	/** @var string */
	protected $sub_label_class_attribute = '';

	/** @var string */
	protected $field_sub_label_placement = '';

	/** @var string */
	protected $invalid_attribute = '';

	/** @var string */
	protected $disabled_text = '';

	/** @var string */
	protected $required_attribute = '';

	/** @var string */
	protected $style = '';

	/** @var string */
	protected $css_prefix = '';

	/** @var int ID of the field in the form. */
	protected $fieldID = null;

	/** @var string Position of the field in the form. */
	protected $fieldPosition = 'full';

	/** @var string Field text. */
	protected $fieldText = '';

	/** @var string Field identifier. */
	protected $fieldName;

	/** @var string Set input to readonly. */
	protected $readonly = '';

	/** @var string Add css class to input */
	protected $cssClass = '';

	/**
	 * @param object $field
	 * @param array  $value
	 */
	public function __construct(object $field, array $value )
	{
		$this->field                     = $field;
		$this->value                     = $value;
		$this->css_prefix                = $this->field->is_entry_detail ? "_admin" : "";
		$this->is_admin                  = $this->field->is_entry_detail || $this->field->is_form_editor;
		$this->style                     = ( $this->is_admin && \rgar( $this->getInput(), 'isHidden' ) ) ? "style='display:none;'" : '';
		$this->disabled_text             = $this->field->is_form_editor ? "disabled='disabled'" : '';
		$this->required_attribute        = $this->field->isRequired ? 'aria-required="true"' : '';
		$this->invalid_attribute         = $this->field->failed_validation ? 'aria-invalid="true"' : 'aria-invalid="false"';
		$this->field_sub_label_placement = $this->field->subLabelPlacement;
		$this->is_sub_label_above        = 'above' == $this->field_sub_label_placement || ( empty( $this->field_sub_label_placement ) && 'above' == \rgar( \GFAPI::get_form( $field->formId ), 'subLabelPlacement' ) );
		$this->sub_label_class_attribute = 'hidden_label' == $this->field_sub_label_placement ? "class='hidden_sub_label screen-reader-text'" : '';
	}

	/**
	 * Get the placeholder
	 *
	 * @return string
	 */
	public function getPlaceholder(): string
	{
		return \GFCommon::get_input_placeholder_attribute( $this->getInput() );
	}

	/**
	 * Get the label
	 *
	 * @return string
	 */
	public function getLabel(): string
	{
		return '' != \rgar( $this->getInput(), 'customLabel' ) ? $this->getInput()['customLabel'] : $this->fieldText;
	}

	/**
	 * Get the structured label of the field.
	 *
	 * @return string
	 */
	protected function getLabelField(): string
	{
		return "<label for='{$this->field->id}_{$this->fieldID}' id='{$this->field->id}_{$this->fieldID}_label' {$this->sub_label_class_attribute}>{$this->getLabel()}</label>";
	}

	/**
	 * Get the structuied span of the field.
	 *
	 * @return string
	 */
	protected function getSpanField(): string
	{
		$cssClass = implode(
			" ",
			array_filter(
				array(
					trim( "ginput_{$this->fieldPosition}" ),
					trim( $this->css_prefix ),
					trim( $this->cssClass ),
				)
			)
		);
		return "<span id=\"input_{$this->field->id}_{$this->field->formId}.{$this->fieldID}.container\" class=\"{$cssClass}\" {$this->style}>";
	}

	/**
	 * Get the submitted value
	 *
	 * @return string|array
	 */
	public function getValue(): ?string
	{
		if (is_array( $this->value )) {
			return \esc_attr( \rgget( $this->field->id . '.' . $this->field->id, $this->value ) );
		} else {
			return $this->value;
		}
	}

	/**
	 * Set the css of class
	 *
	 * @param string $cssClass
	 *
	 * @return  self
	 */
	public function setClass(string $cssClass ): self
	{
		$this->cssClass = $cssClass;
		return $this;
	}

	/**
	 * Set the value of field.
	 *
	 * @param string $value
	 *
	 * @return  self
	 */
	public function setFieldValue(string $value ): self
	{
		$this->value = $value;
		return $this;
	}

	/**
	 * Set the value to readonly.
	 *
	 * @return self
	 */
	public function setReadonly(): self
	{
		$this->readonly = 'readonly';
		return $this;
	}

	/**
	 * Set the value of fieldName
	 *
	 * @param string $fieldName
	 *
	 * @return  self
	 */
	public function setFieldName(string $fieldName ): self
	{
		$this->fieldName = $fieldName;
		return $this;
	}

	/**
	 * Set the value of fieldText
	 *
	 * @param string $fieldText
	 *
	 * @return  self
	 */
	public function setFieldText(string $fieldText ): self
	{
		$this->fieldText = $fieldText;
		return $this;
	}

	/**
	 * Set the value of fieldPosition
	 *
	 * @param string $fieldPosition
	 *
	 * @return  self
	 */
	public function setFieldPosition(string $fieldPosition ): self
	{
		$this->fieldPosition = $fieldPosition;
		return $this;
	}

	/**
	 * Set the value of fieldID
	 *
	 * @param int $fieldID
	 *
	 * @return  self
	 */
	public function setFieldID(int $fieldID ): self
	{
		$this->fieldID = $fieldID;
		return $this;
	}

	/**
	 * Set the value of the input.
	 *
	 * @param string $value
	 *
	 * @return self
	 */
	public function setValue(?string $value ): self
	{
		$this->value = $value;
		return $this;
	}

	/**
	 * Return the input object.
	 *
	 * @return array|null
	 */
	public function getInput()
	{
		return \GFFormsModel::get_input( $this->field, $this->field->id . '.' . $this->field->id );
	}
}
