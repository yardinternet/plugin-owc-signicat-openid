<?php

declare(strict_types=1);

namespace OWCSignicatOpenID\GravityForms\Fields;

class eIDASField extends OpenIDField
{

    protected static function getIdp(): string
    {
        return 'eidas';
    }

    public function get_form_editor_field_title()
    {
        return esc_attr('eIDAS');
    }
}
