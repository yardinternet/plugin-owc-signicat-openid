<?php

declare(strict_types=1);

namespace OWCSignicatOpenID\Services;

use GuzzleHttp\Psr7\ServerRequest;
use OWCSignicatOpenID\Interfaces\Services\IdentityProviderServiceInterface;
use OWCSignicatOpenID\Interfaces\Services\OpenIDServiceInterface;
use OWCSignicatOpenID\Interfaces\Services\RouteServiceInterface;
use OWCSignicatOpenID\Interfaces\Services\SettingsServiceInterface;
use WP_Http;
use WP_REST_Response;
use WP_REST_Server;

class RouteService extends Service implements RouteServiceInterface
{

    private const REST_NAMESPACE = 'owc-signicat-openid/v1';

    protected SettingsServiceInterface $settings;
    protected OpenIDServiceInterface $openIDService;
    protected IdentityProviderServiceInterface $identityProviderService;

    public function __construct(
        SettingsServiceInterface $settings,
        OpenIDServiceInterface $openIDService,
        IdentityProviderServiceInterface $identityProviderService
    ) {
        $this->settings = $settings;
        $this->openIDService = $openIDService;
        $this->identityProviderService = $identityProviderService;
    }

    public function register()
    {
        add_action('parse_request', [$this, 'registerRoutes']);
        add_action('rest_api_init', [$this, 'registerRestRoutes']);
    }

    public function registerRoutes(\WP $wp): void
    {
        switch ($wp->request) {
            case $this->settings->getSetting('path_login'):
                $server_request = ServerRequest::fromGlobals();
                $queryParams = $server_request->getQueryParams();
                $idp = $queryParams['idp'] ?? '';
                $redirectUrl = $queryParams['redirectUrl'] ?? wp_get_referer();
                $refererUrl = $queryParams['refererUrl'] ?? wp_get_referer();
                $identityProvider = $this->identityProviderService->getIdentityProvider($idp);
                //TODO: check of idp gevonden is

                if (! $this->openIDService->hasActiveSession($identityProvider)) {
                    $this->openIDService->authenticate($identityProvider, esc_url($redirectUrl), esc_url($refererUrl));
                } else {
                    wp_safe_redirect(esc_url($redirectUrl));
                    exit;
                }

                break;
            case $this->settings->getSetting('path_redirect'):
                $server_request = ServerRequest::fromGlobals();
                $this->openIDService->handleCallback($server_request);

                break;

            case $this->settings->getSetting('path_logout'):
                $server_request = ServerRequest::fromGlobals();
                $queryParams = $server_request->getQueryParams();
                $idp = $queryParams['idp'] ?? '';
                $identityProvider = $this->identityProviderService->getIdentityProvider($idp);
                //TODO: Add IDP parameter or rewrite rule
                //FIXME: dit is eigenlijk revoke (ipv logout)
                $this->openIDService->revoke($identityProvider);

                break;
        }
    }

    public function registerRestRoutes()
    {
        //TODO: rest routes naar eigen class?
        register_rest_route(
            self::REST_NAMESPACE,
            'refresh',
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [ $this, 'refresh'],
                'permission_callback' => '__return_true',
            ]
        );

        register_rest_route(
            self::REST_NAMESPACE,
            'revoke',
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [ $this, 'revoke'],
                'permission_callback' => '__return_true',
            ]
        );
    }

    public function revoke()
    {
        foreach($this->identityProviderService->getEnabledIdentityProviders() as $identityProvider) {
            if ($this->openIDService->hasActiveSession($identityProvider)) {
                $result = $this->openIDService->revoke($identityProvider);
            }
        }

        return new WP_REST_Response(
            [
                'message' => 'Tokens revoked',
            ],
            WP_Http::OK
        );
    }

    public function refresh()
    {
        foreach($this->identityProviderService->getEnabledIdentityProviders() as $identityProvider) {
            if ($this->openIDService->hasActiveSession($identityProvider)) {
                $result = $this->openIDService->refresh($identityProvider);
                if (is_wp_error($result)) {
                    return $result;
                }
            }
        }

        return new WP_REST_Response(
            [
                'message' => 'Session refreshed',
            ],
            WP_Http::OK
        );
    }
}
