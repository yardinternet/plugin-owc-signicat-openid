<?php

declare(strict_types=1);

namespace OWCSignicatOpenID\Services;

use Exception;
use GuzzleHttp\Psr7\ServerRequest;
use OWCSignicatOpenID\IdentityProvider;
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
                $serverRequest = ServerRequest::fromGlobals();
                $queryParams = $serverRequest->getQueryParams();
                $idp = $queryParams['idp'] ?? '';
                $redirectUrl = $queryParams['redirectUrl'] ?? wp_get_referer();
                $refererUrl = $queryParams['refererUrl'] ?? wp_get_referer();
                $identityProvider = $this->identityProviderService->getIdentityProvider($idp);

                if ($identityProvider instanceof IdentityProvider && ! $this->openIDService->hasActiveSession($identityProvider)) {
                    $this->openIDService->authenticate($identityProvider, esc_url($redirectUrl), esc_url($refererUrl));
                } else {
                    wp_safe_redirect(esc_url($redirectUrl));
                    exit;
                }

                break;
            case $this->settings->getSetting('path_redirect'):
                $serverRequest = ServerRequest::fromGlobals();
                $this->openIDService->handleCallback($serverRequest);

                break;

            case $this->settings->getSetting('path_logout'):
                $serverRequest = ServerRequest::fromGlobals();
                $queryParams = $serverRequest->getQueryParams();
                $idp = $queryParams['idp'] ?? '';
                $redirectUrl = $queryParams['redirectUrl'] ?? ''; // Maybe always redirect to home?
                $refererUrl = $queryParams['refererUrl'] ?? wp_get_referer(); // Do we need to validate if the referer is from this site?

                try {
                    $identityProvider = $this->identityProviderService->getIdentityProvider($idp);

                    if (! $identityProvider instanceof IdentityProvider) {
                        throw new Exception();
                    }

                    $this->openIDService->revoke($identityProvider);
                } catch (Exception $e) {
                    // Fail gracefully.
                } finally {
                    wp_safe_redirect(esc_url($redirectUrl ?: get_site_url()));
                    exit;
                }
        }
    }

    public function registerRestRoutes(): void
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

    public function revoke(): WP_REST_Response
    {
        foreach ($this->identityProviderService->getEnabledIdentityProviders() as $identityProvider) {
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

    public function refresh(): WP_REST_Response
    {
        foreach ($this->identityProviderService->getEnabledIdentityProviders() as $identityProvider) {
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
