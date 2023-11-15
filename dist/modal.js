/******/ (() => { // webpackBootstrap
var __webpack_exports__ = {};
/*!*******************************!*\
  !*** ./resources/js/modal.js ***!
  \*******************************/
/**
 * Initialize the countdown.
 *
 * @param number sessionTTL
 * @param string resumeUri
 * @param string logoutUri
 */
function initCountdown(sessionTTL, resumeUri, logoutUri) {
  var second = 1000;
  var minute = 60 * second;
  var sessionTTLInSeconds = sessionTTL * second;

  // Elements
  var elemModal = 'js-owc-signicat-openid-popup';
  var elemResumeHandler = 'js-owc-signicat-resume-handler';
  var elemLogoutHandler = 'js-owc-signicat-logout-handler';

  // Classes
  var classModalShow = 'owc-signicat-openid-popup-show';

  // Open the modal 1 minute before the session ends.
  var modalShouldOpen = sessionTTLInSeconds / minute;

  /**
   * Show or hide the modal.
   */
  function toggleModal(show) {
    var modal = document.getElementById(elemModal);
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
    var resume = document.getElementById(elemResumeHandler);
    var abort = document.getElementById(elemLogoutHandler);
    if (resume) {
      resume.addEventListener('click', sessionResume);
      resume.addEventListener('keydown', a11yClick);
    }
    if (abort) {
      abort.addEventListener('click', logout);
      abort.addEventListener('keydown', a11yClick);
    }
    document.addEventListener('keydown', function (e) {
      var ESCAPE_KEY = 27;
      var modal = document.getElementById(elemModal);
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
  function sessionResume() {
    window.location = resumeUri;
  }
  function logout() {
    window.location = logoutUri;
  }
  function a11yClick(e) {
    var SPACE_KEY = 32;
    if (e.type !== 'click' || e.type !== 'keypress') return false;
    if (e.type === 'keypress') {
      var code = e.charCode || e.keyCode;
      if (code !== SPACE_KEY) return false;
    }
    return true;
  }

  // Initialize Countdown
  document.addEventListener('DOMContentLoaded', function () {
    initTimer();
    registerEventListeners();
  });
}
var sessionTTL = sopenidSettings.exp;
var resumeUri = sopenidSettings.resume_uri;
var logoutUri = sopenidSettings.logout_uri;
if (sessionTTL > 0) {
  initCountdown(sessionTTL, resumeUri, logoutUri);
}
/******/ })()
;
//# sourceMappingURL=modal.js.map