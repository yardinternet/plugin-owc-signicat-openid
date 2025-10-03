<?php

/**
 * OpenID provider.
 *
 * @package OWC_Signicat_OpenID
 *
 * @author  Yard | Digital Agency
 *
 * @since   0.0.1
 */

declare(strict_types=1);

namespace OWCSignicatOpenID\Services;

use Exception;
use Facile\OpenIDClient\Client\ClientInterface;
use Facile\OpenIDClient\Exception\OAuth2Exception;
use Facile\OpenIDClient\Exception\RemoteException;
use Facile\OpenIDClient\Service\AuthorizationService;
use Facile\OpenIDClient\Service\Builder\IntrospectionServiceBuilder;
use Facile\OpenIDClient\Service\Builder\RevocationServiceBuilder;
use Facile\OpenIDClient\Service\Builder\UserInfoServiceBuilder;
use Facile\OpenIDClient\Token\TokenSet;
use OWCSignicatOpenID\ContainerManager;
use OWCSignicatOpenID\IdentityProvider;
use OWCSignicatOpenID\Interfaces\Services\IdentityProviderServiceInterface;
use OWCSignicatOpenID\Interfaces\Services\OpenIDServiceInterface;
use OWCSignicatOpenID\Interfaces\Services\SettingsServiceInterface;
use OWC\IdpUserData\UserDataInterface;
use Odan\Session\SessionInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use WP_Error;

use function Facile\OpenIDClient\parse_callback_params;

class OpenIDService extends Service implements OpenIDServiceInterface
{
	protected ClientInterface $client;
	protected AuthorizationService $authorizationService;
	protected SessionInterface $session;
	protected SettingsServiceInterface $settings;
	protected IdentityProviderServiceInterface $identityProviderService;

	public function __construct(
		ClientInterface $client,
		AuthorizationService $authorizationService,
		SessionInterface $session,
		SettingsServiceInterface $settings,
		IdentityProviderServiceInterface $identityProviderService
	) {
		$this->client                  = $client;
		$this->authorizationService    = $authorizationService;
		$this->session                 = $session;
		$this->settings                = $settings;
		$this->identityProviderService = $identityProviderService;

		foreach ($this->getEnabledIdentityProviders() as $identityProvider) {
			add_filter( 'owc_' . $identityProvider->getSlug() . '_is_logged_in', fn (bool $isLoggedIn ): bool => $this->isUserLoggedIn( $isLoggedIn, $identityProvider->getSlug() ) );
			add_filter( 'owc_' . $identityProvider->getSlug() . '_userdata', fn (?UserDataInterface $userData ): ?UserDataInterface => $this->retrieveUserInfo( $userData, $identityProvider->getSlug() ) );
		}
	}

	public function getScopesSupported(): ?array
	{
		return $this->client->getIssuer()->getMetadata()->getScopesSupported();
	}

	public function getEnabledIdentityProviders(): array
	{
		return $this->identityProviderService->getEnabledIdentityProviders();
	}

	public function retrieveUserInfo(?UserDataInterface $userInfo, string $idpSlug ): ?UserDataInterface
	{
		$idp = $this->identityProviderService->getIdentityProvider( $idpSlug );
		if (null === $idp) {
			return $userInfo;
		}

		if ($this->hasActiveSession( $idp )) {
			$userDataClass = $idp->getUserDataClass();
			$userInfo      = new $userDataClass( $this->getUserInfo( $idp ), $idp->getMapping() );
		}

		return $userInfo;
	}

	public function isUserLoggedIn(bool $isUserLoggedIn, string $idpSlug ): bool
	{
		$idp = $this->identityProviderService->getIdentityProvider( $idpSlug );
		if (null === $idp) {
			return $isUserLoggedIn;
		}

		if ($this->hasActiveSession( $idp )) {
			$isUserLoggedIn = true;
		}

		return $isUserLoggedIn;
	}

	public function getLoginUrl(IdentityProvider $identityProvider, ?string $redirectUrl = null, ?string $refererUrl = null, array $selectedIdpScopes = array() ): string
	{
		$args = array_filter(
			array(
				'idp'         => $identityProvider->getSlug(),
				'redirectUrl' => $redirectUrl,
				'refererUrl'  => $refererUrl,
				'idpScopes'   => $selectedIdpScopes ? implode( ' ', $selectedIdpScopes ) : null,
			)
		);

		return add_query_arg(
			$args,
			get_site_url( null, $this->settings->getSetting( 'path_login' ) )
		);
	}

