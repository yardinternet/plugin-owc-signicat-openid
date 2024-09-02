import apiFetch from '@wordpress/api-fetch';
import '../css/modal.css';

class OWC_Signicat_OIDC_Modal {
	second = 1000;
	minute = 60 * this.second;

	constructor(settings) {
		const { sessionTTL } = settings;
		this.timeSessionShouldEnd = Number(sessionTTL) * this.minute;
	}

	init() {
		const modalWrapperId = 'owc-signicat-openid-modal-wrapper';

		this.modalOpenClass = 'show';
		this.lastActivity = new Date().getTime();

		this.modalEl = document.getElementById(modalWrapperId);

		if (!this.modalEl) {
			return;
		}

		this.registerEventHandlers();
		this.initTimer();
	}

	registerEventHandlers() {
		document.addEventListener('mousemove', () => this.updateLastActivity());
		document.addEventListener('keydown', () => this.updateLastActivity());
	}

	initTimer() {
		setInterval(() => this.checkSessionStatus(), this.second);
	}

	checkSessionStatus = () => {
		const inactivity = Date.now() - this.lastActivity;

		console.log(inactivity, this.timeSessionShouldEnd);

		if (inactivity >= this.timeSessionShouldEnd) {
			this.toggleModal();
		}
	};

	toggleModal() {
		this.logout();
		this.modalEl.classList.add(this.modalOpenClass);
		this.modalEl.setAttribute('aria-hidden', 'false');
	}

	updateLastActivity = () => {
		this.lastActivity = Date.now();
	};

	logout = () => {
		apiFetch({
			path: 'owc-signicat-openid/v1/revoke',
		});
	};
}

new OWC_Signicat_OIDC_Modal(window.owcSignicatOIDCModalSettings).init();
