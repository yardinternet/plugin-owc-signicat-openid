import '../css/front.css';

document.addEventListener('DOMContentLoaded', () => {
	const countdown = document.getElementById('js-sopenid-countdown');
	const timer = document.getElementById('js-sopenid-timer');
	const logout = document.getElementById('js-sopenid-logout');

	/**
	 * Countdown.
	 */
	if (countdown) {
		let currentTime = new Date(timer.dataset.current).getTime();
		const expiryTime = timer.dataset.expiry;
		const logoutUrl = logout.dataset.action;

		const interval = setInterval(() => {
			const distance = new Date(expiryTime).getTime() - currentTime;

			const days = Math.floor(distance / (1000 * 60 * 60 * 24));
			const hours = Math.floor(
				(distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60)
			);
			const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
			const seconds = Math.floor((distance % (1000 * 60)) / 1000);

			currentTime += 1000;

			timer.innerHTML = `${days} dagen ${hours} uren ${minutes} minuten ${seconds} seconden`;

			if (distance < 0) {
				clearInterval(interval);
				timer.innerHTML = 'Expired';

				return (window.location.href = logoutUrl);
			}
		}, 1000);
	}

	/**
	 * Logout.
	 */
	if (logout) {
		logout.addEventListener('click', (e) => {
			const logoutUrl = e.target.dataset.action;

			return (window.location.href = logoutUrl);
		});
	}
});
