<div class="owc-signicat-openid">
	<a href="<?php echo esc_url( $url ); ?>" class="owc-signicat-openid__button">
		<?php echo esc_html( $buttonText ); ?>
	</a>
	<?php if ($errors) : ?>
		<div class="alert alert-danger">
			<strong>Er zijn problemen met de inlogpoging:</strong>
			<ul>
				<?php foreach ($errors as $error) : ?>
					<?php foreach ($error as $message) : ?>
						<li><?php echo esc_html( $message ); ?></li>
					<?php endforeach; ?>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>
</div>
