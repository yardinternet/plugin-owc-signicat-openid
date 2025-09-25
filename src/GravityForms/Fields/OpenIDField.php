<?php

declare(strict_types=1);

namespace OWCSignicatOpenID\GravityForms\Fields;

use GF_Field;
use GFAPI;
use GFFormDisplay;
use GFFormsModel;
use OWC\IdpUserData\DigiDSession;
use OWC\IdpUserData\eHerkenningSession;
use OWCSignicatOpenID\IdentityProvider;
use OWCSignicatOpenID\Interfaces\Services\OpenIDServiceInterface;

class OpenIDField extends GF_Field
{
    protected OpenIDServiceInterface $openIDService;
    public IdentityProvider $idp;
    public string $idpSlug;
    public string $label;
    public array $selectableScopes = [];

    public function __construct($data = [])
    {
        if (isset($data['idp']) && ! is_a($data['idp'], IdentityProvider::class)) {
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
        if (isset($this->openIdSelectedScopeValue) && null !== $this->openIdSelectedScopeValue) {
            $this->idp->addIdpScope($this->openIdSelectedScopeValue);
        }

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

        if ($this->is_form_editor()) {
            // Set the field property used for the scope select in the form editor.
            $this->selectableScopes = $this->prepareScopeSelectOptions();
        }

        if (! $this->is_entry_detail() && ! $this->is_form_editor() && ! $this->has_active_idp_session()) {
            $resumeUrl = $this->getResumeUrl();
            $input = sprintf(
                "<a href='%s'>%s</a>",
                esc_url($this->openIDService->getLoginUrl($this->idp, $resumeUrl, $resumeUrl, $this->idp->getIdpScopes())),
                $input
            );

            $input = $this->addPossibleErrorsToInput($input);
        }

        return sprintf("<div class='ginput_container ginput_container_openid'>%s</div>", $input);
    }

    /**
     * Prepare the scope options for the select field in the form editor.
     *
     * @return array<int, array{value: string, label: string}>
     */
    private function prepareScopeSelectOptions(): array
    {
        $default = [
            'value' => '',
            'label' => __('Selecteer een Service Index', 'owc-signicat-openid'),
        ];

        $supportedScopes = array_map(function ($scope) {
            if (strpos($scope, $this->idp->getSlug()) === false) {
                return null; // Skip scopes that do not match the IDP's slug.
            }

            return [
                'value' => $scope,
                'label' => $scope,
            ];
        }, $this->openIDService->getScopesSupported() ?? []);

        return [$default, ...array_filter($supportedScopes)];
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
        add_filter('gform_incomplete_submission_pre_save', function ($submissionJSON, $resumeToken, $form) {
            $submissionData = json_decode($submissionJSON);
            $submissionData->page_number = GFFormDisplay::get_current_page($this->formId);
            $submissionJSON = json_encode($submissionData);

            return $submissionJSON;
        }, 10, 3);

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

        return add_query_arg('gf_token', $resumeToken, $currentPageURL);
    }

    /**
     * If a different IDP session is already active like DigiD or eHerkenning, another field with a different IDP will pass the validation.
     * This enables form editors to use multiple IDP fields in the same form.
     * If there is only one IDP field in the form, this validation will also pass.
     */
    public function validate($value, $form)
    {
        if ($this->has_active_idp_session()) {
            $this->failed_validation = false;

            return;
        }

        $this->failed_validation = true;
        $this->validation_message = 'Je bent niet ingelogd';
    }

    private function has_active_idp_session(): bool
    {
        $digidSession = DigiDSession::isLoggedIn() && ! is_null(DigiDSession::getUserData());
        $eHerkenningSession = eHerkenningSession::isLoggedIn() && ! is_null(eHerkenningSession::getUserData());

        return $digidSession || $eHerkenningSession;
    }

    public function get_value_save_entry($value, $form, $input_name, $lead_id, $lead)
    {
        return sprintf('Ingelogd (%s)', $this->idp->getName());
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
            'open_id_select_scope_setting',
        ];
    }

    public function get_form_editor_field_icon()
    {
        return $this->idp->getLogoUrl();
    }
}
