<?php

declare(strict_types=1);

namespace OWCSignicatOpenID\Services;

use Odan\Session\SessionInterface;
use OWCSignicatOpenID\Interfaces\Services\BlockServiceInterface;
use OWCSignicatOpenID\Interfaces\Services\OpenIDServiceInterface;

class BlockService extends Service implements BlockServiceInterface
{
    protected OpenIDServiceInterface $openIDClient;
    protected SessionInterface $session;

    public function __construct(OpenIDServiceInterface $openIDClient, SessionInterface $session)
    {
        $this->openIDClient = $openIDClient;
        $this->session = $session;
    }

    public function addBlock()
    {
    }

    public function register()
    {
        add_action('init', [$this, 'registerBlocks']);

    }
    public function registerBlocks()
    {
        register_block_type_from_metadata(
            OWC_SIGNICAT_OPENID_DIR_PATH . 'dist/eherkenning',
        );
        register_block_type_from_metadata(
            OWC_SIGNICAT_OPENID_DIR_PATH . 'dist/eidas',
        );

        register_block_type_from_metadata(
            OWC_SIGNICAT_OPENID_DIR_PATH . 'dist/eherkenning-output',
            [
                'render_callback' => [ $this, 'render_output' ],
            ]
        );

    }

    public function render_output(): string
    {

        // Access token exists and not beyond expiration.
        $valid_session = true; // $this->session->has('access_token') && time() < $this->session->get('exp');
        $data = [];

        // Get the user info.
        if ($valid_session) {
            $data = $this->openIDClient->get_user_info();
        }

        // Check if the subject issuer is valid for this authentication method.
        $is_valid_issuer = in_array($data['subject_issuer'], [ 'simulator', 'eherkenning' ], true);

        return print_r($data, true);
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
