<?php

declare(strict_types=1);

namespace OWCSignicatOpenID\Services;

use GF_Field;
use GF_Fields;
use GFCommon;
use OWCSignicatOpenID\GravityForms\Fields\OpenIDField;
use OWCSignicatOpenID\Interfaces\Services\GravityFormsServiceInterface;
use OWCSignicatOpenID\Interfaces\Services\IdentityProviderServiceInterface;
use OWCSignicatOpenID\Interfaces\Services\OpenIDServiceInterface;
use OWCSignicatOpenID\Interfaces\Services\SettingsServiceInterface;

class GravityFormsService extends Service implements GravityFormsServiceInterface
{
    protected OpenIDServiceInterface $openIDService;
    protected SettingsServiceInterface $settings;
    protected IdentityProviderServiceInterface $idpService;

    public function __construct(
        OpenIDServiceInterface $openIDService,
        SettingsServiceInterface $settings,
        IdentityProviderServiceInterface $idpService
    ) {
        $this->openIDService = $openIDService;
        $this->settings = $settings;
        $this->idpService = $idpService;
    }

    public function register()
    {
        add_action('gform_loaded', [$this, 'registerFields']);
        add_filter('gform_gf_field_create', [$this, 'setOpenIDService'], 10, 2);
        add_filter('gform_incomplete_submission_pre_save', [$this, 'setPageNumber'], 10, 3);
        add_action('gform_editor_js_set_default_values', [$this, 'setDefaults']);

        add_filter('gform_get_input_value', [$this, 'decrypt'], 10, 4);
        add_filter('gform_save_field_value', [$this, 'encrypt'], 10, 5);
    }

    public function decrypt(string $value, array $entry, \GF_Field $field, $input_id): string
    {
        if (! is_a($field, OpenIDField::class)) {
            return $value;
        }

        return GFCommon::openssl_decrypt($value);
    }

    public function encrypt($value, $entry, $field, $form, $input_id)
    {
        if (! is_a($field, OpenIDField::class)) {
            return $value;
        }

        return GFCommon::openssl_encrypt($value);
    }

    public function setDefaults()
    {
        foreach ($this->idpService->getActiveIdentityProviders() as $idp) {
            ?>
			case "<?php echo sprintf('owc-signicat-openid-%s', $idp->getSlug());?>":
				field.idp = <?php echo wp_json_encode($idp);?>;
				break;
			<?php
        }
    }

    public function registerFields()
    {
        $services = [
            'openIdService' => $this->openIDService,
            'settings' => $this->settings,
            'idpService' => $this->idpService,
        ];

        foreach ($this->idpService->getActiveIdentityProviders() as $idp) {
            $data = ['idp' => $idp ] + $services;
            GF_Fields::register(new OpenIDField($data));
        }
    }

    public function setOpenIDService(GF_Field $field, array $properties): GF_Field
    {
        if (! is_a($field, OpenIDField::class)) {
            return $field;
        }
        $field->setServices($this->openIDService, $this->settings, $this->idpService);

        return $field;
    }

    public function setPageNumber(string $submission_json, string $resume_token, array $form): string
    {
        $submissionData = \json_decode($submission_json);
        $submissionData->page_number = \GFFormDisplay::get_current_page($form['id']);

        return \json_encode($submissionData);
    }
}
