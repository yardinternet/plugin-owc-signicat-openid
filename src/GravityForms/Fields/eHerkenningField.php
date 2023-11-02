<?php
/**
 * GravityForms eHerkenningField.
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

use GF_Field;
use Aura\Session\Segment;
use OWCSignicatOpenID\GravityForms\Fields\eHerkenningLoginField;
use OWCSignicatOpenID\GravityForms\Fields\HiddenField;

use function OWCSignicatOpenID\Foundation\Helpers\config;
use function OWCSignicatOpenID\Foundation\Helpers\decrypt;
use function OWCSignicatOpenID\Foundation\Helpers\encrypt;
use function OWCSignicatOpenID\Foundation\Helpers\resolve;

/**
 * GravityForms eHerkenningField class.
 *
 * @since 0.0.1
 */
class eHerkenningField extends GF_Field
{
	/**
	 * @var string $type The field type.
	 */
	public $type = 'eherkenning';

	/** @var ?string */
	protected $bsn;

	/** @var Segment */
	protected $session;

	public function __construct($data = array() )
	{
		parent::__construct( $data );

		$this->session = resolve( 'session' )->getSegment( 'sopenid' );
	}

	/**
	 * Return the field title, for use in the form editor.
	 *
	 * @return string
	 */
	public function get_form_editor_field_title()
	{
		return esc_attr__( 'eHerkenning', config( 'core.text_domain' ) );
	}

	/**
	 * Returns the field button properties for the form editor. The array contains two elements:
	 * 'group' => 'standard_fields' // or  'advanced_fields', 'post_fields', 'pricing_fields'
	 * 'text'  => 'Button text'
	 *
	 * Built-in fields don't need to implement this because the buttons are added in sequence in GFFormDetail
	 *
	 * @return array
	 */
	public function get_form_editor_button()
	{
		return array(
			'group' => 'advanced_fields',
			'text'  => $this->get_form_editor_field_title(),
		);
	}

	/**
	 * Returns the class names of the settings which should be available on the field in the form editor.
	 *
	 * @return array
	 */
	public function get_form_editor_field_settings()
	{
		return array(
			'input_placeholders_setting',
			'rules_setting',
			'conditional_logic_field_setting',
			'rules_setting',
			'description_setting',
			'css_class_setting',
		);
	}

	/**
	 * This field type can be used when configuring conditional logic rules.
	 *
	 * @return bool
	 */
	public function is_conditional_logic_supported(): bool
	{
		return true;
	}

	/**
	 * Override this method to perform custom validation logic.
	 *
	 * Return the result (bool) by setting $this->failed_validation.
	 * Return the validation message (string) by setting $this->validation_message.
	 *
	 * @param string|array $value The field value from get_value_submission().
	 * @param array        $form  The Form Object currently being processed.
	 *
	 * @return void
	 */
	public function validate($value, $form )
	{
		$bsn = \rgget( $this->id . '.1', $value );

		if (\rgblank( $bsn )) {
			$this->failed_validation  = true;
			$this->validation_message = empty( $this->errorMessage ) ? \esc_html__( 'This field is required.', config( 'core.text_domain' ) ) : $this->errorMessage;
		}

		return $this;
	}

	/**
	 * Return all the fields available.
	 *
	 * @param array $value
	 * @return array
	 */
	protected function getFields(array $value ): array
	{
		$bsn = $this->session->get( 'bsn', '' );

		if ( ! empty( $bsn )) {
			$bsn = encrypt( $bsn );
		}

		return array(
			( new eHerkenningLoginField( $this, $value, $this->session ) )
				->setFieldID( 2 )
				->setFieldName( 'eherkenning' )
				->setFieldText( \__( 'eHerkenning', config( 'core.text_domain' ) ) ),
			( new HiddenField( $this, $value ) )
				->setFieldID( 1 )
				->setFieldName( 'bsn' )
				->setValue( $bsn ),
		);
	}

	/**
	 * Returns the field inner markup.
	 *
	 * @param array        $form  The Form Object currently being processed.
	 * @param string|array $value The field value. From default/dynamic population, $_POST, or a resumed incomplete submission.
	 * @param null|array   $entry Null or the Entry Object currently being edited.
	 *
	 * @return string
	 */
	public function get_field_input($form, $value = '', $entry = null )
	{
		$output = implode(
			' ',
			array_map(
				function ($item ) {
					return $item->render();
				},
				$this->getFields( $value )
			)
		);

		return "<div class=\"ginput_complex{$this->class_suffix} ginput_container ginput_container_digid\" id=\"input_{$form['id']}_{$this->id}\">
                    {$output}
                <div class=\"gf_clear gf_clear_complex\"></div>
            </div>";
	}

