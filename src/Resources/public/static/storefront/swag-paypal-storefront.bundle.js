/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, { enumerable: true, get: getter });
/******/ 		}
/******/ 	};
/******/
/******/ 	// define __esModule on exports
/******/ 	__webpack_require__.r = function(exports) {
/******/ 		if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 			Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 		}
/******/ 		Object.defineProperty(exports, '__esModule', { value: true });
/******/ 	};
/******/
/******/ 	// create a fake namespace object
/******/ 	// mode & 1: value is a module id, require it
/******/ 	// mode & 2: merge all properties of value into the ns
/******/ 	// mode & 4: return value when already ns object
/******/ 	// mode & 8|1: behave like require
/******/ 	__webpack_require__.t = function(value, mode) {
/******/ 		if(mode & 1) value = __webpack_require__(value);
/******/ 		if(mode & 8) return value;
/******/ 		if((mode & 4) && typeof value === 'object' && value && value.__esModule) return value;
/******/ 		var ns = Object.create(null);
/******/ 		__webpack_require__.r(ns);
/******/ 		Object.defineProperty(ns, 'default', { enumerable: true, value: value });
/******/ 		if(mode & 2 && typeof value != 'string') for(var key in value) __webpack_require__.d(ns, key, function(key) { return value[key]; }.bind(null, key));
/******/ 		return ns;
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "";
/******/
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = 2);
/******/ })
/************************************************************************/
/******/ ([
/* 0 */
/***/ (function(module, exports, __webpack_require__) {

(function (global, factory) {
	 true ? module.exports = factory() :
	undefined;
}(this, (function () { 'use strict';

var isMergeableObject = function isMergeableObject(value) {
	return isNonNullObject(value)
		&& !isSpecial(value)
};

function isNonNullObject(value) {
	return !!value && typeof value === 'object'
}

function isSpecial(value) {
	var stringValue = Object.prototype.toString.call(value);

	return stringValue === '[object RegExp]'
		|| stringValue === '[object Date]'
		|| isReactElement(value)
}

// see https://github.com/facebook/react/blob/b5ac963fb791d1298e7f396236383bc955f916c1/src/isomorphic/classic/element/ReactElement.js#L21-L25
var canUseSymbol = typeof Symbol === 'function' && Symbol.for;
var REACT_ELEMENT_TYPE = canUseSymbol ? Symbol.for('react.element') : 0xeac7;

function isReactElement(value) {
	return value.$$typeof === REACT_ELEMENT_TYPE
}

function emptyTarget(val) {
	return Array.isArray(val) ? [] : {}
}

function cloneUnlessOtherwiseSpecified(value, options) {
	return (options.clone !== false && options.isMergeableObject(value))
		? deepmerge(emptyTarget(value), value, options)
		: value
}

function defaultArrayMerge(target, source, options) {
	return target.concat(source).map(function(element) {
		return cloneUnlessOtherwiseSpecified(element, options)
	})
}

function getMergeFunction(key, options) {
	if (!options.customMerge) {
		return deepmerge
	}
	var customMerge = options.customMerge(key);
	return typeof customMerge === 'function' ? customMerge : deepmerge
}

function mergeObject(target, source, options) {
	var destination = {};
	if (options.isMergeableObject(target)) {
		Object.keys(target).forEach(function(key) {
			destination[key] = cloneUnlessOtherwiseSpecified(target[key], options);
		});
	}
	Object.keys(source).forEach(function(key) {
		if (!options.isMergeableObject(source[key]) || !target[key]) {
			destination[key] = cloneUnlessOtherwiseSpecified(source[key], options);
		} else {
			destination[key] = getMergeFunction(key, options)(target[key], source[key], options);
		}
	});
	return destination
}

function deepmerge(target, source, options) {
	options = options || {};
	options.arrayMerge = options.arrayMerge || defaultArrayMerge;
	options.isMergeableObject = options.isMergeableObject || isMergeableObject;

	var sourceIsArray = Array.isArray(source);
	var targetIsArray = Array.isArray(target);
	var sourceAndTargetTypesMatch = sourceIsArray === targetIsArray;

	if (!sourceAndTargetTypesMatch) {
		return cloneUnlessOtherwiseSpecified(source, options)
	} else if (sourceIsArray) {
		return options.arrayMerge(target, source, options)
	} else {
		return mergeObject(target, source, options)
	}
}

deepmerge.all = function deepmergeAll(array, options) {
	if (!Array.isArray(array)) {
		throw new Error('first argument should be an array')
	}

	return array.reduce(function(prev, next) {
		return deepmerge(prev, next, options)
	}, {})
};

var deepmerge_1 = deepmerge;

return deepmerge_1;

})));


/***/ }),
/* 1 */
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),
/* 2 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);

// EXTERNAL MODULE: /Users/ares/www/next/platform/src/Storefront/Resources/node_modules/deepmerge/dist/umd.js
var umd = __webpack_require__(0);
var umd_default = /*#__PURE__*/__webpack_require__.n(umd);

// CONCATENATED MODULE: /Users/ares/www/next/platform/src/Storefront/Resources/src/script/helper/string.helper.js
function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

var StringHelper =
/*#__PURE__*/
function () {
  function StringHelper() {
    _classCallCheck(this, StringHelper);
  }

  _createClass(StringHelper, null, [{
    key: "ucFirst",

    /**
     * turns first character of word to uppercase
     *
     * @param {string} string
     * @returns {string}
     * @private
     */
    value: function ucFirst(string) {
      return string.charAt(0).toUpperCase() + string.slice(1);
    }
    /**
     * turns first character of string to uppercase
     *
     * @param {string} string
     * @returns {string}
     * @private
     */

  }, {
    key: "lcFirst",
    value: function lcFirst(string) {
      return string.charAt(0).toLowerCase() + string.slice(1);
    }
    /**
     * converts a camel case string
     * into a dash case string
     *
     * @param string
     * @returns {string}
     */

  }, {
    key: "toDashCase",
    value: function toDashCase(string) {
      return string.replace(/([A-Z])/g, '-$1').replace(/^-/, '').toLowerCase();
    }
    /**
     *
     * @param {string} string
     * @param {string} separator
     *
     * @returns {string}
     */

  }, {
    key: "toLowerCamelCase",
    value: function toLowerCamelCase(string, separator) {
      var upperCamelCase = StringHelper.toUpperCamelCase(string, separator);
      return StringHelper.lcFirst(upperCamelCase);
    }
    /**
     *
     * @param {string} string
     * @param {string} separator
     *
     * @returns {string}
     */

  }, {
    key: "toUpperCamelCase",
    value: function toUpperCamelCase(string, separator) {
      if (!separator) {
        return StringHelper.ucFirst(string.toLowerCase());
      }

      var stringParts = string.split(separator);
      return stringParts.map(function (string) {
        return StringHelper.ucFirst(string.toLowerCase());
      }).join('');
    }
    /**
     * returns primitive value of a string
     *
     * @param value
     * @returns {*}
     * @private
     */

  }, {
    key: "parsePrimitive",
    value: function parsePrimitive(value) {
      try {
        // replace comma with dot
        // if value only contains numbers and commas
        if (/^\d+(.|,)\d+$/.test(value)) {
          value = value.replace(',', '.');
        }

        return JSON.parse(value);
      } catch (e) {
        return value.toString();
      }
    }
  }]);

  return StringHelper;
}();


// CONCATENATED MODULE: /Users/ares/www/next/platform/src/Storefront/Resources/src/script/helper/dom-access.helper.js
function _typeof(obj) { if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function dom_access_helper_classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function dom_access_helper_defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function dom_access_helper_createClass(Constructor, protoProps, staticProps) { if (protoProps) dom_access_helper_defineProperties(Constructor.prototype, protoProps); if (staticProps) dom_access_helper_defineProperties(Constructor, staticProps); return Constructor; }



var dom_access_helper_DomAccess =
/*#__PURE__*/
function () {
  function DomAccess() {
    dom_access_helper_classCallCheck(this, DomAccess);
  }

  dom_access_helper_createClass(DomAccess, null, [{
    key: "isNode",

    /**
     * Returns whether or not the element is an HTML node
     *
     * @param {HTMLElement} element
     * @returns {boolean}
     */
    value: function isNode(element) {
      if (!element) return false;

      if ((typeof Node === "undefined" ? "undefined" : _typeof(Node)) === 'object') {
        return element instanceof Node;
      }

      var isObject = _typeof(element) === 'object';
      var isNumber = typeof element.nodeType === 'number';
      var isString = typeof element.nodeName === 'string';
      var HtmlNode = isObject && isNumber && isString;
      var RootNode = element === document || element === window;
      return element && (HtmlNode || RootNode);
    }
    /**
     * Returns if the given element has the requested attribute/property
     * @param {HTMLElement} element
     * @param {string} attribute
     */

  }, {
    key: "hasAttribute",
    value: function hasAttribute(element, attribute) {
      if (!DomAccess.isNode(element)) {
        throw new Error('The element must be a valid HTML Node!');
      }

      if (typeof element.hasAttribute !== 'function') return false;
      return element.hasAttribute(attribute);
    }
    /**
     * Returns the value of a given element's attribute/property
     * @param {HTMLElement|EventTarget} element
     * @param {string} attribute
     * @param {boolean} strict
     * @returns {*|this|string}
     */

  }, {
    key: "getAttribute",
    value: function getAttribute(element, attribute) {
      var strict = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : true;

      if (strict && DomAccess.hasAttribute(element, attribute) === false) {
        throw new Error("The required property \"".concat(attribute, "\" does not exist!"));
      }

      if (typeof element.getAttribute !== 'function') {
        if (strict) {
          throw new Error('This node doesn\'t support the getAttribute function!');
        }

        return undefined;
      }

      return element.getAttribute(attribute);
    }
    /**
     * Returns the value of a given elements dataset entry
     *
     * @param {HTMLElement|EventTarget} element
     * @param {string} key
     * @param {boolean} strict
     * @returns {*|this|string}
     */

  }, {
    key: "getDataAttribute",
    value: function getDataAttribute(element, key) {
      var strict = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : true;
      var keyWithoutData = key.replace(/^data(|-)/, '');
      var parsedKey = StringHelper.toLowerCamelCase(keyWithoutData, '-');

      if (!DomAccess.isNode(element)) {
        if (strict) {
          throw new Error('The passed node is not a valid HTML Node!');
        }

        return undefined;
      }

      if (typeof element.dataset === 'undefined') {
        if (strict) {
          throw new Error('This node doesn\'t support the dataset attribute!');
        }

        return undefined;
      }

      var attribute = element.dataset[parsedKey];

      if (typeof attribute === 'undefined') {
        if (strict) {
          throw new Error("The required data attribute \"".concat(key, "\" does not exist on ").concat(element, "!"));
        }

        return attribute;
      }

      return StringHelper.parsePrimitive(attribute);
    }
    /**
     * Returns the selected element of a defined parent node
     * @param {HTMLElement|EventTarget} parentNode
     * @param {string} selector
     * @param {boolean} strict
     * @returns {HTMLElement}
     */

  }, {
    key: "querySelector",
    value: function querySelector(parentNode, selector) {
      var strict = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : true;

      if (strict && !DomAccess.isNode(parentNode)) {
        throw new Error('The parent node is not a valid HTML Node!');
      }

      var element = parentNode.querySelector(selector) || false;

      if (strict && element === false) {
        throw new Error("The required element \"".concat(selector, "\" does not exist in parent node!"));
      }

      return element;
    }
    /**
     * Returns the selected elements of a defined parent node
     *
     * @param {HTMLElement|EventTarget} parentNode
     * @param {string} selector
     * @param {boolean} strict
     * @returns {NodeList|false}
     */

  }, {
    key: "querySelectorAll",
    value: function querySelectorAll(parentNode, selector) {
      var strict = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : true;

      if (strict && !DomAccess.isNode(parentNode)) {
        throw new Error('The parent node is not a valid HTML Node!');
      }

      var elements = parentNode.querySelectorAll(selector);

      if (elements.length === 0) {
        elements = false;
      }

      if (strict && elements === false) {
        throw new Error("At least one item of \"".concat(selector, "\" must exist in parent node!"));
      }

      return elements;
    }
  }]);

  return DomAccess;
}();


// CONCATENATED MODULE: /Users/ares/www/next/platform/src/Storefront/Resources/src/script/helper/plugin/plugin.class.js
function plugin_class_classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function plugin_class_defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function plugin_class_createClass(Constructor, protoProps, staticProps) { if (protoProps) plugin_class_defineProperties(Constructor.prototype, protoProps); if (staticProps) plugin_class_defineProperties(Constructor, staticProps); return Constructor; }




/**
 * Plugin Base class
 */

var plugin_class_Plugin =
/*#__PURE__*/
function () {
  /**
   * plugin constructor
   *
   * @param {HTMLElement} el
   * @param {Object} options
   * @param {string} pluginName
   */
  function Plugin(el) {
    var options = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
    var pluginName = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : false;

    plugin_class_classCallCheck(this, Plugin);

    if (!dom_access_helper_DomAccess.isNode(el)) {
      throw new Error('There is no valid element given.');
    }

    this.el = el;
    this._pluginName = this._getPluginName(pluginName);
    this.options = this._mergeOptions(options);
    this._initialized = false;

    this._registerInstance();

    this._init();
  }
  /**
   * this function gets executed when the plugin is initialized
   */


  plugin_class_createClass(Plugin, [{
    key: "init",
    value: function init() {
      throw new Error("The \"init\" method for the plugin \"".concat(this._pluginName, "\" is not defined."));
    }
    /**
     * this function gets executed when the plugin is being updated
     */

  }, {
    key: "update",
    value: function update() {}
    /**
     * internal init method which checks
     * if the plugin is already initialized
     * before executing the public init
     *
     * @private
     */

  }, {
    key: "_init",
    value: function _init() {
      if (this._initialized) return;
      this.init();
      this._initialized = true;
    }
    /**
     * internal update method which checks
     * if the plugin is already initialized
     * before executing the public update
     *
     * @private
     */

  }, {
    key: "_update",
    value: function _update() {
      if (!this._initialized) return;
      this.update();
    }
    /**
     * deep merge the passed options and the static defaults
     *
     * @param {Object} options
     *
     * @private
     */

  }, {
    key: "_mergeOptions",
    value: function _mergeOptions(options) {
      var dashedPluginName = StringHelper.toDashCase(this._pluginName);
      var dataAttributeConfig = dom_access_helper_DomAccess.getDataAttribute(this.el, "data-".concat(dashedPluginName, "-config"), false);
      var dataAttributeOptions = dom_access_helper_DomAccess.getAttribute(this.el, "data-".concat(dashedPluginName, "-options"), false); // static plugin options
      // previously merged options
      // explicit options when creating a plugin instance with 'new'

      var merge = [this.constructor.options, this.options, options]; // options which are set via data-plugin-name-config="config name"

      if (dataAttributeConfig) merge.push(window.PluginConfigManager.get(this._pluginName, dataAttributeConfig)); // options which are set via data-plugin-name-options="{json..}"

      try {
        if (dataAttributeOptions) merge.push(JSON.parse(dataAttributeOptions));
      } catch (e) {
        console.error(this.el);
        throw new Error("The data attribute \"data-".concat(dashedPluginName, "-options\" could not be parsed to json: ").concat(e.message));
      }

      return umd_default.a.all(merge.map(function (config) {
        return config || {};
      }));
    }
    /**
     * registers the plugin Instance to the element
     *
     * @private
     */

  }, {
    key: "_registerInstance",
    value: function _registerInstance() {
      var elementPluginInstances = window.PluginManager.getPluginInstancesFromElement(this.el);
      elementPluginInstances.set(this._pluginName, this);
      var plugin = window.PluginManager.getPlugin(this._pluginName, false);
      plugin.get('instances').push(this);
    }
    /**
     * returns the plugin name
     *
     * @param {string} pluginName
     *
     * @returns {string}
     * @private
     */

  }, {
    key: "_getPluginName",
    value: function _getPluginName(pluginName) {
      if (!pluginName) pluginName = this.constructor.name;
      return pluginName;
    }
  }]);

  return Plugin;
}();


// CONCATENATED MODULE: /Users/ares/www/next/platform/src/Storefront/Resources/src/script/service/http-client.service.js
function http_client_service_classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function http_client_service_defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function http_client_service_createClass(Constructor, protoProps, staticProps) { if (protoProps) http_client_service_defineProperties(Constructor.prototype, protoProps); if (staticProps) http_client_service_defineProperties(Constructor, staticProps); return Constructor; }

var HttpClient =
/*#__PURE__*/
function () {
  /**
   * Constructor.
   * @param {string} accessKey
   * @param {string} contextToken
   */
  function HttpClient(accessKey, contextToken) {
    http_client_service_classCallCheck(this, HttpClient);

    this._request = null;
    this._accessKey = accessKey;
    this._contextToken = contextToken;
  }
  /**
   * @returns {string}
   */


  http_client_service_createClass(HttpClient, [{
    key: "get",

    /**
     * Request GET
     *
     * @param {string} url
     * @param {function} callback
     * @param {string} contentType
     *
     * @returns {XMLHttpRequest}
     */
    value: function get(url, callback) {
      var contentType = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : 'application/json';

      var request = this._createPreparedRequest('GET', url, contentType);

      this._registerOnLoaded(request, callback);

      request.send();
      return request;
    }
    /**
     * Request POST
     *
     * @param {string} url
     * @param {object|null} data
     * @param {function} callback
     * @param {string} contentType
     *
     * @returns {XMLHttpRequest}
     */

  }, {
    key: "post",
    value: function post(url, data, callback) {
      var contentType = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : 'application/json';
      contentType = this._getContentType(data, contentType);

      var request = this._createPreparedRequest('POST', url, contentType);

      this._registerOnLoaded(request, callback);

      request.send(data);
      return request;
    }
    /**
     * Request DELETE
     *
     * @param {string} url
     * @param {object|null} data
     * @param {function} callback
     * @param {string} contentType
     *
     * @returns {XMLHttpRequest}
     */

  }, {
    key: "delete",
    value: function _delete(url, data, callback) {
      var contentType = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : 'application/json';
      contentType = this._getContentType(data, contentType);

      var request = this._createPreparedRequest('DELETE', url, contentType);

      this._registerOnLoaded(request, callback);

      request.send(data);
      return request;
    }
    /**
     * Request PATCH
     * @param {string} url
     * @param {object|null} data
     * @param {function} callback
     * @param {string} contentType
     *
     * @returns {XMLHttpRequest}
     */

  }, {
    key: "patch",
    value: function patch(url, data, callback) {
      var contentType = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : 'application/json';
      contentType = this._getContentType(data, contentType);

      var request = this._createPreparedRequest('PATCH', url, contentType);

      this._registerOnLoaded(request, callback);

      request.send(data);
      return request;
    }
    /**
     * Abort running Request
     *
     * @returns {*}
     */

  }, {
    key: "abort",
    value: function abort() {
      if (this._request) {
        return this._request.abort();
      }
    }
    /**
     * register event listener
     * which executes the given callback
     * when the request has finished
     *
     * @param request
     * @param callback
     * @private
     */

  }, {
    key: "_registerOnLoaded",
    value: function _registerOnLoaded(request, callback) {
      request.addEventListener('loadend', function () {
        callback(request.responseText);
      });
    }
    /**
     * returns the appropriate content type for the request
     *
     * @param {*} data
     * @param {string} contentType
     *
     * @returns {string|boolean}
     * @private
     */

  }, {
    key: "_getContentType",
    value: function _getContentType(data, contentType) {
      // when sending form data,
      // the content-type has to be automatically set,
      // to use the correct content-disposition
      // https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Content-Disposition
      if (data instanceof FormData) {
        contentType = false;
      }

      return contentType;
    }
    /**
     * Returns a new and configured XMLHttpRequest object which
     * is prepared to being used
     *
     * @param {'GET'|'POST'|'DELETE'|'PATCH'} type
     * @param {string} url
     * @param {string} contentType
     *
     * @returns {XMLHttpRequest}
     * @private
     */

  }, {
    key: "_createPreparedRequest",
    value: function _createPreparedRequest(type, url, contentType) {
      this._request = new XMLHttpRequest();

      this._request.open(type, url);

      this._request.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

      this._request.setRequestHeader('sw-access-key', this.accessKey);

      this._request.setRequestHeader('sw-context-token', this.contextToken);

      if (contentType) {
        this._request.setRequestHeader('Content-type', contentType);
      }

      return this._request;
    }
  }, {
    key: "accessKey",
    get: function get() {
      return this._accessKey;
    }
    /**
     * @returns {string}
     */

  }, {
    key: "contextToken",
    get: function get() {
      return this._contextToken;
    }
  }]);

  return HttpClient;
}();


// CONCATENATED MODULE: /Users/ares/www/next/platform/src/Storefront/Resources/src/script/helper/iterator.helper.js
function iterator_helper_typeof(obj) { if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { iterator_helper_typeof = function _typeof(obj) { return typeof obj; }; } else { iterator_helper_typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return iterator_helper_typeof(obj); }

function iterator_helper_classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function iterator_helper_defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function iterator_helper_createClass(Constructor, protoProps, staticProps) { if (protoProps) iterator_helper_defineProperties(Constructor.prototype, protoProps); if (staticProps) iterator_helper_defineProperties(Constructor, staticProps); return Constructor; }

var Iterator =
/*#__PURE__*/
function () {
  function Iterator() {
    iterator_helper_classCallCheck(this, Iterator);
  }

  iterator_helper_createClass(Iterator, null, [{
    key: "iterate",

    /**
     * This callback is displayed as a global member.
     * @callback ObjectIterateCallback
     * @param {value} value
     * @param {key} key
     */

    /**
     * Iterates over an object
     *
     * @param {Array|Object} source
     * @param {ObjectIterateCallback} callback
     *
     * @returns {*}
     */
    value: function iterate(source, callback) {
      if (source instanceof Map) {
        return source.forEach(callback);
      }

      if (Array.isArray(source)) {
        return source.forEach(callback);
      }

      if (source instanceof FormData) {
        var _iteratorNormalCompletion = true;
        var _didIteratorError = false;
        var _iteratorError = undefined;

        try {
          for (var _iterator = source.entries()[Symbol.iterator](), _step; !(_iteratorNormalCompletion = (_step = _iterator.next()).done); _iteratorNormalCompletion = true) {
            var entry = _step.value;
            callback(entry[1], entry[0]);
          }
        } catch (err) {
          _didIteratorError = true;
          _iteratorError = err;
        } finally {
          try {
            if (!_iteratorNormalCompletion && _iterator.return != null) {
              _iterator.return();
            }
          } finally {
            if (_didIteratorError) {
              throw _iteratorError;
            }
          }
        }

        return;
      }

      if (source instanceof Object) {
        return Object.keys(source).forEach(function (key) {
          callback(source[key], key);
        });
      }

      throw new Error("The element type ".concat(iterator_helper_typeof(source), " is not iterable!"));
    }
  }]);

  return Iterator;
}();


// CONCATENATED MODULE: /Users/ares/www/next/platform/src/Storefront/Resources/src/script/utility/loading-indicator/loading-indicator.util.js
function loading_indicator_util_classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function loading_indicator_util_defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function loading_indicator_util_createClass(Constructor, protoProps, staticProps) { if (protoProps) loading_indicator_util_defineProperties(Constructor.prototype, protoProps); if (staticProps) loading_indicator_util_defineProperties(Constructor, staticProps); return Constructor; }


var _SELECTOR_CLASS = 'loader';

var loading_indicator_util_LoadingIndicatorUtil =
/*#__PURE__*/
function () {
  /**
   * Constructor
   * @param {Element|string} parent
   * @param position
   */
  function LoadingIndicatorUtil(parent) {
    var position = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 'before';

    loading_indicator_util_classCallCheck(this, LoadingIndicatorUtil);

    this.parent = parent instanceof Element ? parent : document.body.querySelector(parent);
    this.position = position;
  }
  /**
   * Inserts a loading indicator inside the parent element's html
   */


  loading_indicator_util_createClass(LoadingIndicatorUtil, [{
    key: "create",
    value: function create() {
      if (this.exists()) return;
      this.parent.insertAdjacentHTML(this._getPosition(), LoadingIndicatorUtil.getTemplate());
    }
    /**
     * Removes all existing loading indicators inside the parent
     */

  }, {
    key: "remove",
    value: function remove() {
      var indicators = this.parent.querySelectorAll(".".concat(_SELECTOR_CLASS));
      Iterator.iterate(indicators, function (indicator) {
        return indicator.remove();
      });
    }
    /**
     * Checks if a loading indicator already exists
     * @returns {boolean}
     * @protected
     */

  }, {
    key: "exists",
    value: function exists() {
      return this.parent.querySelectorAll(".".concat(_SELECTOR_CLASS)).length > 0;
    }
    /**
     * Defines the position to which the loading indicator shall be inserted.
     * Depends on the usage of the "insertAdjacentHTML" method.
     * @returns {"afterbegin"|"beforeend"}
     * @private
     */

  }, {
    key: "_getPosition",
    value: function _getPosition() {
      return this.position === 'before' ? 'afterbegin' : 'beforeend';
    }
    /**
     * The loading indicators HTML template definition
     * @returns {string}
     */

  }], [{
    key: "getTemplate",
    value: function getTemplate() {
      return "<div class=\"".concat(_SELECTOR_CLASS, "\" role=\"status\">\n                    <span class=\"sr-only\">Loading...</span>\n                </div>");
    }
    /**
     * Return the constant
     * @returns {string}
     * @constructor
     */

  }, {
    key: "SELECTOR_CLASS",
    value: function SELECTOR_CLASS() {
      return _SELECTOR_CLASS;
    }
  }]);

  return LoadingIndicatorUtil;
}();


// CONCATENATED MODULE: /Users/ares/www/next/platform/src/Storefront/Resources/src/script/utility/loading-indicator/element-loading-indicator.util.js
function element_loading_indicator_util_typeof(obj) { if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { element_loading_indicator_util_typeof = function _typeof(obj) { return typeof obj; }; } else { element_loading_indicator_util_typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return element_loading_indicator_util_typeof(obj); }

function element_loading_indicator_util_classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function element_loading_indicator_util_defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function element_loading_indicator_util_createClass(Constructor, protoProps, staticProps) { if (protoProps) element_loading_indicator_util_defineProperties(Constructor.prototype, protoProps); if (staticProps) element_loading_indicator_util_defineProperties(Constructor, staticProps); return Constructor; }

function _possibleConstructorReturn(self, call) { if (call && (element_loading_indicator_util_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }


var ELEMENT_LOADER_CLASS = 'element-loader-backdrop';

var ElementLoadingIndicatorUtil =
/*#__PURE__*/
function (_LoadingIndicatorUtil) {
  _inherits(ElementLoadingIndicatorUtil, _LoadingIndicatorUtil);

  function ElementLoadingIndicatorUtil() {
    element_loading_indicator_util_classCallCheck(this, ElementLoadingIndicatorUtil);

    return _possibleConstructorReturn(this, _getPrototypeOf(ElementLoadingIndicatorUtil).apply(this, arguments));
  }

  element_loading_indicator_util_createClass(ElementLoadingIndicatorUtil, null, [{
    key: "create",

    /**
     * adds the loader from the element
     *
     * @param {HTMLElement} el
     */
    value: function create(el) {
      el.classList.add('has-element-loader');
      if (ElementLoadingIndicatorUtil.exists(el)) return;
      ElementLoadingIndicatorUtil.appendLoader(el);
      setTimeout(function () {
        var loader = el.querySelector(".".concat(ELEMENT_LOADER_CLASS));

        if (!loader) {
          return;
        }

        loader.classList.add('element-loader-backdrop-open');
      }, 1);
    }
    /**
     * removes the loader from the element
     *
     * @param {HTMLElement} el
     */

  }, {
    key: "remove",
    value: function remove(el) {
      el.classList.remove('has-element-loader');
      var loader = el.querySelector(".".concat(ELEMENT_LOADER_CLASS));

      if (!loader) {
        return;
      }

      loader.remove();
    }
    /**
     * checks if a loader is already present
     *
     * @param {HTMLElement} el
     *
     * @returns {boolean}
     */

  }, {
    key: "exists",
    value: function exists(el) {
      return el.querySelectorAll(".".concat(ELEMENT_LOADER_CLASS)).length > 0;
    }
    /**
     * returns the loader template
     *
     * @returns {string}
     */

  }, {
    key: "getTemplate",
    value: function getTemplate() {
      return "\n        <div class=\"".concat(ELEMENT_LOADER_CLASS, "\">\n            <div class=\"loader\" role=\"status\">\n                <span class=\"sr-only\">Loading...</span>\n            </div>\n        </div>\n        ");
    }
    /**
     * inserts the loader into the passed element
     *
     * @param {HTMLElement} el
     */

  }, {
    key: "appendLoader",
    value: function appendLoader(el) {
      el.insertAdjacentHTML('beforeend', ElementLoadingIndicatorUtil.getTemplate());
    }
  }]);

  return ElementLoadingIndicatorUtil;
}(loading_indicator_util_LoadingIndicatorUtil);


// EXTERNAL MODULE: ./express-checkout-button/swag-paypal.express-checkout.scss
var swag_paypal_express_checkout = __webpack_require__(1);

// CONCATENATED MODULE: ./express-checkout-button/swag-paypal.express-checkout.js
function swag_paypal_express_checkout_typeof(obj) { if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { swag_paypal_express_checkout_typeof = function _typeof(obj) { return typeof obj; }; } else { swag_paypal_express_checkout_typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return swag_paypal_express_checkout_typeof(obj); }

function swag_paypal_express_checkout_classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function swag_paypal_express_checkout_defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function swag_paypal_express_checkout_createClass(Constructor, protoProps, staticProps) { if (protoProps) swag_paypal_express_checkout_defineProperties(Constructor.prototype, protoProps); if (staticProps) swag_paypal_express_checkout_defineProperties(Constructor, staticProps); return Constructor; }

function swag_paypal_express_checkout_possibleConstructorReturn(self, call) { if (call && (swag_paypal_express_checkout_typeof(call) === "object" || typeof call === "function")) { return call; } return swag_paypal_express_checkout_assertThisInitialized(self); }

function swag_paypal_express_checkout_assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function swag_paypal_express_checkout_getPrototypeOf(o) { swag_paypal_express_checkout_getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return swag_paypal_express_checkout_getPrototypeOf(o); }

function swag_paypal_express_checkout_inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) swag_paypal_express_checkout_setPrototypeOf(subClass, superClass); }

function swag_paypal_express_checkout_setPrototypeOf(o, p) { swag_paypal_express_checkout_setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return swag_paypal_express_checkout_setPrototypeOf(o, p); }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

/* eslint-disable import/no-unresolved */




var OFF_CANVAS_CART_CLOSE_BUTTON_SELECTOR = '.btn.btn-light.btn-block.offcanvas-close.js-offcanvas-close.sticky-top';

var swag_paypal_express_checkout_SwagPayPalExpressCheckoutButton =
/*#__PURE__*/
function (_Plugin) {
  swag_paypal_express_checkout_inherits(SwagPayPalExpressCheckoutButton, _Plugin);

  function SwagPayPalExpressCheckoutButton() {
    swag_paypal_express_checkout_classCallCheck(this, SwagPayPalExpressCheckoutButton);

    return swag_paypal_express_checkout_possibleConstructorReturn(this, swag_paypal_express_checkout_getPrototypeOf(SwagPayPalExpressCheckoutButton).apply(this, arguments));
  }

  swag_paypal_express_checkout_createClass(SwagPayPalExpressCheckoutButton, [{
    key: "init",
    value: function init() {
      this._client = new HttpClient(window.accessKey, window.contextToken);
      this.paypal = null;
      this.createButton();
    }
  }, {
    key: "createButton",
    value: function createButton() {
      var _this = this;

      var paypalScriptLoaded = document.head.classList.contains(this.options.paypalScriptLoadedClass);

      if (paypalScriptLoaded) {
        this.paypal = window.paypal;
        this.renderButton();
        return;
      }

      this.createScript(function () {
        _this.paypal = window.paypal;
        document.head.classList.add(_this.options.paypalScriptLoadedClass);

        _this.renderButton();
      });
    }
  }, {
    key: "createScript",
    value: function createScript(callback) {
      var scriptOptions = this.getScriptUrlOptions();
      var payPalScriptUrl = this.options.useSandbox ? "https://www.paypal.com/sdk/js?client-id=sb".concat(scriptOptions) : "https://www.paypal.com/sdk/js?client-id=".concat(this.options.clientId).concat(scriptOptions);
      var payPalScript = document.createElement('script');
      payPalScript.type = 'text/javascript';
      payPalScript.src = payPalScriptUrl;
      payPalScript.addEventListener('load', callback.bind(this), false);
      document.head.appendChild(payPalScript);
      return payPalScript;
    }
  }, {
    key: "renderButton",
    value: function renderButton() {
      return this.paypal.Buttons(this.getButtonConfig()).render(this.el);
    }
  }, {
    key: "getButtonConfig",
    value: function getButtonConfig() {
      return {
        style: {
          size: this.options.buttonSize,
          shape: this.options.buttonShape,
          color: this.options.buttonColor,
          tagline: this.options.tagline,
          layout: 'horizontal',
          label: 'checkout',
          height: 40
        },

        /**
         * Will be called if the express button is clicked
         */
        createOrder: this.createOrder.bind(this),

        /**
         * Will be called if the payment process is approved by paypal
         */
        onApprove: this.onApprove.bind(this)
      };
    }
    /**
     * @return {Promise}
     */

  }, {
    key: "createOrder",
    value: function createOrder() {
      var _this2 = this;

      return new Promise(function (resolve) {
        _this2._client.get('/sales-channel-api/v1/_action/paypal/create-payment', function (responseText) {
          var response = JSON.parse(responseText);
          resolve(response.token);
        });
      });
    }
    /**
     * @param data
     */

  }, {
    key: "onApprove",
    value: function onApprove(data) {
      var offCanvasCloseButton = document.querySelector(OFF_CANVAS_CART_CLOSE_BUTTON_SELECTOR);
      var requestPayload = {
        paymentId: data.paymentID
      }; // If the offCanvasCartCloseButton is visible, we close the offCanvsCart by clicking the element

      if (offCanvasCloseButton) {
        offCanvasCloseButton.click();
      } // Add a loading indicator to the body to prevent the user breaking the checkout process


      ElementLoadingIndicatorUtil.create(document.body);

      this._client.post('/paypal/approve-payment', JSON.stringify(requestPayload), function () {
        window.location.replace('/checkout/confirm');
        ElementLoadingIndicatorUtil.remove(document.body);
      });
    }
    /**
     * @return {string}
     */

  }, {
    key: "getScriptUrlOptions",
    value: function getScriptUrlOptions() {
      var config = '';
      config += "&locale=".concat(this.options.languageIso);
      config += "&commit=".concat(this.options.commit);
      config += '&disable-funding=card,credit,sepa';

      if (this.options.currency) {
        config += "&currency=".concat(this.options.currency);
      }

      if (this.options.intent && this.options.intent !== 'sale') {
        config += "&intent=".concat(this.options.intent);
      }

      return config;
    }
  }]);

  return SwagPayPalExpressCheckoutButton;
}(plugin_class_Plugin);

_defineProperty(swag_paypal_express_checkout_SwagPayPalExpressCheckoutButton, "options", {
  /**
   * This option specifies the PayPal button color
   */
  buttonColor: 'gold',

  /**
   * This option specifies the PayPal button shape
   */
  buttonShape: 'rect',

  /**
   * This option specifies the PayPal button size
   */
  buttonSize: 'small',

  /**
   * This option specifies the language of the PayPal button
   */
  languageIso: 'en_GB',

  /**
   * This option specifies if the PayPal button appears on the checkout/register page
   */
  loginEnabled: false,

  /**
   * This option toggles the SandboxMode
   */
  useSandbox: false,

  /**
   * This option holds the client id specified in the settings
   */
  clientId: '',

  /**
   * This option toggles the PayNow/Login text at PayPal
   */
  commit: false,

  /**
   * This option toggles the Text below the PayPal Express button
   */
  tagline: false,

  /**
   * The class that indicates if the script is loaded
   *
   * @type string
   */
  paypalScriptLoadedClass: 'paypal-checkout-js-loaded'
});


// CONCATENATED MODULE: ./paypal-selector/paypal-selector.js
function paypal_selector_typeof(obj) { if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { paypal_selector_typeof = function _typeof(obj) { return typeof obj; }; } else { paypal_selector_typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return paypal_selector_typeof(obj); }

function paypal_selector_classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function paypal_selector_defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function paypal_selector_createClass(Constructor, protoProps, staticProps) { if (protoProps) paypal_selector_defineProperties(Constructor.prototype, protoProps); if (staticProps) paypal_selector_defineProperties(Constructor, staticProps); return Constructor; }

function paypal_selector_possibleConstructorReturn(self, call) { if (call && (paypal_selector_typeof(call) === "object" || typeof call === "function")) { return call; } return paypal_selector_assertThisInitialized(self); }

function paypal_selector_assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function paypal_selector_getPrototypeOf(o) { paypal_selector_getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return paypal_selector_getPrototypeOf(o); }

function paypal_selector_inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) paypal_selector_setPrototypeOf(subClass, superClass); }

function paypal_selector_setPrototypeOf(o, p) { paypal_selector_setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return paypal_selector_setPrototypeOf(o, p); }

function paypal_selector_defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

/* eslint-disable import/no-unresolved */


/**
 * This Plugin selects the PayPal payment method if the user is being redirected by the express implementation.
 */

var paypal_selector_PayPalSelector =
/*#__PURE__*/
function (_Plugin) {
  paypal_selector_inherits(PayPalSelector, _Plugin);

  function PayPalSelector() {
    paypal_selector_classCallCheck(this, PayPalSelector);

    return paypal_selector_possibleConstructorReturn(this, paypal_selector_getPrototypeOf(PayPalSelector).apply(this, arguments));
  }

  paypal_selector_createClass(PayPalSelector, [{
    key: "init",
    value: function init() {
      this.selectPaymentMethodPayPal();
    }
  }, {
    key: "selectPaymentMethodPayPal",
    value: function selectPaymentMethodPayPal() {
      var paypalRadioButton = dom_access_helper_DomAccess.querySelector(document.body, "input[value=\"".concat(this.options.paypalPaymentMethodId, "\"]"));

      if (paypalRadioButton) {
        paypalRadioButton.checked = true;
      }
    }
  }]);

  return PayPalSelector;
}(plugin_class_Plugin);

paypal_selector_defineProperty(paypal_selector_PayPalSelector, "options", {
  /**
   * This option is used to select the PayPal radio button
   */
  paypalPaymentMethodId: ''
});


// CONCATENATED MODULE: ./main.js
// Import all necessary Storefront plugins and scss files

 // Register them via the existing PluginManager

var PluginManager = window.PluginManager;
PluginManager.register('SwagPayPalExpressButton', swag_paypal_express_checkout_SwagPayPalExpressCheckoutButton, '[data-swag-paypal-express-button]');
PluginManager.register('PayPalSelector', paypal_selector_PayPalSelector, 'input[name="isPayPalExpressCheckout"]');

/***/ })
/******/ ]);