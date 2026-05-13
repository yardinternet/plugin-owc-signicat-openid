<?php

declare(strict_types=1);

namespace OWCSignicatOpenID\Services;

use OWCSignicatOpenID\GravityForms\Fields\OpenIDField;
use OWCSignicatOpenID\GravityForms\FieldSettings;
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
		$this->registerFields();
		add_filter( 'gform_gf_field_create', $this->setOpenIDService( ... ), 10, 2 );
		add_filter( 'gform_incomplete_submission_pre_save', $this->setPageNumber( ... ), 10, 3 );
		add_filter( 'gform_pre_process', $this->preserveSingleFileUploadsOnBackwardNavigation( ... ) );
		add_action( 'gform_editor_js_set_default_values', $this->setDefaults( ... ) );
		add_filter( 'gform_field_groups_form_editor', $this->addFieldGroup( ... ) );
		add_filter( 'gform_get_input_value', $this->decrypt( ... ), 10, 4 );
		add_filter( 'gform_save_field_value', $this->encrypt( ... ), 10, 5 );
		add_action( 'gform_field_standard_settings', ( new FieldSettings() )->addFieldSettings( ... ), 10, 2 );
		add_action( 'gform_editor_js', ( new FieldSettings() )->addFieldSettingsSelectScript( ... ), 10, 2 );
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
				field.openIdIsSecondLogin = false;
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

	/**
	 * GF skips $_FILES processing during backward page navigation (target < source), losing single-file
	 * uploads when users navigate back to the OpenID page for SSO authentication. Fires before
	 * set_uploaded_files() in process_form(), moves pending files to temp storage, and writes the result —
	 * merged with files already tracked from other pages — into $_POST['gform_uploaded_files'] so the
	 * hidden input is rendered and uploads survive subsequent navigations.
	 *
	 * @since NEXT
	 */
	public function preserveSingleFileUploadsOnBackwardNavigation(array $form): array
	{
		$formId     = $form['id'];
		$sourcePage = (int) rgpost('gform_source_page_number_' . $formId);
		$targetPage = (int) rgpost('gform_target_page_number_' . $formId);

		if ($targetPage === 0 || $targetPage >= $sourcePage || empty($_FILES)) {
			return $form;
		}

		$sourceFields = \GFFormDisplay::get_fields_by_page($form, $sourcePage);

		if (! is_array($sourceFields) || [] === $sourceFields) {
			return $form;
		}

		$singleFileFields  = array_filter(
			$sourceFields,
			fn($field) => $field instanceof \GF_Field_FileUpload && ! $field->multipleFiles
		);

		if ([] === $singleFileFields) {
			return $form;
		}

		// GFFormDisplay::upload_files() is private; replicate its one pre-condition: ensure temp dir exists.
		$tmpLocation = \GFFormsModel::get_tmp_upload_location($formId);
		$targetPath  = rgar($tmpLocation, 'path');
		if ($targetPath && ! is_dir($targetPath)) {
			wp_mkdir_p($targetPath);
			\GFCommon::recursive_add_index_file($targetPath);
		}

		foreach ($singleFileFields as $field) {
			$field->upload_submission_tmp_files();
		}

		// Persist the result so set_uploaded_files() (process_form line 70) restores it,
		// keeping $uploaded_files non-empty so GF renders the gform_uploaded_files hidden input.
		// Merge with any files already tracked in the hidden input (e.g. uploads from earlier pages)
		// so backward navigation on page N doesn't discard uploads from pages < N.
		$uploaded = \GFFormsModel::$uploaded_files[$formId] ?? [];

		if (is_array($uploaded) && [] !== $uploaded) {
			$existing = json_decode(rgpost('gform_uploaded_files'), true) ?: [];
			$_POST['gform_uploaded_files'] = json_encode(array_merge($existing, $uploaded), JSON_UNESCAPED_UNICODE);
		}

		return $form;
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
