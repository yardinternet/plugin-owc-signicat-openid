<?php

declare(strict_types=1);

namespace OWCSignicatOpenID\GravityForms\Fields;

use GF_Field;
use OWCSignicatOpenID\Interfaces\Services\OpenIDServiceInterface;

abstract class OpenIDField extends GF_Field
{
    protected OpenIDServiceInterface $openIDService;

    abstract protected static function getIdp(): string;

    public function __construct($data = [])
    {
        $data['type'] = sprintf('owc-signicat-openid-%s', static::getIdp());
        parent::__construct($data);
    }

    public function setOpenIDService(OpenIDServiceInterface $openIDService)
    {
        $this->openIDService = $openIDService;
    }

    public function get_field_input($form, $value = '', $entry = null)
    {

        if ($this->openIDService->get_user_info()) {
            return sprintf("<div class='ginput_container ginput_container_openid'>%s</div>", print_r($this->openIDService->get_user_info(), true));
        }

        $input = sprintf(
            "<img src='%s' width='auto' height='60px'>",
            OWC_SIGNICAT_OPENID_PLUGIN_URL . sprintf('resources/img/logo-%s.svg', $this->getIdp()),
        );

        if (! $this->is_entry_detail() && ! $this->is_form_editor()) {
            //TODO: show login error
            $input = sprintf(
                "<a href='%s'>%s</a>",
                esc_url(
                    add_query_arg(
                        [
                            'idp' => $this->getIdp(),
                            'redirect_url' => $this->getResumeUrl(),
                        ],
                        '/sso-login' // TODO: uit settings halen
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
        $this->failed_validation = true;
        $this->validation_message = 'Je moeder';
    }

    public function get_value_save_entry($value, $form, $input_name, $lead_id, $lead)
    {
        $user_info = $this->openIDService->get_user_info();

        //TODO: encrypt + abstract get id-field
    }

    public function get_value_submission($field_values, $get_from_post_global_var = true)
    {
        return $this->openIDService->get_user_info();
    }

    public function get_form_editor_button()
    {
        return [
            'group' => 'signicat',
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
            'rules_setting', //? Nodig?
        ];
    }

    public function get_form_editor_field_icon()
    {
        return OWC_SIGNICAT_OPENID_PLUGIN_URL . sprintf('resources/img/logo-%s.svg', $this->getIdp());
    }

    public function add_button($field_groups): array
    {
        $field_groups = $this->maybe_add_field_group($field_groups);

        return parent::add_button($field_groups);
    }

    public function maybe_add_field_group($field_groups)
    {
        foreach ($field_groups as $field_group) {
            if ('signicat' === $field_group['name']) {

                return $field_groups;
            }
        }

        $field_groups[] = [
            'name'   => 'signicat',
            'label'  => __('Signicat OpenID', 'owc'),
            'fields' => [],
        ];

        return $field_groups;
    }
}
