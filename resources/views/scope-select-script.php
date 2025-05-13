<?php
/**
 * Script for setting selected/entered values to the field settings.
 **/
?>
<script type='text/javascript'>
	// Add setting classes to specific fields.
	jQuery.each(fieldSettings, function(index, value) {
		fieldSettings['owc-signicat-openid'] += ', .open_id_select_scope_setting';
	});

	jQuery(document).bind('gform_load_field_settings', function(event, field, form) {
		jQuery('#openIdSelectedScope').val(field['openIdSelectedScopeValue']);
	});
</script>
