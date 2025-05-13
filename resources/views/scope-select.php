<li class="open_id_select_scope_setting field_setting" id="openIdScopeSelectWrapper">
	<label for="openIdSelectedScope" class="section_label">
		<?php _e( 'Service Catalog - Service Index', 'owc-signicat-openid' ); ?>
	</label>

	<select id="openIdSelectedScope" onchange="SetFieldProperty('openIdSelectedScopeValue', this.value);">
		<!-- Options will be populated by JavaScript -->
	</select>
</li>

<script type="text/javascript">
	jQuery(document).on('gform_load_field_settings', function(event, field) {
		const select = document.getElementById('openIdSelectedScope');
		const wrapper = document.getElementById('openIdScopeSelectWrapper');

		if (! select || ! wrapper) return;
		if (! field?.type?.includes('owc-signicat-openid')) return;
		if (! Array.isArray(field.selectableScopes)) return;

		select.innerHTML = ''; // Clear existing options so we can repopulate.

		field.selectableScopes.forEach(scope => {
			const option = document.createElement('option');
			option.value = scope.value;
			option.textContent = scope.label;
			select.appendChild(option);
		});

		if (! field.openIdSelectedScopeValue) return;

		select.value = field.openIdSelectedScopeValue;
	});
</script>
