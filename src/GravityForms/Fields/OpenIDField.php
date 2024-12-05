<?php

declare(strict_types=1);

namespace OWCSignicatOpenID\GravityForms\Fields;

use GF_Field;
use GFAPI;
use GFFormsModel;
use OWCSignicatOpenID\IdentityProvider;
use OWCSignicatOpenID\Interfaces\Services\OpenIDServiceInterface;

class OpenIDField extends GF_Field
{
    protected OpenIDServiceInterface $openIDService;
    public IdentityProvider $idp;
    public string $idpSlug;

    public function __construct($data = [])
    {
        if (! is_a($data['idp'], IdentityProvider::class)) {
            unset($data['idp']);
        }
        if (empty($data['type'])) {
            $data['type'] = sprintf('owc-signicat-openid-%s', $data['idp']->getSlug());
        }

        parent::__construct($data);
    }

    public function get_form_editor_field_title()
    {
        return $this->idp->getName();
    }

    public function get_field_input($form, $value = '', $entry = null)
    {
        if ($this->openIDService->getUserInfo($this->idp) && ! $this->is_form_editor()) {
            return sprintf(
                "<div class='ginput_container ginput_container_openid'>%s</div>",
                'Je bent ingelogd',
                print_r($this->openIDService->getUserInfo($this->idp), true)
            );
        }

        $input = sprintf(
            "<img src='%s' width='90px' height='90px' class='gform-theme__disable-reset'>",
            $this->idp->getLogoUrl(),
        );

        if (! $this->is_entry_detail() && ! $this->is_form_editor()) {
            $resumeUrl = $this->getResumeUrl();
            $input = sprintf(
                "<a href='%s'>%s</a>",
                esc_url($this->openIDService->getLoginUrl($this->idp, $resumeUrl, $resumeUrl)),
                $input
            );

            $input = $this->addPossibleErrorsToInput($input);
        }

        return sprintf("<div class='ginput_container ginput_container_openid'>%s</div>", $input);
    }

    protected function addPossibleErrorsToInput(string $input): string
    {
        $errors = $this->openIDService->flashErrors();

        if (! count($errors)) {
            return $input;
        }

        $errorItems = $this->formatErrors($errors);

        $html = sprintf('
		<div class="alert alert-danger">
			<strong>Er zijn problemen met de inlogpoging:</strong>
			<ul>%s</ul>
		</div>', $errorItems);

        return $html . $input;
    }

    /**
     * The errors array is double nested, implode to string.
     */
    private function formatErrors(array $errors): string
    {
        return implode(
            '',
            array_map(
                fn ($errorGroup) => implode(
                    '',
                    array_map(
                        fn ($message) => sprintf('<li>%s</li>', esc_html($message)),
                        $errorGroup
                    )
                ),
                $errors
            )
        );
    }

    protected function getResumeUrl(): string
    {
        $currentPageURL = GFFormsModel::get_current_page_url(true);

        $resume = GFAPI::submit_form(
            $this->formId,
            [
                'gf_submitting_' . $this->formId => true,
                'saved_for_later' => true,
                'gform_save' => true,
            ]
        );

        if (is_wp_error($resume)) {
            return $currentPageURL;
        }

        $resumeToken = $resume['resume_token'] ?? null;

        if (! is_string($resumeToken) || 1 > strlen($resumeToken)) {
            return $currentPageURL;
        }

        return \add_query_arg('gf_token', $resumeToken, $currentPageURL);
    }

    public function validate($value, $form)
    {
        $userInfo = $this->openIDService->getUserInfo($this->idp);

        if (! is_array($userInfo) || ! count($userInfo)) {
            $this->failed_validation = true;
            $this->validation_message = 'Je bent niet ingelogd';
        }
    }

    public function get_value_save_entry($value, $form, $input_name, $lead_id, $lead)
    {
        $userInfo = $this->openIDService->getUserInfo($this->idp);

        $saveFields = $this->idp->getSaveFields();
        $value = wp_array_slice_assoc($userInfo, array_values($saveFields));

        return maybe_serialize($value);
    }

    public function get_value_entry_list($value, $entry, $field_id, $columns, $form)
    {
        return $value;
    }

    public function get_value_entry_detail($value, $currency = '', $use_text = false, $format = 'html', $media = 'screen')
    {
        return $value;
    }

    public function is_value_submission_empty($formId)
    {
        return empty($this->openIDService->getUserInfo($this->idp));
    }

    public function get_value_submission($field_values, $get_from_post_global_var = true)
    {
        return $this->openIDService->getUserInfo($this->idp);
    }

    public function get_form_editor_field_settings()
    {
        return [
            'label_setting',
            'label_placement_setting',
            'description_setting',
            'columns_setting', //?is dit nodig
            'conditional_logic_field_setting',
            'css_class_setting',
            'rules_setting',
        ];
    }

    public function get_form_editor_field_icon()
    {
        return $this->idp->getLogoUrl();
    }
}
