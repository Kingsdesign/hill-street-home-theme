class EventHandler {
  constructor(eventName, elementSelector, handler, context = document) {
    this.eventName = eventName;
    this.elementSelector = elementSelector;
    this.handler = handler;
    this.context = context;
    this.context.addEventListener(eventName, this);
    //this.destroy = this.destroy.bind(this);
  }

  handleEvent(e) {
    if (e.type !== this.eventName) return;
    // loop parent nodes from the target to the delegation node
    for (
      var target = e.target;
      target && target != this.context;
      target = target.parentNode
    ) {
      if (target.matches(this.elementSelector)) {
        this.handler.call(target, e, target);
        break;
      }
    }
  }

  destroy() {
    this.context.removeEventListener(this.eventName, this);
  }

  isEventHandler(eventName, elementSelector, handler, context) {
    return (
      this.eventName === eventName &&
      this.elementSelector === elementSelector &&
      this.handler === handler &&
      this.context === context
    );
  }
}

const eventHandlers = [];

function addEventToStore(eventHandler) {
  eventHandlers.push(eventHandler);
}

function findEventInStore({ eventName, elementSelector, handler, context }) {
  const index = eventHandlers.findIndex((eH) =>
    eH.isEventHandler(eventName, elementSelector, handler, context)
  );
  if (index === -1) return null;
  return { index, eventHandler: eventHandlers[index] };
}

export function addEventListener(
  eventName,
  elementSelector,
  handler,
  context = document
) {
  const eventHandler = new EventHandler(
    eventName,
    elementSelector,
    handler,
    context
  );

  addEventToStore(eventHandler);
}

export function removeEventListener(
  eventName,
  elementSelector,
  handler,
  context = document
) {
  //Prevent possible dupes
  let haveHandlers = true;
  while (haveHandlers) {
    const eH = findEventInStore({
      eventName,
      elementSelector,
      handler,
      context,
    });
    if (!eH) {
      haveHandlers = false;
    } else {
      eH.eventHandler.destroy();
      eventHandlers.splice(eH.index, 1);
    }
  }
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
