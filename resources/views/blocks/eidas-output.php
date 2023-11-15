<div class="sopenid-eidas-output">
	<?php if ( ! $is_active ) : ?>
		<p><?php esc_html_e( 'Je bent niet ingelogd met eIDAS', 'owc-signicat-openid' ); ?></p>
	<?php else : ?>
		<ul>
		<?php if (isset( $bsn )) : ?>
			<li><?php esc_html_e( 'BSN', 'owc-signicat-openid' ); ?>: <?php echo esc_html( $bsn ); ?></li>
		<?php endif; ?>
		<?php if (isset( $first_name )) : ?>
			<li><?php esc_html_e( 'Voornaam', 'owc-signicat-openid' ); ?>: <?php echo esc_html( $first_name ); ?></li>
		<?php endif; ?>
		<?php if (isset( $family_name )) : ?>
			<li><?php esc_html_e( 'Achternaam', 'owc-signicat-openid' ); ?>: <?php echo esc_html( $family_name ); ?></li>
		<?php endif; ?>
		<?php if (isset( $date_of_birth )) : ?>
			<li><?php esc_html_e( 'Geboortedatum', 'owc-signicat-openid' ); ?>: <?php echo esc_html( $date_of_birth ); ?></li>
		<?php endif; ?>
	</ul>
	<?php endif; ?>
</div>
