<?php

declare(strict_types=1);

namespace OWCSignicatOpenID\Services;

use OWCSignicatOpenID\GravityForms\FieldSettings;
use OWCSignicatOpenID\GravityForms\Fields\OpenIDField;
use OWCSignicatOpenID\Interfaces\Services\GravityFormsServiceInterface;
use OWCSignicatOpenID\Interfaces\Services\IdentityProviderServiceInterface;
use OWCSignicatOpenID\Interfaces\Services\OpenIDServiceInterface;
use OWCSignicatOpenID\Interfaces\Services\SettingsServiceInterface;

class GravityFormsService extends Service implements GravityFormsServiceInterface
{
	private const FIELD_GROUP = 'owc-signicat-openid';

	protected OpenIDServiceInterface $openIDService;
	protected SettingsServiceInterface $settings;
	protected IdentityProviderServiceInterface $idpService;

	public function __construct(
		OpenIDServiceInterface $openIDService,
		SettingsServiceInterface $settings,
		IdentityProviderServiceInterface $idpService
	) {
		$this->openIDService = $openIDService;
		$this->settings      = $settings;
		$this->idpService    = $idpService;
	}

	public function register()
	{
		add_action( 'gform_loaded', array( $this, 'registerFields' ) );
		add_filter( 'gform_gf_field_create', array( $this, 'setOpenIDService' ), 10, 2 );
		add_filter( 'gform_incomplete_submission_pre_save', array( $this, 'setPageNumber' ), 10, 3 );
		add_action( 'gform_editor_js_set_default_values', array( $this, 'setDefaults' ) );
		add_filter( 'gform_field_groups_form_editor', array( $this, 'addFieldGroup' ) );
		add_filter( 'gform_get_input_value', array( $this, 'decrypt' ), 10, 4 );
		add_filter( 'gform_save_field_value', array( $this, 'encrypt' ), 10, 5 );
		add_action( 'gform_field_standard_settings', array( new FieldSettings(), 'addFieldSettings' ), 10, 2 );
		add_action( 'gform_editor_js', array( new FieldSettings(), 'addFieldSettingsSelectScript' ), 10, 2 );
	}

	public function decrypt(string $value, array $entry, \GF_Field $field, $input_id ): string
	{
		if ( ! is_a( $field, OpenIDField::class ) || empty( $value )) {
			return $value;
		}

		// TODO: optie/filter om decryption te onderdrukken
		return \GFCommon::openssl_decrypt( $value ) ?: $value;
	}

	public function encrypt($value, $entry, $field, $form, $input_id )
	{
		if ( ! is_a( $field, OpenIDField::class ) || empty( $value )) {
			return $value;
		}

		return \GFCommon::openssl_encrypt( $value );
	}

	public function setDefaults()
	{
		foreach ($this->idpService->getEnabledIdentityProviders() as $idp) {
			?>
			case "<?php printf( 'owc-signicat-openid-%s', $idp->getSlug() ); ?>":
				field.label = "<?php echo $idp->getName(); ?>";
				field.idpSlug = "<?php echo $idp->getSlug(); ?>";
				break;
			<?php
		}
	}

	public function registerFields()
	{
		$services = array(
			'openIdService' => $this->openIDService,
		);

		foreach ($this->idpService->getEnabledIdentityProviders() as $idp) {
			$data = array( 'idp' => $idp ) + $services;
			\GF_Fields::register( new OpenIDField( $data ) );
		}
	}

	public function setOpenIDService(\GF_Field $field, $properties ): \GF_Field
	{
		if ( ! is_a( $field, OpenIDField::class )) {
			return $field;
		}
		$field->__set( 'openIDService', $this->openIDService );
		if ( ! $field->__isset( 'idp' ) && $field->__isset( 'idpSlug' )) {
			$idpSlug = $field->__get( 'idpSlug' );
			$idp     = $this->idpService->getIdentityProvider( $idpSlug );
			if (null !== $idp) {
				$field->__set( 'idp', $idp );
			}
		}

		return $field;
	}

	public function setPageNumber(string $submission_json, string $resume_token, array $form ): string
	{
		$submissionData              = \json_decode( $submission_json );
		$submissionData->page_number = \GFFormDisplay::get_current_page( $form['id'] );

		return \json_encode( $submissionData );
	}

	public function addFieldGroup(array $fieldGroups ): array
	{
		$fields = array();
		foreach (array_keys( $this->idpService->getEnabledIdentityProviders() ) as $idpSlug) {
			$fieldType = sprintf( 'owc-signicat-openid-%s', $idpSlug );
			$fields[]  = array(
				'data-type' => $fieldType,
				'value'     => \GFCommon::get_field_type_title( $fieldType ),
			);
		}

		$fieldGroups[] = array(
			'name'   => self::FIELD_GROUP,
			'label'  => __( 'Signicat OpenID', 'owc' ),
			'fields' => $fields,
		);

		return $fieldGroups;
	}
}
