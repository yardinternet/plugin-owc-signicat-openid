<?php
/**
 * EHerkenning Block provider.
 *
 * @package OWC_Signicat_OpenID
 * @author  Yard | Digital Agency
 * @since   0.0.1
 */

declare ( strict_types = 1 );

namespace OWCSignicatOpenID\Block;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' )) {
	exit;
}

use Cedaro\WP\Plugin\AbstractHookProvider;
use Facile\OpenIDClient\Client\ClientInterface;
use Odan\Session\PhpSession;

/**
 * Block class.
 *
 * @since 0.0.1
 */
class EHerkenning extends AbstractHookProvider
{
	/**
	 * OIDC Client.
	 *
	 * @var ClientInterface
	 */
	protected $oidc_client;

	/**
	 * Session.
	 *
	 * @var PhpSession
	 */
	protected $session;

	/**
	 * Constructor.
	 *
	 * @since 0.0.1
	 *
	 * @param  $oidc_client         OIDC Client.
	 * @param PhpSession                      $session             Session.
	 */
	public function __construct(
		$oidc_client,
		PhpSession $session
	) {
		$this->oidc_client = $oidc_client;
		$this->session     = $session;
	}

	/**
	 * Register hooks.
	 *
	 * @since 0.0.1
	 *
	 * @return void
	 */
	public function register_hooks(): void
	{
		add_action( 'init', array( $this, 'register_blocks' ) );
	}

	/**
	 * Register the Gutenberg blocks.
	 *
	 * @since 0.0.1
	 *
	 * @return void
	 */
	public function register_blocks(): void
	{
		// block editor is not available.
		if ( ! function_exists( 'register_block_type_from_metadata' )) {
			return;
		}

		register_block_type_from_metadata(
			$this->plugin->get_directory() . '/dist/eherkenning',
		);

		register_block_type_from_metadata(
			$this->plugin->get_directory() . '/dist/eherkenning-output',
			array(
				'render_callback' => array( $this, 'render_output' ),
			)
		);
	}

	/**
	 * Render the output.
	 *
	 * @since 0.0.1
	 *
	 * @return void
	 */
	public function render_output(): void
	{
		// TODO handle when not logged in
		$user_info = $this->oidc_client->get_user_info();

		$kvkNumber = $user_info['urn:etoegang:1.9:EntityConcernedID:KvKnr'];

		echo $kvkNumber;
	}
}
