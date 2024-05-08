import apiFetch from '@wordpress/api-fetch';
import '../css/modal.css';

class OWC_Signicat_OIDC_Modal {
	constructor(settings) {
		this.second = 1000;
		this.minute = 60 * this.second;

		this.sessionShouldEnd = settings.sessionTTL * this.minute;
		this.modalShouldOpen = this.sessionShouldEnd - this.minute;

		this.refreshUrl = settings.refreshUrl;
		this.logoutUrl = settings.logoutUrl;
	}

	init() {
		const modalWrapperId = 'owc-signicat-openid-modal-wrapper';
		const refreshButtonId = 'owc-signicat-openid-refresh';
		const logoutButtonId = 'owc-signicat-openid-logout';

		this.modalOpenClass = 'show';
		this.modalIsOpen = false;
		this.lastActivity = new Date();
		this.lastActivityIsUpdated = false;

		this.modalEl = document.getElementById(modalWrapperId);
		this.refreshButtonEl = document.getElementById(refreshButtonId);
		this.logoutButtonEl = document.getElementById(logoutButtonId);

		if (!this.modalEl || !this.logoutButtonEl || !this.refreshButtonEl) {
			return;
		}

		this.registerEventHandlers();
		this.initTimer();
	}

	registerEventHandlers() {
		this.refreshButtonEl.addEventListener('click', (e) =>
			this.sessionResume(e)
		);
		this.refreshButtonEl.addEventListener('keydown', (e) =>
			this.a11yClick(e)
		);
		this.logoutButtonEl.addEventListener('click', (e) =>
			this.sessionEnd(e)
		);
		this.logoutButtonEl.addEventListener('keydown', (e) =>
			this.a11yClick(e)
		);
		document.addEventListener('mousemove', () => this.updateLastActivity());
		document.addEventListener('keydown', () => this.updateLastActivity());
	}

	initTimer() {
		setInterval(this.checkSessionStatus, this.second);
		setInterval(this.keepSessionAlive, this.minute);
	}

	checkSessionStatus = () => {
		let inactivity = new Date().valueOf() - this.lastActivity;

		if (inactivity > this.modalShouldOpen && !this.modalIsOpen) {
			this.toggleModal(true);
		}

		if (inactivity > this.sessionShouldEnd) {
			this.logout();
		}
	};

	sessionResume() {
		this.toggleModal(false);
		this.keepSessionAlive();
	}

	sessionEnd() {
		this.toggleModal(false);
		this.logout();
	}

	toggleModal(open) {
		if (open) {
			this.modalEl.classList.add(this.modalOpenClass);
			this.modalEl.setAttribute('aria-hidden', 'false');
		} else {
			this.modalEl.classList.remove(this.modalOpenClass);
			this.modalEl.setAttribute('aria-hidden', 'truez');
		}

		this.modalIsOpen = open;
	}

	updateLastActivity = () => {
		this.lastActivity = Date.now();
		this.lastActivityIsUpdated = true;
	};

	logout = () => {
		apiFetch({
			path: 'owc-signicat-openid/v1/revoke',
		}).then((res) => {
			console.log(res);
			window.location = this.logoutUrl;
		});
	};

	keepSessionAlive = () => {
		if (this.lastActivityIsUpdated) {
			apiFetch({
				path: 'owc-signicat-openid/v1/refresh',
			}).then((res) => {
				this.lastActivityIsUpdated = false;
			});
		}
	};

	/**
	 * Add keypress event to modal buttons.
	 *
	 * @param {Object} e
	 */
	a11yClick = (e) => {
		const SPACE_KEY = 32;

		if (e.type !== 'click' || e.type !== 'keypress') return false;
		if (e.type === 'keypress') {
			const code = e.charCode || e.keyCode;
			if (code !== SPACE_KEY) return false;
		}

		return true;
	};
}

new OWC_Signicat_OIDC_Modal(owcSignicatOIDCModalSettings).init();
