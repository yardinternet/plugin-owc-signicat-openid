<li class="open_id_second_login_setting field_setting">
	<div>
		<input type="checkbox" id="openIdIsSecondLogin"
			onchange="SetFieldProperty('openIdIsSecondLogin', this.checked);">
		<label for="openIdIsSecondLogin" class="inline">
			<?php _e( 'Second applicant (co-applicant)', 'owc-signicat-openid' ); ?>
		</label>
	</div>
	<div>
		<small>
			<?php _e( 'Activate this option if this field is intended for the co-applicant. The co-applicant logs in with a separate session.', 'owc-signicat-openid' ); ?>
		</small>
	</div>
</li>

<script type="text/javascript">
	jQuery(document).on('gform_load_field_settings', function(event, field) {
		const checkbox = document.getElementById('openIdIsSecondLogin');
		if (! checkbox) return;
		if (! field?.type?.includes('owc-signicat-openid')) return;

		checkbox.checked = field.openIdIsSecondLogin === true;
	});
</script>
