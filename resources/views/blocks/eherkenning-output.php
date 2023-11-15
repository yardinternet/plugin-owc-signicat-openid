<div class="sopenid-eherkenning-output">
	<?php if ( ! $is_active ) : ?>
		<p><?php esc_html_e( 'Je bent niet ingelogd met eHerkenning', 'owc-signicat-openid' ); ?></p>
	<?php endif; ?>

	<?php if ( ! empty( $kvknr )) : ?>
		<p><?php esc_html_e( 'Je KVK nummer is', 'owc-signicat-openid' ); ?>: <?php echo esc_html( $kvknr ); ?></p>
	<?php elseif (0 === $kvknr) : ?>
		<p><?php esc_html_e( 'Je KVK nummer is niet beschikbaar', 'owc-signicat-openid' ); ?></p>
	<?php endif; ?>
</div>
