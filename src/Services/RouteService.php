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
use Psr\Log\LoggerInterface;
use WP_Http;
use WP_REST_Response;
use WP_REST_Server;

class RouteService extends Service implements RouteServiceInterface
{
	private const REST_NAMESPACE = 'owc-signicat-openid/v1';

	protected SettingsServiceInterface $settings;
	protected OpenIDServiceInterface $openIDService;
	protected IdentityProviderServiceInterface $identityProviderService;
	protected LoggerInterface $logger;

	public function __construct(
		SettingsServiceInterface $settings,
		OpenIDServiceInterface $openIDService,
		IdentityProviderServiceInterface $identityProviderService,
		LoggerInterface $logger
	) {
		$this->settings                = $settings;
		$this->openIDService           = $openIDService;
		$this->identityProviderService = $identityProviderService;
		$this->logger                  = $logger;
	}

	public function register()
	{
		add_action( 'parse_request', $this->registerRoutes( ... ) );
		add_action( 'rest_api_init', $this->registerRestRoutes( ... ) );
	}

	public function registerRoutes(\WP $wp ): void
	{
		switch ($wp->request) {
			case $this->settings->getSetting( 'path_login' ):
				$serverRequest    = ServerRequest::fromGlobals();
				$queryParams      = $serverRequest->getQueryParams();
				$idp              = $queryParams['idp'] ?? '';
				$idpScopes        = $queryParams['idpScopes'] ?? '';
				$redirectUrl      = $queryParams['redirectUrl'] ?? ( wp_get_referer() ?: '' );
				$refererUrl       = $queryParams['refererUrl'] ?? ( wp_get_referer() ?: '' );
				$identityProvider = $this->identityProviderService->getIdentityProvider( $idp );

				if ($identityProvider instanceof IdentityProvider && ! $this->openIDService->hasActiveSession( $identityProvider )) {
					if (strlen( $idpScopes ) > 0) {
						$identityProvider->addIdpScopes( explode( ' ', $idpScopes ) ?: array() );
					}

					$this->openIDService->authenticate( $identityProvider, esc_url( $redirectUrl ), esc_url( $refererUrl ) );
				} else {
					wp_safe_redirect( esc_url( $redirectUrl ) );
					exit;
				}

				break;
			case $this->settings->getSetting( 'path_redirect' ):
				$serverRequest = ServerRequest::fromGlobals();
				$this->openIDService->handleCallback( $serverRequest );

				break;

			case $this->settings->getSetting( 'path_logout' ):
				$serverRequest = ServerRequest::fromGlobals();
				$queryParams   = $serverRequest->getQueryParams();
				$idp           = $queryParams['idp'] ?? '';
				$redirectUrl   = isset( $queryParams['redirectUrl'] ) ? rawurldecode( $queryParams['redirectUrl'] ) : home_url(); // Maybe always redirect to home?
				$refererUrl    = isset( $queryParams['refererUrl'] ) ? rawurldecode( $queryParams['refererUrl'] ) : wp_get_referer(); // Do we need to validate if the referer is from this site?

				try {
					$identityProvider = $this->identityProviderService->getIdentityProvider( $idp );

					if ( ! $identityProvider instanceof IdentityProvider) {
						throw new Exception();
					}

					$logoutUrl = $this->openIDService->revoke( $identityProvider );

					wp_redirect( $logoutUrl );
					exit;
				} catch (Exception $e) {
					$this->logger->error('Failed to revoke tokens during logout', array(
						'exception' => $e,
					));
				} finally {
					wp_safe_redirect( esc_url( $redirectUrl ) );
					exit;
				}
		}
	}

	public function registerRestRoutes(): void
	{
		// TODO: rest routes naar eigen class?
		register_rest_route(
			self::REST_NAMESPACE,
			'refresh',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'refresh' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'revoke',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'revoke' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	public function revoke(): WP_REST_Response
	{
		foreach ($this->identityProviderService->getEnabledIdentityProviders() as $identityProvider) {
			if (! $this->openIDService->hasActiveSession($identityProvider)) {
				continue;
			}

			try {
				$logoutUrl = $this->openIDService->revoke($identityProvider);
			} catch (Exception $e) {
				$this->logger->error('Failed to revoke tokens during REST logout', array(
					'exception' => $e,
				));

				return new WP_REST_Response([
					'message' => 'Failed to revoke tokens',
				], WP_Http::INTERNAL_SERVER_ERROR);
			}

			return new WP_REST_Response([
				'message'   => 'Tokens revoked',
				'logoutUrl' => $logoutUrl,
			], WP_Http::OK);
		}

		return new WP_REST_Response([
			'message' => 'No active session',
		], WP_Http::OK);
	}

	public function refresh(): WP_REST_Response
	{
		foreach ($this->identityProviderService->getEnabledIdentityProviders() as $identityProvider) {
			if ($this->openIDService->hasActiveSession( $identityProvider )) {
				$result = $this->openIDService->refresh( $identityProvider );

				if (is_wp_error( $result )) {
					return new WP_REST_Response(
						array(
							'message' => $result->get_error_message(),
						),
						WP_Http::BAD_REQUEST
					);
				}
			}
		}

		return new WP_REST_Response(
			array(
				'message' => 'Session refreshed',
			),
			WP_Http::OK
		);
	}
}
