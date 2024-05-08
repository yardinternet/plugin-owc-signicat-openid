/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./resources/css/modal.css":
/*!*********************************!*\
  !*** ./resources/css/modal.css ***!
  \*********************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "@wordpress/api-fetch":
/*!**********************************!*\
  !*** external ["wp","apiFetch"] ***!
  \**********************************/
/***/ ((module) => {

module.exports = window["wp"]["apiFetch"];

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
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
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
  !*** ./resources/js/modal.js ***!
  \*******************************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _css_modal_css__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../css/modal.css */ "./resources/css/modal.css");
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }
function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, _toPropertyKey(descriptor.key), descriptor); } }
function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }
function _defineProperty(obj, key, value) { key = _toPropertyKey(key); if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }
function _toPropertyKey(arg) { var key = _toPrimitive(arg, "string"); return _typeof(key) === "symbol" ? key : String(key); }
function _toPrimitive(input, hint) { if (_typeof(input) !== "object" || input === null) return input; var prim = input[Symbol.toPrimitive]; if (prim !== undefined) { var res = prim.call(input, hint || "default"); if (_typeof(res) !== "object") return res; throw new TypeError("@@toPrimitive must return a primitive value."); } return (hint === "string" ? String : Number)(input); }


var OWC_Signicat_OIDC_Modal = /*#__PURE__*/function () {
  function OWC_Signicat_OIDC_Modal(settings) {
    var _this = this;
    _classCallCheck(this, OWC_Signicat_OIDC_Modal);
    _defineProperty(this, "checkSessionStatus", function () {
      var inactivity = new Date().valueOf() - _this.lastActivity;
      if (inactivity > _this.modalShouldOpen && !_this.modalIsOpen) {
        _this.toggleModal(true);
      }
      if (inactivity > _this.sessionShouldEnd) {
        _this.logout();
      }
    });
    _defineProperty(this, "updateLastActivity", function () {
      _this.lastActivity = Date.now();
      _this.lastActivityIsUpdated = true;
    });
    _defineProperty(this, "logout", function () {
      _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_0___default()({
        path: 'owc-signicat-openid/v1/revoke'
      }).then(function (res) {
        console.log(res);
        window.location = _this.logoutUrl;
      });
    });
    _defineProperty(this, "keepSessionAlive", function () {
      if (_this.lastActivityIsUpdated) {
        _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_0___default()({
          path: 'owc-signicat-openid/v1/refresh'
        }).then(function (res) {
          _this.lastActivityIsUpdated = false;
        });
      }
    });
    /**
     * Add keypress event to modal buttons.
     *
     * @param {Object} e
     */
    _defineProperty(this, "a11yClick", function (e) {
      var SPACE_KEY = 32;
      if (e.type !== 'click' || e.type !== 'keypress') return false;
      if (e.type === 'keypress') {
        var code = e.charCode || e.keyCode;
        if (code !== SPACE_KEY) return false;
      }
      return true;
    });
    this.second = 1000;
    this.minute = 60 * this.second;
    this.sessionShouldEnd = settings.sessionTTL * this.minute;
    this.modalShouldOpen = this.sessionShouldEnd - this.minute;
    this.refreshUrl = settings.refreshUrl;
    this.logoutUrl = settings.logoutUrl;
  }
  _createClass(OWC_Signicat_OIDC_Modal, [{
    key: "init",
    value: function init() {
      var modalWrapperId = 'owc-signicat-openid-modal-wrapper';
      var refreshButtonId = 'owc-signicat-openid-refresh';
      var logoutButtonId = 'owc-signicat-openid-logout';
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
  }, {
    key: "registerEventHandlers",
    value: function registerEventHandlers() {
      var _this2 = this;
      this.refreshButtonEl.addEventListener('click', function (e) {
        return _this2.sessionResume(e);
      });
      this.refreshButtonEl.addEventListener('keydown', function (e) {
        return _this2.a11yClick(e);
      });
      this.logoutButtonEl.addEventListener('click', function (e) {
        return _this2.sessionEnd(e);
      });
      this.logoutButtonEl.addEventListener('keydown', function (e) {
        return _this2.a11yClick(e);
      });
      document.addEventListener('mousemove', function () {
        return _this2.updateLastActivity();
      });
      document.addEventListener('keydown', function () {
        return _this2.updateLastActivity();
      });
    }
  }, {
    key: "initTimer",
    value: function initTimer() {
      setInterval(this.checkSessionStatus, this.second);
      setInterval(this.keepSessionAlive, this.minute);
    }
  }, {
    key: "sessionResume",
    value: function sessionResume() {
      this.toggleModal(false);
      this.keepSessionAlive();
    }
  }, {
    key: "sessionEnd",
    value: function sessionEnd() {
      this.toggleModal(false);
      this.logout();
    }
  }, {
    key: "toggleModal",
    value: function toggleModal(open) {
      if (open) {
        this.modalEl.classList.add(this.modalOpenClass);
        this.modalEl.setAttribute('aria-hidden', 'false');
      } else {
        this.modalEl.classList.remove(this.modalOpenClass);
        this.modalEl.setAttribute('aria-hidden', 'truez');
      }
      this.modalIsOpen = open;
    }
  }]);
  return OWC_Signicat_OIDC_Modal;
}();
new OWC_Signicat_OIDC_Modal(owcSignicatOIDCModalSettings).init();
})();

/******/ })()
;
//# sourceMappingURL=modal.js.map