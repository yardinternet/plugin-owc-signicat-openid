<?php
/**
 * EIDAS Block provider.
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
use Odan\Session\PhpSession;

use OWCSignicatOpenID\Provider\OpenID;
use OWCSignicatOpenID\View;

/**
 * Block class.
 *
 * @since 0.0.1
 */
class EIDAS extends AbstractHookProvider
{
	/**
	 * OIDC Client.
	 *
	 * @var OpenID
	 */
	protected $oidc_client;

	/**
	 * Session.
	 *
	 * @var PhpSession
	 */
	protected $session;

	/**
	 * View.
	 *
	 * @var View
	 */
	protected $view;

	/**
	 * Constructor.
	 *
	 * @since 0.0.1
	 *
	 * @param OpenID     $oidc_client         OIDC Client.
	 * @param PhpSession $session             Session.
	 */
	public function __construct(
		OpenID $oidc_client,
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
			$this->plugin->get_directory() . '/dist/eidas',
		);

		register_block_type_from_metadata(
			$this->plugin->get_directory() . '/dist/eidas-output',
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
	 * @return string
	 */
	public function render_output(): string
	{
		// Access token exists and not beyond expiration.
		$valid_session = $this->session->has( 'access_token' ) && time() < $this->session->get( 'exp' );
		$data          = array();

		// Get the user info.
		if ($valid_session) {
			$data = $this->oidc_client->get_user_info();
		}

		// Check if the subject issuer is valid for this authentication method.
		$is_valid_issuer = in_array( $data['subject_issuer'], array( 'simulator', 'eidas' ), true );

		$view = new View();
		$view->assign( 'is_active', $is_valid_issuer );

		// Check if the subject issuer is valid for this authentication method.
		if ($is_valid_issuer) {
			$view->assign( 'pseudo', $data['urn:etoegang:1.12:EntityConcernedID:PseudoID'] );
			$view->assign( 'bsn', $data['urn:etoegang:1.12:EntityConcernedID:BSN'] );
			$view->assign( 'family_name', $data['urn:etoegang:1.9:attribute:FamilyName'] );
			$view->assign( 'first_name', $data['urn:etoegang:1.9:attribute:FirstName'] );
			$view->assign( 'date_of_birth', $data['urn:etoegang:1.9:attribute:DateOfBirth'] );
		}

		$rendered_output = $view->render( 'blocks/eidas-output.php' );
		return $rendered_output;
	}
}
