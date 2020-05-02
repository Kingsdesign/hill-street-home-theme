export function addEventListener(eventName, elementSelector, handler) {
  document.addEventListener(
    eventName,
    function (e) {
      // loop parent nodes from the target to the delegation node
      for (
        var target = e.target;
        target && target != this;
        target = target.parentNode
      ) {
        if (target.matches(elementSelector)) {
          handler.call(target, e, target);
          break;
        }
      }
    },
    false
  );
}

export function ready(fn) {
  if (document.readyState != "loading") {
    fn();
  } else {
    document.addEventListener("DOMContentLoaded", fn);
  }
}

export function selectElements(selector, context = document) {
  const el = context.querySelectorAll(selector);
  let o = {};
  Array.from(el).forEach((e, i) => {
    o[i] = e;
  });
  o.forEach = (cb) => {
    Array.from(el).forEach(cb);
  };
  return o;
}

export function trigger(eventName, el = document, data = {}) {
  let event;
  if (window.CustomEvent && typeof window.CustomEvent === "function") {
    event = new CustomEvent(eventName, { detail: data });
  } else {
    event = document.createEvent("CustomEvent");
    event.initCustomEvent(eventName, true, true, data);
  }

  el.dispatchEvent(event);
}
