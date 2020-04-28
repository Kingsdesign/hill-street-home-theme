var DOM = function (selector) {
  return new DOM.fn.init(selector);
};

DOM.fn = DOM.prototype = {
  constructor: DOM,
  length: 0,
};

DOM.fn.init = function (selector) {
  var thisElement; //is actually an array

  if (typeof selector === `function`) {
    return ready(() => selector.call(null, DOM));
  } else if (selector === document) {
    return {
      ready: function (cb) {
        return ready(() => cb.call(document, DOM));
      },
    };
  } else if (selector.tagName) {
    //We have a DOM element ready to go
    thisElement = [selector];
  } else if (typeof selector === `string`) {
    //Is html string
    if (selector.match(/^<(.*?)>/)) {
      thisElement = [...createFromHtml(selector)];
    } else {
      thisElement = [...find(selector)];
    }
  }

  if (!thisElement) thisElement = [];

  for (var i = 0; i < thisElement.length; i++) {
    this[i] = thisElement[0];
  }

  return {
    find(selector) {
      return thisElement.map((e) => find(e, selector));
    },
    closest(selector) {
      return thisElement.map((e) => e.closest(selector));
    },
    append(selector) {
      var targetElements = find(selector);

      thisElement.forEach((e) => {
        targetElements.forEach((target) => {
          target.appendChild(e);
        });
      });
      return this;
    },
    insertAfter(selector) {
      var targetElements = find(selector);

      thisElement.forEach((e) => {
        targetElements.forEach((target) => {
          target.insertAdjacentElement("afterend", e);
        });
      });
      return this;
    },
    wrap(wrappingElement) {
      thisElement.forEach((e) => {});
    },
    trigger(eventType, extraParameters) {
      var event;
      //Native
      if (eventType === "click" || eventType === "change") {
        //TODO other event types
        event = document.createEvent("HTMLEvents"); //createEVent is deprecated!! //TODO replace with event constructors
        event.initEvent(eventType, true, false);
      } else {
        //Custom
        if (window.CustomEvent && typeof window.CustomEvent === "function") {
          event = new CustomEvent(eventType, {
            detail: extraParameters,
          });
        } else {
          event = document.createEvent("CustomEvent");
          event.initCustomEvent(eventType, true, true, extraParameters);
        }
      }

      thisElement.forEach((e) => e.dispatchEvent(event));
      return this;
    },
    click(handler) {
      //TODO eventdata param
      thisElement.forEach((e) => this.on("click", e, handler));
      return this;
    },
    fadeIn(duration = 400) {
      thisElement.forEach((e) => {
        e.style.transition = "opacity " + duration + "ms";
        e.style.opacity = 0;
        requestAnimationFrame(() => {
          e.style.opacity = 1;
        });
      });
      return this;
    },
    fadeOut(duration = 400) {
      thisElement.forEach((e) => {
        e.style.transition = "opacity " + duration + "ms";
        e.style.opacity = 1;
        requestAnimationFrame(() => {
          e.style.opacity = 0;
        });
      });
      return this;
    },
    show() {
      thisElement.forEach((e) => {
        e.style.display = "";
      });
      return this;
    },
    hide() {
      thisElement.forEach((e) => {
        e.style.display = "none";
      });
      return this;
    },
    data(key, value) {
      key = kebabToCamelCase(key);
      if (typeof value !== "undefined") {
        thisElement.forEach((e) => {
          e.dataset[key] = value;
        });
        return this;
      }
      if (thisElement.length) return thisElement[0].dataset[key];
      return null;
    },
    each(callback) {
      thisElement.forEach((e, i) => {
        callback.apply(e, [i, e]);
      });
      return this;
    },
    on(eventNames, elementSelector, handler) {
      //TODO eventdata param
      eventNames = eventNames.split(" ");

      eventNames.forEach((eventName) => {
        if (thisElement === document) {
          if (typeof elementSelector === "function") {
            handler = elementSelector;
            elementSelector = document;
          }
          document.addEventListener(
            eventName,
            function (e) {
              for (
                var target = e.target;
                target && target != this;
                target = target.parentNode
              ) {
                if (target.matches(elementSelector)) {
                  handler.call(target, e);
                  break;
                }
              }
            },
            false
          );
          return this;
        }
        thisElement.forEach((e) => {
          if (typeof elementSelector === "function") {
            handler = elementSelector;
            elementSelector = e;
          }
          e.addEventListener(eventName, handler);
        });
      });

      return this;
    },
    off(eventName, handler) {
      //if(thisElement === document)
      //return document.removeEventListener(eventName,)
      //TODO

      thisElement.forEach((e) => e.removeEventListener(eventName, handler));
      return this;
    },
  };

  function find(el, selector = null) {
    if (selector === null) {
      selector = el;
      el = document;
    }
    selector = selector.split(",");
    var elements = [];
    selector.map((selector) => {
      try {
        el.querySelectorAll(selector).forEach((newElement) =>
          elements.push(newElement)
        );
      } catch (e) {
        findPatch(selector).forEach((newElement) => elements.push(newElement));
      }
    });
    return elements;
  }

  function findPatch(selector) {
    //TODO patch the find for :not
    console.log("Bad find: ", selector);
    return [];
  }

  function ready(fn) {
    if (document.readyState != "loading") {
      fn();
    } else {
      document.addEventListener("DOMContentLoaded", fn);
    }
  }

  function createFromHtml(htmlString) {
    var div = document.createElement("div");
    div.innerHTML = htmlString.trim();

    // Change this to div.childNodes to support multiple top-level nodes
    return div.childNodes;
  }

  function kebabToCamelCase(kebabCase) {
    var parts = kebabCase.toLowerCase().split("-");
    return parts
      .map((part, index) =>
        index === 0 ? part : part.charAt(0).toUpperCase() + part.substr(1)
      )
      .join("");
  }
};

DOM.fn.isFunction = function (exp) {
  return typeof exp === "function";
};
//DOM.fn = DOM.prototype;

window.$ = DOM;
window.jQuery = window.$;
