<?php
/**
 * Settings view.
 *
 * @package OWC_Signicat_OpenID
 * @author  Yard | Digital Agency
 * @since   0.0.1
 */

declare (strict_types = 1);

namespace OWCSignicatOpenID;

?>

<div class="wrap">
	<h1><?php esc_html_e( 'Signicat OpenID', 'owc-signicat-openid' ); ?></h1>

	<form method="post" action="options.php">
		<?php settings_fields( 'owc_signicat_openid_settings_group' ); ?>
		<?php do_settings_sections( 'owc_signicat_openid_settings_group' ); ?>
		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="owc_signicat_openid_configuration_url_settings">
						<?php esc_html_e( 'Configuration URL', 'owc-signicat-openid' ); ?>
					</label>
				</th>
				<td>
					<input type="url" name="owc_signicat_openid_configuration_url_settings" id="owc_signicat_openid_configuration_url_settings" value="<?php echo esc_attr( $configuration_url ); ?>" width="40" required>
					<p class="description">
						<?php esc_html_e( 'Example: https://example.com/.well-known/openid-configuration', 'owc-signicat-openid' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="owc_signicat_openid_client_id_settings">
						<?php esc_html_e( 'Client ID', 'owc-signicat-openid' ); ?>
					</label>
				</th>
				<td>
					<input type="text" name="owc_signicat_openid_client_id_settings" id="owc_signicat_openid_client_id_settings" value="<?php echo esc_attr( $client_id ); ?>" required>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="owc_signicat_openid_client_secret_settings">
						<?php esc_html_e( 'Client Secret', 'owc-signicat-openid' ); ?>
					</label>
				</th>
				<td>
					<input type="password" name="owc_signicat_openid_client_secret_settings" id="owc_signicat_openid_client_secret_settings" value="<?php echo esc_attr( $client_secret ); ?>" required>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="owc_signicat_openid_path_login_settings">
						<?php esc_html_e( 'Login path', 'owc-signicat-openid' ); ?>
					</label>
				</th>
				<td>
					<input type="text" name="owc_signicat_openid_path_login_settings" id="owc_signicat_openid_path_login_settings" value="<?php echo esc_attr( $path_login ); ?>" required>
					<p class="description">
						<?php esc_html_e( 'Example: sso-login', 'owc-signicat-openid' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="owc_signicat_openid_path_logout_settings">
						<?php esc_html_e( 'Logout path', 'owc-signicat-openid' ); ?>
					</label>
				</th>
				<td>
					<input type="text" name="owc_signicat_openid_path_logout_settings" id="owc_signicat_openid_path_logout_settings" value="<?php echo esc_attr( $path_logout ); ?>" required>
					<p class="description">
					<?php esc_html_e( 'Example: sso-logout', 'owc-signicat-openid' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="owc_signicat_openid_path_redirect_settings">
						<?php esc_html_e( 'Redirect path', 'owc-signicat-openid' ); ?>
					</label>
				</th>
				<td>
					<input type="text" name="owc_signicat_openid_path_redirect_settings" id="owc_signicat_openid_path_redirect_settings" value="<?php echo esc_attr( $path_redirect ); ?>" required>
					<p class="description">
						<?php esc_html_e( 'Example: redirect', 'owc-signicat-openid' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="owc_signicat_openid_enable_simulator_settings">
						<?php esc_html_e( 'Enable simulator', 'owc-signicat-openid' ); ?>
					</label>
				</th>
				<td>
					<input type='checkbox' name='owc_signicat_openid_enable_simulator_settings' <?php checked( $enable_simulator, 1 ); ?> value='1'>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="owc_signicat_openid_service_index_eherkenning_settings">
						<?php esc_html_e( 'eHerkenning service index', 'owc-signicat-openid' ); ?>
					</label>
				</th>
				<td>
					<input type="text" name="owc_signicat_openid_service_index_eherkenning_settings" id="owc_signicat_openid_service_index_eherkenning_settings" value="<?php echo esc_attr( $service_index_eherkenning ); ?>">
					<p class="description">
						<?php esc_html_e( 'The service index of the eHerkenning service in your Signicat catalogue. Leave empty to use the default service configured in Signicat. Example: 9701', 'owc-signicat-openid' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="owc_signicat_openid_service_index_eidas_settings">
						<?php esc_html_e( 'eIDAS service index', 'owc-signicat-openid' ); ?>
					</label>
				</th>
				<td>
					<input type="text" name="owc_signicat_openid_service_index_eidas_settings" id="owc_signicat_openid_service_index_eidas_settings" value="<?php echo esc_attr( $service_index_eidas ); ?>">
					<p class="description">
						<?php esc_html_e( 'The service index of the eIDAS-enabled service in your Signicat catalogue. Leave empty to use the default service configured in Signicat. Example: 9702', 'owc-signicat-openid' ); ?>
					</p>
				</td>
			</tr>
		</table>
		<?php submit_button(); ?>
	</form>
</div>
