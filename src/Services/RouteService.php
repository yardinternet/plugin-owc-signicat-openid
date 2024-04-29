<?php

declare(strict_types=1);

namespace OWCSignicatOpenID\Services;

use GuzzleHttp\Psr7\ServerRequest;
use OWCSignicatOpenID\Interfaces\Services\OpenIDServiceInterface;
use OWCSignicatOpenID\Interfaces\Services\RouteServiceInterface;
use OWCSignicatOpenID\Interfaces\Services\SettingsServiceInterface;

class RouteService extends Service implements RouteServiceInterface
{
    protected SettingsServiceInterface $settings;
    protected OpenIDServiceInterface $open_id;

    public function __construct(SettingsServiceInterface $settings, OpenIDServiceInterface $open_id)
    {
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

                if (empty($this->open_id->introspect()['active'])) {
                    $server_request = ServerRequest::fromGlobals();
                    $query_params = $server_request->getQueryParams();
                    $idpScope = $query_params['idp'] ?? '';
                    $redirectUrl = $query_params['redirect_url'] ?? wp_get_referer();

                    $this->open_id->authenticate([$idpScope], esc_url($redirectUrl));
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
