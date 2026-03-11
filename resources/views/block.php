<div class="owc-signicat-openid">
	<a href="<?php echo esc_url( $url ); ?>" class="owc-signicat-openid__button">
		<?php echo esc_html( $buttonText ); ?>
	</a>
	<?php if (0 < count($errors)) : ?>
		<div class="alert alert-danger">
			<strong>Er zijn problemen met de inlogpoging:</strong>
			<ul>
				<?php foreach ($errors as $message) : ?>
						<li><?php echo esc_html( $message ); ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>
</div>
