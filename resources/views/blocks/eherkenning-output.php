<div class="sopenid-eherkenning-output">
	<?php if ( ! empty( $kvknr )) : ?>
		<p>Je KvK nummer is: <?php echo esc_html( $kvknr ); ?></p>
	<?php elseif (0 === $kvknr) : ?>
		<p>Je KvK nummer is niet beschikbaar</p>
	<?php else : ?>
		<p>Je bent niet ingelogd</p>
	<?php endif; ?>
</div>
