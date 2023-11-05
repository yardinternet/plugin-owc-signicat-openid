/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./resources/css/front.css":
/*!*********************************!*\
  !*** ./resources/css/front.css ***!
  \*********************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry need to be wrapped in an IIFE because it need to be isolated against other modules in the chunk.
(() => {
/*!*******************************!*\
  !*** ./resources/js/front.js ***!
  \*******************************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _css_front_css__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../css/front.css */ "./resources/css/front.css");

document.addEventListener('DOMContentLoaded', function () {
  var countdown = document.getElementById('js-sopenid-countdown');
  var timer = document.getElementById('js-sopenid-timer');
  var logout = document.getElementById('js-sopenid-logout');

  /**
   * Countdown.
   */
  if (countdown) {
    var currentTime = new Date(timer.dataset.current).getTime();
    var expiryTime = timer.dataset.expiry;
    var logoutUrl = logout.dataset.action;
    var interval = setInterval(function () {
      var distance = new Date(expiryTime).getTime() - currentTime;
      var days = Math.floor(distance / (1000 * 60 * 60 * 24));
      var hours = Math.floor(distance % (1000 * 60 * 60 * 24) / (1000 * 60 * 60));
      var minutes = Math.floor(distance % (1000 * 60 * 60) / (1000 * 60));
      var seconds = Math.floor(distance % (1000 * 60) / 1000);
      currentTime += 1000;
      timer.innerHTML = "".concat(days, " dagen ").concat(hours, " uren ").concat(minutes, " minuten ").concat(seconds, " seconden");
      if (distance < 0) {
        clearInterval(interval);
        timer.innerHTML = 'Expired';
        return window.location.href = logoutUrl;
      }
    }, 1000);
  }

  /**
   * Logout.
   */
  if (logout) {
    logout.addEventListener('click', function (e) {
      var logoutUrl = e.target.dataset.action;
      return window.location.href = logoutUrl;
    });
  }
});
})();

/******/ })()
;
//# sourceMappingURL=front.js.map