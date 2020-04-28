(function (global, factory) {
  "use strict";

  if (typeof module === "object" && typeof module.exports === "object") {
    // For CommonJS and CommonJS-like environments where a proper `window`
    // is present, execute the factory and get jQuery.
    // For environments that do not have a `window` with a `document`
    // (such as Node.js), expose a factory as module.exports.
    // This accentuates the need for the creation of a real `window`.
    // e.g. var jQuery = require("jquery")(window);
    // See ticket #14549 for more info.
    module.exports = global.document
      ? factory(global, true)
      : function (w) {
          if (!w.document) {
            throw new Error("DOM requires a window with a document");
          }
          return factory(w);
        };
  } else {
    factory(global);
  }

  // Pass this if window is not defined yet
})(typeof window !== "undefined" ? window : this, function (window, noGlobal) {
  "use strict";

  var DOM = function (selector, context) {
    return new DOM.fn.init(selector, context);
  };

  DOM.fn = DOM.prototype = {
    constructor: DOM,
    length: 0,
    toArray: function () {
      return Array.slice.call(this);
    },
    get: function (num) {
      if (num === null) {
        return Array.slice.call(this);
      }
      return num < 0 ? this[num + this.length] : this[num];
    },
    pushStack: function (elems) {
      // Build a new jQuery matched element set
      var ret = DOM.merge(this.constructor(), elems);

      // Add the old object onto the stack (as a reference)
      ret.prevObject = this;

      // Return the newly-formed element set
      return ret;
    },
    each: function (callback) {
      return DOM.each(this, callback);
    },
    map: function (callback) {
      return this.pushStack(
        DOM.map(this, function (elem, i) {
          return callback.call(elem, i, elem);
        })
      );
    },
    slice: function () {
      return this.pushStack(Array.slice.apply(this, arguments));
    },

    first: function () {
      return this.eq(0);
    },

    last: function () {
      return this.eq(-1);
    },

    even: function () {
      return this.pushStack(
        DOM.grep(this, function (_elem, i) {
          return (i + 1) % 2;
        })
      );
    },

    odd: function () {
      return this.pushStack(
        DOM.grep(this, function (_elem, i) {
          return i % 2;
        })
      );
    },

    eq: function (i) {
      var len = this.length,
        j = +i + (i < 0 ? len : 0);
      return this.pushStack(j >= 0 && j < len ? [this[j]] : []);
    },

    end: function () {
      return this.prevObject || this.constructor();
    },
  };

  var rootDOM,
    // A simple way to check for HTML strings
    // Prioritize #id over <tag> to avoid XSS via location.hash (#9521)
    // Strict HTML recognition (#11290: must start with <)
    // Shortcut simple #id case for speed
    rquickExpr = /^(?:\s*(<[\w\W]+>)[^>]*|#([\w-]+))$/,
    init = (DOM.fn.init = function (selector, context, root) {
      //TODO
      //Init goes here
    });

  init.prototype = DOM.fn;

  rootDOM = DOM(document);

  if (typeof noGlobal === "undefined") {
    window.jQuery = window.$ = DOM;
  }

  return DOM;
});