	/**
	 * Returns the field markup; including field label, description, validation, and the form editor admin buttons.
	 *
	 * The {FIELD} placeholder will be replaced in GFFormDisplay::get_field_content with the markup returned by GF_Field::get_field_input().
	 *
	 * @param string|array $value                The field value. From default/dynamic population, $_POST, or a resumed incomplete submission.
	 * @param bool         $force_frontend_label Should the frontend label be displayed in the admin even if an admin label is configured.
	 * @param array        $form                 The Form Object currently being processed.
	 *
	 * @return string
	 */
	public function get_field_content($value, $force_frontend_label, $form )
	{
		$field_label = $this->get_field_label( $force_frontend_label, $value );

		$validation_message_id = 'validation_message_' . $form['id'] . '_' . $this->id;
		$validation_message    = ( $this->failed_validation && ! empty( $this->validation_message ) ) ? sprintf( "<div id='%s' class='gfield_description validation_message' aria-live='polite'>%s</div>", $validation_message_id, $this->validation_message ) : '';

		$is_form_editor  = $this->is_form_editor();
		$is_entry_detail = $this->is_entry_detail();
		$is_admin        = $is_form_editor || $is_entry_detail;

		$required_div = $is_admin || $this->isRequired ? sprintf( "<span class='gfield_required'>%s</span>", $this->isRequired ? '*' : '' ) : '';

		$admin_buttons = $this->get_admin_buttons();

		$target_input_id = $this->get_first_input_id( $form );

		$for_attribute = empty( $target_input_id ) ? '' : "for='{$target_input_id}'";

		$description = $this->get_description( $this->description, 'gfield_description' );
		$bsn         = $this->session->get( 'bsn', '' );
		if ( ! empty( $bsn )) {
			$description = '';
		}
		if ($this->is_description_above( $form )) {
			$clear         = $is_admin ? "<div class='gf_clear'></div>" : '';
			$field_content = sprintf( "%s<label class='%s' $for_attribute >%s%s</label>%s{FIELD}%s$clear", $admin_buttons, esc_attr( $this->get_field_label_class() ), esc_html( $field_label ), $required_div, $description, $validation_message );
		} else {
			$field_content = sprintf( "%s<label class='%s' $for_attribute >%s%s</label>{FIELD}%s%s", $admin_buttons, esc_attr( $this->get_field_label_class() ), esc_html( $field_label ), $required_div, $description, $validation_message );
		}

		return $field_content;
	}

	/**
	 * Format the entry value for display on the entries list page.
	 * Return a value that's safe to display on the page.
	 */
	public function get_value_save_entry($value, $form, $input_name, $lead_id, $lead )
	{
		return empty( $value ) ? '' : decrypt( $value );
	}

	/**
	 * Format the entry value for display on the entries list page.
	 * Return a value that's safe to display on the page.
	 */
	public function get_value_entry_list($value, $entry, $field_id, $columns, $form )
	{
		// Escapes value so that it is safe to be displayed on the entry list page
		return esc_html( decrypt( $value ) );
	}

	/**
	 * Returns the scripts to be included for this field type in the form editor.
	 *
	 * @return string
	 */
	public function get_form_editor_inline_script_on_page_render()
	{
		// set the default field label for the field
		$script = sprintf(
			"function SetDefaultValues_%s(field) {
        field.label = '%s';
        field.inputs = [
            new Input(field.id + '.1', '%s'),
        ];
        }",
			$this->type,
			$this->get_form_editor_field_title(),
			'BSN'
		) . PHP_EOL;

		return $script;
	}

	/**
	 * Format the entry value for display on the entry detail page and for the {all_fields} merge tag.
	 *
	 * Return a value that's safe to display for the context of the given $format.
	 *
	 * @param string|array $value    The field value.
	 * @param string       $currency The entry currency code.
	 * @param bool|false   $use_text When processing choice based fields should the choice text be returned instead of the value.
	 * @param string       $format   The format requested for the location the merge is being used. Possible values: html, text or url.
	 * @param string       $media    The location where the value will be displayed. Possible values: screen or email.
	 *
	 * @return string
	 */
	public function get_value_entry_detail($value, $currency = '', $use_text = false, $format = 'html', $media = 'screen' )
	{
		if (is_array( $value )) {
			$return = decrypt( trim( rgget( $this->id . '.1', $value ) ) );
		} else {
			$return = '';
		}

		if ('html' === $format) {
			$return = esc_html( $return );
		}

		return $return;
	}

	/**
	 * Format the entry value before it is used in entry exports and by framework add-ons using GFAddOn::get_field_value().
	 */
	public function get_value_export($entry, $input_id = '', $use_text = false, $is_csv = false )
	{
		// Export doesn’t require encoding, but field data may require some manipulation or formatting before it is exported
		return decrypt( \rgar( $entry, $input_id ) );
	}
}
