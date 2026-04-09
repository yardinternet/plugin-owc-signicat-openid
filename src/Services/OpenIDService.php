<?php

/**
 * OpenID provider.
 *
 * @package OWC_Signicat_OpenID
 * @author  Yard | Digital Agency
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
use Facile\OpenIDClient\Service\RevocationService;
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
	private const DIGID_GENERIC_ERROR = 'Inloggen bij deze organisatie is niet gelukt. Probeert u het later nog een keer. Lukt het nog steeds niet? Log in bij Mijn DigiD. Zo controleert u of uw DigiD goed werkt. Mogelijk is er een storing bij de organisatie waar u inlogt.';
	private const EHERKENNING_GENERIC_ERROR = 'Inloggen bij deze organisatie is niet gelukt. Probeert u het later nog een keer. Mogelijk is er een storing bij de organisatie waar u inlogt.';

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

			// Filters for the partner/second login session (slot '2'). Mirrors the primary filters above, scoped to slot '2'.
			add_filter( 'owc_' . $identityProvider->getSlug() . '_is_logged_in_2', fn (bool $isLoggedIn ): bool => $this->isUserLoggedIn( $isLoggedIn, $identityProvider->getSlug(), '2' ) );
			add_filter( 'owc_' . $identityProvider->getSlug() . '_userdata_2', fn (?UserDataInterface $userData ): ?UserDataInterface => $this->retrieveUserInfo( $userData, $identityProvider->getSlug(), '2' ) );
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

	public function retrieveUserInfo(?UserDataInterface $userInfo, string $idpSlug, string $slot = '' ): ?UserDataInterface
	{
		$idp = $this->identityProviderService->getIdentityProvider( $idpSlug );

		if (null === $idp) {
			return $userInfo;
		}

		if ($this->hasActiveSession( $idp, $slot )) {
			$userDataClass = $idp->getUserDataClass();
			$userInfo      = new $userDataClass( $this->getUserInfo( $idp, $slot ), $idp->getMapping() );
		}

		return $userInfo;
	}

	public function isUserLoggedIn(bool $isUserLoggedIn, string $idpSlug, string $slot = '' ): bool
	{
		$idp = $this->identityProviderService->getIdentityProvider( $idpSlug );
		if (null === $idp) {
			return $isUserLoggedIn;
		}

		if ($this->hasActiveSession( $idp, $slot )) {
			$isUserLoggedIn = true;
		}

		return $isUserLoggedIn;
	}

	public function getLoginUrl(IdentityProvider $identityProvider, ?string $redirectUrl = null, ?string $refererUrl = null, array $selectedIdpScopes = array(), string $slot = '' ): string
	{
		$args = array_filter(
			array(
				'idp'         => $identityProvider->getSlug(),
				'redirectUrl' => $redirectUrl,
				'refererUrl'  => $refererUrl,
				'idpScopes'   => implode(
					' ',
					array_unique(
						array_merge( $identityProvider->getSlug() === 'digid' ? array( 'offline_access' ) : array(), $selectedIdpScopes )
					)
				),
				'slot'        => $slot,
			)
		);

		return add_query_arg(
			$args,
			home_url( $this->settings->getSetting( 'path_login' ) )
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
			home_url( $this->settings->getSetting( 'path_logout' ) )
		);
	}

	public function authenticate(IdentityProvider $identityProvider, ?string $redirectUrl, ?string $refererUrl = null, string $slot = '' ): void
	{
		$stateID = $this->saveState(
			array(
				'identityProvider' => $identityProvider,
				'redirectUrl'      => $redirectUrl,
				'refererUrl'       => $refererUrl ?? wp_get_referer(),
				'slot'             => $slot,
			)
		);

		$simulatorEnabled = (bool) $this->settings->getSetting( 'enable_simulator' );

		$scope     = array( 'openid' );
		$acrValues = array();

		if ($this->isLegacyImplementation()) {
			$scope[] = $simulatorEnabled ? 'idp_scoping:simulator' : $identityProvider->getScope();
		} else {
			$acrValues[] = $simulatorEnabled ? 'idp:simulator' : 'idp:' . $identityProvider->getSlug();
			$scope[]     = 'nin';
		}

		$scope = array_merge( $scope, $identityProvider->getIdpScopes() );

		$params = array_filter(
			array(
				'scope'      => implode( ' ', array_unique( array_filter( $scope ) ) ),
				'state'      => $stateID,
				'acr_values' => implode( ',', $acrValues ),
				'prompt'     => 'login',
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
			wp_safe_redirect( home_url() );
			exit();
		}

		$state = $this->popState( $stateId );
		$identityProvider = $state['identityProvider'];
		$slot             = $state['slot'] ?? '';

		try {
			$callback_params = $this->authorizationService->getCallbackParams( $server_request, $this->client );
		} catch (OAuth2Exception $exception) {
			$this->maybeStartSession();
			$this->handleIdpsError($exception, $identityProvider);
			$this->session->save();
			wp_safe_redirect( $state['refererUrl'] ?? home_url() );
			exit;
		}

		$this->maybeStartSession();

		$tokenSet = $this->authorizationService->callback( $this->client, $callback_params );
		if (null === $tokenSet->getIdToken()) {
			throw new RuntimeException( 'Unauthorized' );
		}

		$this->setIdpTokenSet( $identityProvider, $tokenSet, $slot );
		$this->session->save();

		wp_safe_redirect( $state['redirectUrl'] );
		exit;
	}

	private function handleIdpsError(OAuth2Exception $exception, IdentityProvider $identityProvider): void
	{
		$allowed = ['AuthnFailed'];

		if (in_array($exception->getError(), $allowed, true)) {
			$errorText = ContainerManager::getContainer()->get( 'idps_errors' )[ $exception->getError() ] ?? $exception->getDescription();
		} elseif('IDP-3200' === $exception->getError()) {
			$errorText = __('The user cancelled the login process', 'owc-signicat-openid' );
		} elseif('digid' === $identityProvider->getSlug()) {
			$errorText = self::DIGID_GENERIC_ERROR; // Generic error message for all other errors, to avoid showing technical details to the user.
		} elseif('eherkenning' === $identityProvider->getSlug()) {
			$errorText = self::EHERKENNING_GENERIC_ERROR; // Generic error message for all other errors, to avoid showing technical details to the user.
		} else {
			$errorText = $exception->getDescription();
		}

		$this->session->getFlash()->add($identityProvider->getSlug(), $errorText);
	}

	/**
	 * Retrieves flash errors for a specific IDP.
	 *
	 * Flash messages are automatically removed after retrieval. Due to the hybrid
	 * session setup, the flash storage is explicitly cleared to prevent stale
	 * messages from reappearing on subsequent requests.
	 *
	 * @since 3.0.0
	 */
	public function flashErrorsByIdp(string $idp): array
	{
		$errors = $this->session->getFlash()->get($idp);

		if ( 0 === count($errors) ) {
			return array();
		}

		// Clear the flash messages for this IDP to avoid them persisting through the native session storage.
		$this->session->getFlash()->set($idp, array());
		$this->session->save();

		return $errors;
	}

	/**
	 * @return WP_Error|bool
	 */
	public function refresh(IdentityProvider $identityProvider, string $slot = '' )
	{
		$this->maybeStartSession();

		if ( ! $this->hasIdpTokenSet( $identityProvider, $slot ) || null === $this->getIdpTokenSet( $identityProvider, $slot )->getRefreshToken()) {
			return new WP_Error( 'missing_refresh_token', 'Missing refresh token' );
		}
		if ($this->hasActiveSession( $identityProvider, $slot )) {
			return true;
		}

		$tokenSet = $this->authorizationService->refresh( $this->client, $this->getIdpTokenSet( $identityProvider, $slot )->getRefreshToken() );
		$this->setIdpTokenSet( $identityProvider, $tokenSet, $slot );
		$this->session->save();

		return true;
	}

	public function getUserInfo(IdentityProvider $identityProvider, string $slot = '' ): array
	{
		$this->maybeStartSession();
		if ( ! $this->hasActiveSession( $identityProvider, $slot )) {
			return array();
		}

		$userInfoService = ( new UserInfoServiceBuilder() )->build();

		try {
			return $userInfoService->getUserInfo( $this->client, $this->getIdpTokenSet( $identityProvider, $slot ) );
		} catch (Exception $e) {
			return array();
		}
	}

	public function revoke(IdentityProvider $identityProvider, string $slot = '' ): string
	{
		$this->maybeStartSession();
		if ( ! $this->hasIdpTokenSet( $identityProvider, $slot )) {
			throw new RuntimeException( 'You are not logged in' );
		}

		$tokenSet = $this->getIdpTokenSet( $identityProvider, $slot );

		if ( ! $tokenSet instanceof TokenSet ) {
			throw new RuntimeException( 'Invalid token set' );
		}

		$revocationService = ( new RevocationServiceBuilder() )->build();

		try {
			$this->revokeAccessToken( $tokenSet, $revocationService );
			$this->revokeRefreshToken( $tokenSet, $revocationService );
		} catch (Exception $e) {
			// Ignore revocation errors to ensure local session cleanup still occurs.
		} finally {
			$this->removeIdpTokenSet( $identityProvider, $slot );
		}

		return $this->buildEndSessionUrl( $tokenSet );
	}

	private function revokeAccessToken( TokenSet $tokenSet, RevocationService $revocationService ): void
	{
		$accessToken = $tokenSet->getAccessToken();

		if ( ! is_string( $accessToken ) || '' === $accessToken ) {
			return;
		}

		$revocationService->revoke(
			$this->client,
			$accessToken,
			array(
				'token_type_hint' => 'access_token',
			)
		);
	}

	private function revokeRefreshToken( TokenSet $tokenSet, RevocationService $revocationService ): void
	{
		$refreshToken = $tokenSet->getRefreshToken();

		if ( ! is_string( $refreshToken ) || '' === $refreshToken ) {
			return;
		}

		$revocationService->revoke(
			$this->client,
			$refreshToken,
			array(
				'token_type_hint' => 'refresh_token',
			)
		);
	}

	private function buildEndSessionUrl( TokenSet $tokenSet ): string
	{
		$endSession = $this->client->getIssuer()->getMetadata()->get( 'end_session_endpoint' );

		if ( ! is_string( $endSession ) || '' === $endSession) {
			throw new RuntimeException( 'End session endpoint not configured for this identity provider' );
		}

		$idToken = $tokenSet->getIdToken();

		if ( ! is_string( $idToken ) || '' === $idToken ) {
			throw new RuntimeException( 'ID token is required for logout' );
		}

		$params = array(
			'id_token_hint'            => $idToken,
			'post_logout_redirect_uri' => home_url(),
		);

		$logoutUrl = esc_url_raw( $endSession . ( str_contains( $endSession, '?' ) ? '&' : '?' ) . http_build_query( $params ));

		return ( $logoutUrl );
	}

	public function introspect(IdentityProvider $identityProvider, string $slot = '' ): array
	{
		if ( ! $this->hasIdpTokenSet( $identityProvider, $slot )) {
			return array();
		}

		$introspectionService = ( new IntrospectionServiceBuilder() )->build();

		try {
			return $introspectionService->introspect( $this->client, $this->getIdpTokenSet( $identityProvider, $slot )->getAccessToken() );
		} catch (Exception | RemoteException $e) {
			return array();
		}
	}

	public function hasActiveSession(IdentityProvider $identityProvider, string $slot = '' ): bool
	{
		$this->maybeStartSession();
		$introspect = $this->introspect( $identityProvider, $slot );

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

			return;
		}

		if (count( $this->session->all() ) > 0) {
			return; // Storage is already populated.
		}

		if ( ! $this->isNativeSessionValid()) {
			return;
		}

		$configuredName = ContainerManager::getContainer()->get( 'session_options' )['name'] ?? 'OWC_Signicat_OpenID';

		if (session_name() === $configuredName) {
			// The running session is ours, sync the storage.
			$this->session->setValues( $_SESSION );
		} elseif ( ! headers_sent()) {
			// A different session is running. Close it and start our own so the correct token data is loaded.
			session_write_close();
			$this->session->start();
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
		$this->session->delete( $stateID );

		return $state;
	}

	private function getSessionKey(IdentityProvider $identityProvider, string $slot = '' ): string
	{
		$key = 'owc_openid_' . $identityProvider->getSlug();

		return $slot !== '' ? $key . '_' . $slot : $key;
	}

	private function hasIdpTokenSet(IdentityProvider $identityProvider, string $slot = '' ): bool
	{
		$key = $this->getSessionKey( $identityProvider, $slot );

		return $this->session->has( $key ) && is_a( $this->session->get( $key ), TokenSet::class );
	}

	private function getIdpTokenSet(IdentityProvider $identityProvider, string $slot = '' ): ?TokenSet
	{
		return $this->session->get( $this->getSessionKey( $identityProvider, $slot ) );
	}

	private function setIdpTokenSet(IdentityProvider $identityProvider, TokenSet $tokenSet, string $slot = '' ): void
	{
		$this->session->set( $this->getSessionKey( $identityProvider, $slot ), $tokenSet );
	}

	private function removeIdpTokenSet(IdentityProvider $identityProvider, string $slot = '' ): void
	{
		$this->session->delete( $this->getSessionKey( $identityProvider, $slot ) );
		$this->session->save();
	}
}
