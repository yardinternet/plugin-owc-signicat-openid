<?php

declare(strict_types=1);

namespace OWCSignicatOpenID\Services;

use GF_Field;
use GF_Fields;
use OWCSignicatOpenID\GravityForms\Fields\DigiDField;
use OWCSignicatOpenID\GravityForms\Fields\eHerkenningField;
use OWCSignicatOpenID\GravityForms\Fields\eIDASField;
use OWCSignicatOpenID\GravityForms\Fields\OpenIDField;
use OWCSignicatOpenID\Interfaces\Services\GravityFormsServiceInterface;
use OWCSignicatOpenID\Interfaces\Services\OpenIDServiceInterface;

class GravityFormsService extends Service implements GravityFormsServiceInterface
{
    protected OpenIDServiceInterface $openIDService;

    public function __construct(OpenIDServiceInterface $openIDService)
    {
        $this->openIDService = $openIDService;
    }

    public function register()
    {
        add_action('gform_loaded', [$this, 'registerFields']);
        add_filter('gform_gf_field_create', [$this, 'setOpenIDService'], 10, 2);
        add_filter('gform_incomplete_submission_pre_save', [$this, 'setPageNumber'], 10, 3);
    }

    public function registerFields()
    {
        GF_Fields::register(new DigiDField(['openIdService' => $this->openIDService ]));
        GF_Fields::register(new eHerkenningField(['openIdService' => $this->openIDService ]));
        GF_Fields::register(new eIDASField(['openIdService' => $this->openIDService ]));
    }

    public function setOpenIDService(GF_Field $field, array $properties): GF_Field
    {
        if (! is_a($field, OpenIDField::class)) {
            return $field;
        }
        $field->setOpenIDService($this->openIDService);
        //TODO: inject EncryptionService?

        return $field;
    }

    public function setPageNumber(string $submission_json, string $resume_token, array $form): string
    {
        $submissionData = \json_decode($submission_json);
        $submissionData->page_number = \GFFormDisplay::get_current_page($form['id']);

        return \json_encode($submissionData);
    }
}