	public function getLogoutUrl(?IdentityProvider $identityProvider = null, ?string $redirectUrl = null, ?string $refererUrl = null ): string
	{
		$args = array_filter(
			array(
				'idp'         => $identityProvider ? $identityProvider->getSlug() : null,
				'redirectUrl' => $redirectUrl ? rawurlencode( $redirectUrl ) : null,
				'refererUrl'  => $refererUrl ? rawurlencode( $refererUrl ) : null,
			)
		);

		return add_query_arg(
			$args,
			get_site_url( null, $this->settings->getSetting( 'path_logout' ) )
		);
	}

	public function authenticate(IdentityProvider $identityProvider, ?string $redirectUrl, ?string $refererUrl = null ): void
	{
		$stateID = $this->saveState(
			array(
				'identityProvider' => $identityProvider,
				'redirectUrl'      => $redirectUrl,
				'refererUrl'       => $refererUrl ?? wp_get_referer(),
			)
		);

		$simulatorEnabled = (bool) $this->settings->getSetting( 'enable_simulator' );

		$scope     = array( 'openid' );
		$acrValues = array();

		if ($this->isLegacyImplementation()) {
			$scope[] = $simulatorEnabled ? 'idp_scoping:simulator' : $identityProvider->getScope();
		} else {
			$acrValues[] = $simulatorEnabled ? 'idp:simulator' : 'idp:' . $identityProvider->getSlug();
		}

		$scope = array_merge( $scope, $identityProvider->getIdpScopes() );

		$params = array_filter(
			array(
				'scope'      => implode( ' ', array_unique( array_filter( $scope ) ) ),
				'state'      => $stateID,
				'acr_values' => implode( ',', $acrValues ),
			)
		);

		$redirect_authorization_uri = $this->authorizationService->getAuthorizationUri( $this->client, $params );

		header( 'Location: ' . $redirect_authorization_uri );
		exit();
	}

	public function isLegacyImplementation(): bool
	{
		return strpos( $this->settings->getSetting( 'configuration_url' ), 'broker/sp/oidc/' ) !== false;
	}

	public function redirectToLogout(IdentityProvider $identityProvider, string $redirectUrl, ?string $refererUrl = null )
	{
	}

	// public function logout(IdentityProvider $identityProvider, string $redirectUrl): void
	// {
	//     $stateID = bin2hex(random_bytes(12));

	//     $logoutUrl = $this->client->getIssuer()->getMetadata()->get('');
	// }

	public function handleCallback(ServerRequestInterface $server_request ): void
	{
		$rawCallbackParams = parse_callback_params( $server_request );
		$stateId           = sanitize_key( $rawCallbackParams['state'] ?? '' );

		if (empty( $stateId )) {
			wp_safe_redirect( get_site_url() );
			exit();
		}

		$state = $this->popState( $stateId );

		try {
			$callback_params = $this->authorizationService->getCallbackParams( $server_request, $this->client );
		} catch (OAuth2Exception $exception) {
			$this->maybeStartSession();
			$this->session->getFlash()->add( $exception->getError(), ContainerManager::getContainer()->get( 'idps_errors' )[ $exception->getError() ] ?? $exception->getDescription() );
			$this->session->save();
			wp_safe_redirect( $state['refererUrl'] ?? get_site_url() );
			exit;
		}

		$this->maybeStartSession();

		$tokenSet = $this->authorizationService->callback( $this->client, $callback_params );
		if (null === $tokenSet->getIdToken()) {
			throw new RuntimeException( 'Unauthorized' );
		}
		$identityProvider = $state['identityProvider'];

		$this->setIdpTokenSet( $identityProvider, $tokenSet );
		$this->session->save();

		wp_safe_redirect( $state['redirectUrl'] );
		exit;
	}

