<div class="sopenid-eidas-output">
	<?php if ( ! $pseudo ) : ?>
		<p>Je bent niet ingelogd</p>
	<?php else : ?>
		<ul>
			<li>BSN: <?php echo esc_html( $bsn ); ?></li>
			<li>Voornaam: <?php echo esc_html( $first_name ); ?></li>
			<li>Achternaam: <?php echo esc_html( $family_name ); ?></li>
			<li>Geboortedatum: <?php echo esc_html( $date_of_birth ); ?></li>
		</ul>
	<?php endif; ?>
</div>
