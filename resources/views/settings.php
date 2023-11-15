<?php
/**
 * Settings view.
 *
 * @package OWC_Signicat_OpenID
 * @author  Yard | Digital Agency
 * @since   0.0.1
 */

declare ( strict_types = 1 );

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
					<input type="text" name="owc_signicat_openid_configuration_url_settings" id="owc_signicat_openid_configuration_url_settings" value="<?php echo esc_attr( get_option( 'owc_signicat_openid_configuration_url_settings' ) ); ?>" required>
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
					<input type="text" name="owc_signicat_openid_client_id_settings" id="owc_signicat_openid_client_id_settings" value="<?php echo esc_attr( get_option( 'owc_signicat_openid_client_id_settings' ) ); ?>" required>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="owc_signicat_openid_client_secret_settings">
						<?php esc_html_e( 'Client Secret', 'owc-signicat-openid' ); ?>
					</label>
				</th>
				<td>
					<input type="password" name="owc_signicat_openid_client_secret_settings" id="owc_signicat_openid_client_secret_settings" value="<?php echo esc_attr( get_option( 'owc_signicat_openid_client_secret_settings' ) ); ?>" required>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="owc_signicat_openid_path_login_settings">
						<?php esc_html_e( 'Login path', 'owc-signicat-openid' ); ?>
					</label>
				</th>
				<td>
					<input type="text" name="owc_signicat_openid_path_login_settings" id="owc_signicat_openid_path_login_settings" value="<?php echo esc_attr( get_option( 'owc_signicat_openid_path_login_settings' ) ); ?>" required>
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
					<input type="text" name="owc_signicat_openid_path_logout_settings" id="owc_signicat_openid_path_logout_settings" value="<?php echo esc_attr( get_option( 'owc_signicat_openid_path_logout_settings' ) ); ?>" required>
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
					<input type="text" name="owc_signicat_openid_path_redirect_settings" id="owc_signicat_openid_path_redirect_settings" value="<?php echo esc_attr( get_option( 'owc_signicat_openid_path_redirect_settings' ) ); ?>" required>
					<p class="description">
						<?php esc_html_e( 'Example: redirect', 'owc-signicat-openid' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="owc_signicat_openid_path_refresh_settings">
						<?php esc_html_e( 'Refresh path', 'owc-signicat-openid' ); ?>
					</label>
				</th>
				<td>
					<input type="text" name="owc_signicat_openid_path_refresh_settings" id="owc_signicat_openid_path_refresh_settings" value="<?php echo esc_attr( get_option( 'owc_signicat_openid_path_refresh_settings' ) ); ?>" required>
					<p class="description">
						<?php esc_html_e( 'Example: sso-refresh', 'owc-signicat-openid' ); ?>
					</p>
				</td>
			</tr>
		</table>
		<?php submit_button(); ?>
	</form>
</div>