	/**
	 * @return WP_Error|bool
	 */
	public function refresh(IdentityProvider $identityProvider )
	{
		$this->maybeStartSession();

		if ( ! $this->hasIdpTokenSet( $identityProvider ) || null === $this->getIdpTokenSet( $identityProvider )->getRefreshToken()) {
			return new WP_Error( 'missing_refresh_token', 'Missing refresh token' );
		}
		if ($this->hasActiveSession( $identityProvider )) {
			return true;
		}

		$tokenSet = $this->authorizationService->refresh( $this->client, $this->getIdpTokenSet( $identityProvider )->getRefreshToken() );
		$this->setIdpTokenSet( $identityProvider, $tokenSet );

		// $this->session->save();
		return true;
	}

	public function getUserInfo(IdentityProvider $identityProvider ): array
	{
		$this->maybeStartSession();
		if ( ! $this->hasActiveSession( $identityProvider )) {
			return array();
		}
		$userInfoService = ( new UserInfoServiceBuilder() )->build();

		return $userInfoService->getUserInfo( $this->client, $this->getIdpTokenSet( ( $identityProvider ) ) );
	}

	public function revoke(IdentityProvider $identityProvider ): void
	{
		$this->maybeStartSession();
		if ( ! $this->hasIdpTokenSet( $identityProvider )) {
			throw new RuntimeException( 'You are not logged in' );
		}
		$revocationService = ( new RevocationServiceBuilder() )->build();
		$revocationService->revoke( $this->client, $this->getIdpTokenSet( $identityProvider )->getAccessToken() );
		$this->removeIdpTokenSet( $identityProvider );
	}

	public function introspect(IdentityProvider $identityProvider ): array
	{
		if ( ! $this->hasIdpTokenSet( $identityProvider )) {
			return array();
		}
		$introspectionService = ( new IntrospectionServiceBuilder() )->build();

		try {
			return $introspectionService->introspect( $this->client, $this->getIdpTokenSet( $identityProvider )->getAccessToken() );
		} catch (Exception | RemoteException $e) {
			return array();
		}
	}

	public function hasActiveSession(IdentityProvider $identityProvider ): bool
	{
		$this->maybeStartSession();
		$introspect = $this->introspect( $identityProvider );

		return ! empty( $introspect['active'] );
	}

	public function flashErrors(): array
	{
		$errors = $this->session->getFlash()->all();
		$this->session->save();

		return $errors;
	}

	public function setFlashError(string $error, string $description ): void
	{
		$this->session->getFlash()->add( $error, $description );
	}

	private function maybeStartSession()
	{
		if ( ! $this->session->isStarted() && ! headers_sent()) {
			$this->session->start();
		} elseif (count( $this->session->all() ) === 0 && $this->isNativeSessionValid()) {
			// Replace the current session with an existing session already started by another plugin.
			$this->session->replace( $_SESSION );
		}
	}

	private function isNativeSessionValid(): bool
	{
		if (session_status() !== PHP_SESSION_ACTIVE) {
			return false;
		}

		return is_array( $_SESSION ) && 0 < count( $_SESSION );
	}

	private function saveState(array $state ): string
	{
		$stateID = bin2hex( random_bytes( 12 ) );
		$this->maybeStartSession();
		$this->session->set( $stateID, $state );
		$this->session->save();

		return $stateID;
	}

	private function popState(string $stateID ): array
	{
		$this->maybeStartSession();
		if ( ! $this->session->has( $stateID )) {
			throw new RuntimeException( 'State not found' );
		}

		$state = $this->session->get( $stateID );
		$this->session->remove( $stateID );

		return $state;
	}

	private function hasIdpTokenSet(IdentityProvider $identityProvider ): bool
	{
		return $this->session->has( 'owc_openid_' . $identityProvider->getSlug() ) && is_a( $this->session->get( 'owc_openid_' . $identityProvider->getSlug() ), TokenSet::class );
	}

	private function getIdpTokenSet(IdentityProvider $identityProvider ): ?TokenSet
	{
		return $this->session->get( 'owc_openid_' . $identityProvider->getSlug() );
	}

	private function setIdpTokenSet(IdentityProvider $identityProvider, TokenSet $tokenSet )
	{
		$this->session->set( 'owc_openid_' . $identityProvider->getSlug(), $tokenSet );
	}

	private function removeIdpTokenSet(IdentityProvider $identityProvider )
	{
		$this->session->remove( 'owc_openid_' . $identityProvider->getSlug() );
	}
}
