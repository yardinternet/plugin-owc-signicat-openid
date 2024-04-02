<?php

declare(strict_types=1);

namespace OWCSignicatOpenID\Services;

use GuzzleHttp\Psr7\ServerRequest;
use Odan\Session\SessionInterface;
use OWCSignicatOpenID\Interfaces\Services\OpenIDServiceInterface;
use OWCSignicatOpenID\Interfaces\Services\RouteServiceInterface;
use OWCSignicatOpenID\Interfaces\Services\SettingsServiceInterface;

class RouteService extends Service implements RouteServiceInterface
{
    protected SessionInterface $session;
    protected SettingsServiceInterface $settings;
    protected OpenIDServiceInterface $open_id;


    public function __construct(SessionInterface $session, SettingsServiceInterface $settings, OpenIDServiceInterface $open_id)
    {
        $this->session = $session;
        $this->settings = $settings;
        $this->open_id = $open_id;
    }

    public function register()
    {
        add_action('parse_request', [$this, 'register_routes']);
    }

    public function register_routes(\WP $wp): void
    {
        switch ($wp->request) {
            case $this->settings->get_setting('path_login'):
                if (! $this->session->has('access_token')) {
                    $this->open_id->authenticate();
                } else {
                    wp_safe_redirect(home_url());
                    exit;
                }

                break;
            case $this->settings->get_setting('path_redirect'):
                $server_request = ServerRequest::fromGlobals();
                $this->open_id->handle_redirect($server_request);

                break;

            case $this->settings->get_setting('path_refresh'):
                $this->open_id->refresh();

                break;

            case $this->settings->get_setting('path_logout'):
                $this->open_id->logout();

                break;
        }
    }

}
