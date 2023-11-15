import apiFetch from '@wordpress/api-fetch';

/**
 * Initialize the countdown.
 *
 * @param number sessionTTL
 * @param string refreshUri
 * @param string logoutUri
 */
function initCountdown(sessionTTL, refreshUri, logoutUri) {
	const second = 1000;
	const minute = 60 * second;
	const sessionTTLInSeconds = sessionTTL * second;

	console.log(refreshUri);

	// Elements.
	const elemModal = 'js-owc-signicat-openid-popup';
	const elemResumeHandler = 'js-owc-signicat-resume-handler';
	const elemLogoutHandler = 'js-owc-signicat-logout-handler';

	// Classes.
	const classModalShow = 'owc-signicat-openid-popup-show';

	// Open the modal 1 minute before the session ends.
	const modalShouldOpen = sessionTTLInSeconds / minute;

	/**
	 * Show or hide the modal.
	 */
	function toggleModal(show) {
		const modal = document.getElementById(elemModal);

		if (modal) {
			if (show) {
				modal.classList.add(classModalShow);
			} else {
				modal.classList.remove(classModalShow);
			}
		}
	}

	/**
	 * Register event listeners.
	 */
	function registerEventListeners() {
		const resume = document.getElementById(elemResumeHandler);
		const abort = document.getElementById(elemLogoutHandler);

		if (resume) {
			resume.addEventListener('click', sessionResume);
			resume.addEventListener('keydown', a11yClick);
		}

		if (abort) {
			abort.addEventListener('click', logout);
			abort.addEventListener('keydown', a11yClick);
		}

		document.addEventListener('keydown', (e) => {
			const ESCAPE_KEY = 27;
			const modal = document.getElementById(elemModal);

			if (e.keyCode === ESCAPE_KEY && modal.classList.contains(modalShow)) {
				logout();
			}
		});
	}

	/**
	 * Check session status every second.
	 */
	function initTimer() {
		setInterval(checkSessionStatus, second);
	}

	/**
	 * Check if the expiration modal should open or the user should be logged out.
	 */
	function checkSessionStatus() {
		console.log('test');
		if (Date.now() > modalShouldOpen) {
			toggleModal(true);
		}

		if (Date.now() > sessionTTLInSeconds) {
			logout();
		}
	}

	async function sessionResume() {
		try {
			const response = await apiFetch({
				url: refreshUri,
				method: 'GET',
				headers: {
					'Content-Type': 'application/json',
				},
			});

			// Handle success
			console.log(response);
		} catch (error) {
			// Handle error
			console.error('Error:', error);
		}
	}

	function logout() {
		window.location = logoutUri;
	}

	function a11yClick(e) {
		const SPACE_KEY = 32;

		if (e.type !== 'click' || e.type !== 'keypress') return false;
		if (e.type === 'keypress') {
			const code = e.charCode || e.keyCode;
			if (code !== SPACE_KEY) return false;
		}

		return true;
	}

	// Initialize Countdown
	document.addEventListener('DOMContentLoaded', () => {
		initTimer();
		registerEventListeners();
	});
}

const sessionTTL = Date.now() + 1000; // TODO: should be sopendIdSettings.exp
const refreshUri = sopenidSettings.refresh_uri;
const logoutUri = sopenidSettings.logout_uri;

if (sessionTTL > 0) {
	initCountdown(sessionTTL, refreshUri, logoutUri);
}
