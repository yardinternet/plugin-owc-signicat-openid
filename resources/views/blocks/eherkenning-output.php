<div class="sopenid-eherkenning-output">
	<?php
	echo $kvknr;
	if ( ! empty( $kvknr )) {
		echo "<p>Je KvK nummer is: $kvknr</p>";
	} else {
		echo "<p>Je KvK nummer is niet beschikbaar</p>";
	}
	?>
	<p>Je bent niet ingelogd</p>
</div>
