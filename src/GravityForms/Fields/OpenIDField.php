<?php

declare(strict_types=1);

namespace OWCSignicatOpenID\GravityForms\Fields;

use GF_Field;
use OWCSignicatOpenID\IdentityProvider;
use OWCSignicatOpenID\Interfaces\Services\IdentityProviderServiceInterface;
use OWCSignicatOpenID\Interfaces\Services\OpenIDServiceInterface;
use OWCSignicatOpenID\Interfaces\Services\SettingsServiceInterface;

class OpenIDField extends GF_Field
{
    private const GROUP = 'owc-signicat-openid';

    protected OpenIDServiceInterface $openIDService;
    protected SettingsServiceInterface $settings;
    protected IdentityProviderServiceInterface $identityProviderService;
    public IdentityProvider $idp;

    public function __construct($data = [])
    {
        if (! is_a($data['idp'], IdentityProvider::class)) {
            $data['idp'] = new IdentityProvider($data['idp']['slug'] ?? 'slug', $data['idp']['name'] ?? 'name');
        }
        if (empty($data['type'])) {
            $data['type'] = sprintf('owc-signicat-openid-%s', $data['idp']->getSlug());
        }

        parent::__construct($data);
    }

    public function setServices(OpenIDServiceInterface $openIDService, SettingsServiceInterface $settings, IdentityProviderServiceInterface $idpService)
    {
        $this->openIDService = $openIDService;
        $this->settings = $settings;
        $this->identityProviderService = $idpService;
    }

    public function get_form_editor_field_title()
    {
        return $this->idp->getName();
    }

    public function get_field_input($form, $value = '', $entry = null)
    {
        if ($this->openIDService->get_user_info() && ! $this->is_form_editor()) {
            return sprintf(
                "<div class='ginput_container ginput_container_openid'>%s</div>",
                'Je bent ingelogd'//print_r($this->openIDService->get_user_info(), true)
            );
        }

        $input = sprintf(
            "<img src='%s' width='auto' height='60px'>",
            $this->idp->getLogoUrl(),
        );

        if (! $this->is_entry_detail() && ! $this->is_form_editor()) {
            //TODO: show login error
            $input = sprintf(
                "<a href='%s'>%s</a>",
                esc_url(
                    add_query_arg(
                        [
                            'idp' => $this->idp->getSlug(),
                            'redirect_url' => $this->getResumeUrl(),
                        ],
                        get_site_url(null, $this->settings->get_setting('path_login'))
                    ),
                ),
                $input
            );
        }

        return sprintf("<div class='ginput_container ginput_container_openid'>%s</div>", $input);
    }

    protected function getResumeUrl(): string
    {
        $resume = \GFAPI::submit_form(
            $this->formId,
            [
                'gf_submitting_' . $this->formId => true,
                'saved_for_later'                => true,
                'gform_save'                     => true,
            ]
        );

        //TODO: check resume result voor WP_Error

        $resumeToken = $resume['resume_token'] ?? null;

        return \add_query_arg('gf_token', $resumeToken, \GFFormsModel::get_current_page_url(true));
    }

    public function validate($value, $form)
    {
        //TODO!
        //$this->failed_validation = true;
        //$this->validation_message = 'Je moeder';
    }

    public function get_value_save_entry($value, $form, $input_name, $lead_id, $lead)
    {
        $user_info = $this->openIDService->get_user_info();

        return 'test';
        //TODO: encrypt + abstract get id-field
    }

    public function is_value_submission_empty($formId)
    {
        return empty($this->openIDService->get_user_info());
    }

    public function get_value_submission($field_values, $get_from_post_global_var = true)
    {
        return $this->openIDService->get_user_info();
    }

    public function get_form_editor_button()
    {
        return [
            'group' => self::GROUP,
            'text'  => $this->get_form_editor_field_title(),
        ];
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

    public function add_button($field_groups): array
    {
        $field_groups = $this->maybe_add_field_group($field_groups);

        return parent::add_button($field_groups);
    }

    public function maybe_add_field_group($field_groups)
    {
        foreach ($field_groups as $field_group) {
            if (self::GROUP === $field_group['name']) {

                return $field_groups;
            }
        }

        $field_groups[] = [
            'name'   => self::GROUP,
            'label'  => __('Signicat OpenID', 'owc'),
            'fields' => [],
        ];

        return $field_groups;
    }
}
