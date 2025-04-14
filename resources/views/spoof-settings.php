<?php
/**
 * Settings view.
 *
 * @package OWC_Signicat_OpenID
 *
 * @author  Yard | Digital Agency
 *
 * @since   0.0.1
 */

declare (strict_types=1);

namespace OWCSignicatOpenID;

?>

<div class="wrap">
	<h1><?php esc_html_e('Signicat Simulator', 'owc-signicat-openid'); ?></h1>

    <p>Nog geen Signicat aansluiting gerealiseerd? Worry no more! Met de Signicat Simulator 2000™ kun je net doen alsof je ingelogd bent op DigiD!</p>

	<form method="post" action="options.php">
		<?php settings_fields('owc_signicat_simulator_settings_group'); ?>
		<?php do_settings_sections('owc_signicat_simulator_settings_group'); ?>
		<table class="form-table">
            <tr>
				<th scope="row">
					<label for="owc_signicat_simulator_enable_simulator_settings">
						<?php esc_html_e('Enable simulator', 'owc-signicat-openid'); ?>
					</label>
				</th>
				<td>
					<input type='checkbox' name='owc_signicat_simulator_enable_simulator_settings' <?php checked($enable_simulator, 1); ?> value='1'>
				</td>
			</tr>
            
            <tr>
				<th scope="row">
					<label for="owc_signicat_simulator_bsn_settings">
						<?php esc_html_e('BSN-nummer', 'owc-signicat-openid'); ?>
					</label>
				</th>
				<td>
					<input type="number" min="0" name="owc_signicat_simulator_bsn_settings" id="owc_signicat_simulator_bsn_settings" value="<?php echo esc_attr($bsn); ?>">
				</td>
			</tr>

            <tr>
				<th scope="row">
					<label for="owc_signicat_simulator_levelOfAssurance_settings">
						<?php esc_html_e('Zekerheidsniveau', 'owc-signicat-openid'); ?>
					</label>
				</th>
				<td>
					<input type="text" name="owc_signicat_simulator_levelOfAssurance_settings" id="owc_signicat_simulator_levelOfAssurance_settings" value="<?php echo esc_attr($levelOfAssurance); ?>">
				</td>
			</tr>
		</table>
		<?php submit_button(); ?>
	</form>
</div>
