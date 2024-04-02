<?php
/**
 * EHerkenning Block provider.
 *
 * @package OWC_Signicat_OpenID
 *
 * @author  Yard | Digital Agency
 *
 * @since   0.0.1
 */

declare (strict_types = 1);

namespace OWCSignicatOpenID\Block;

/**
 * Exit when accessed directly.
 */
if (! defined('ABSPATH')) {
    exit;
}

use Odan\Session\PhpSession;
use OWCSignicatOpenID\Interfaces\Services\OpenIDServiceInterface;
use OWCSignicatOpenID\Provider\OpenID;
use OWCSignicatOpenID\View;

/**
 * Block class.
 *
 * @since 0.0.1
 */
class EHerkenning
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
     */
    public function __construct(
        OpenIDServiceInterface $oidc_client
    ) {
        $this->oidc_client = $oidc_client;
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
        add_action('init', [ $this, 'register_blocks' ]);
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
        if (! function_exists('register_block_type_from_metadata')) {
            return;
        }

        register_block_type_from_metadata(
            $this->plugin->get_directory() . '/dist/eherkenning',
        );

        register_block_type_from_metadata(
            $this->plugin->get_directory() . '/dist/eherkenning-output',
            [
                'render_callback' => [ $this, 'render_output' ],
            ]
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
        $valid_session = $this->session->has('access_token') && time() < $this->session->get('exp');
        $data = [];

        // Get the user info.
        if ($valid_session) {
            $data = $this->oidc_client->get_user_info();
        }

        // Check if the subject issuer is valid for this authentication method.
        $is_valid_issuer = in_array($data['subject_issuer'], [ 'simulator', 'eherkenning' ], true);

        $view = new View();
        $view->assign('is_active', $is_valid_issuer);

        // Check if the subject issuer is valid for this authentication method.
        if ($is_valid_issuer) {
            $view->assign('kvknr', $data['urn:etoegang:1.9:EntityConcernedID:KvKnr']);
        }

        $rendered_output = $view->render('blocks/eherkenning-output.php');

        return $rendered_output;
    }
}
