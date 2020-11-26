//import "@babel/polyfill";

//import MicroModal from "micromodal";
import autoComplete from "@tarekraafat/autocomplete.js";
//import fetch from "unfetch";
//import modalConfig from "./util/modalConfig";
import ModalService from "./services/modalService";
import { ucWords, ucFirst, deslugify } from "./util/string-helpers";
import { ready, addEventListener, removeEventListener } from "./util/dom-help";
import barba from "@barba/core";
import throttle from "./util/throttle";

const Cookies = window.Cookies;
const data = window.store_chooser_data;

const cookieName = data.cookie_name;

//console.log(data);

//const MdCheck = `<svg class="icon" stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 24 24" height="1em" width="1em" xmlns="http://www.w3.org/2000/svg"> <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"></path> </svg>`;

const cookieData = (() => {
  try {
    return JSON.parse(Cookies.get(cookieName));
  } catch (e) {
    return null;
  }
})();
//console.log(data, Cookies.get(data.cookie_name));

let method = "";
let postcode = "";
let suburb = "";
let location = "";

if (cookieData) {
  if (cookieData.method) method = cookieData.method;
  if (cookieData.suburb && cookieData.postcode) {
    suburb = cookieData.suburb;
    postcode = cookieData.postcode;
  }
  if (cookieData.location) location = cookieData.location;
}

let initialRender = false;
ready(() => {
  maybeShowModal();

  //Bind data
  barba.hooks.afterEnter(renderDomData());
  if (!initialRender) renderDomData();

  //Event listeners
  bindEvents();
});

function bindEvents() {
  addEventListener("click", "[data-sc-show]", showModal);
  document.addEventListener("store-chooser::show", showModal);
  document.addEventListener("store-chooser::maybe_show", maybeShowModal);
}

function renderDomData() {
  initialRender = true;
  //Generic data binding
  const _d = {
    method,
    postcode,
    suburb,
    location,
    method_display: ucFirst(method),
    postcode_display: postcode,
    suburb_display: ucWords(suburb),
    location_display: ucWords(deslugify(location)),
  };

  Array.from(document.querySelectorAll("[data-sc-val]")).forEach((el) => {
    if (el.tagName === "input") {
      el.value = _d[el.dataset.scVal];
    } else {
      el.innerHTML = _d[el.dataset.scVal];
    }
  });

  function parseConditional(obj, context = {}) {
    return Function('"use strict";var _ctx=arguments[0]; return (' + obj + ")")(
      context
    );
  }

  //Conditional render
  Array.from(document.querySelectorAll("[data-sc-if]")).forEach((el) => {
    let condition = el.dataset["scIf"];
    //if (condition.length > 50) return;
    //Replace variables from our data
    const reg = /%([a-z0-9-_]+)%/g;
    let result;
    while ((result = reg.exec(condition))) {
      if (typeof _d[result[1]] !== `undefined`) {
        condition =
          condition.substr(0, result.index) +
          `_ctx['${result[1]}']` +
          condition.substr(result.index + result[0].length);
      }
    }

    let conditionResult = false;
    try {
      conditionResult = !!parseConditional(condition, _d);
    } catch (e) {
      conditionResult = false;
    }
    if (!conditionResult) {
      el.style.display = "none";
    } else {
      el.style.display = "";
    }

    if (
      el.nextElementSibling &&
      el.nextElementSibling.dataset &&
      typeof el.nextElementSibling.dataset[`scElse`] !== "undefined"
    ) {
      if (conditionResult) {
        el.nextElementSibling.style.display = "none";
      } else {
        el.nextElementSibling.style.display = "";
      }
    }
  });

  //Custom
  Array.from(
    document.querySelectorAll('[data-sc-tpl="header_string"]')
  ).forEach((el) => {
    if (!haveRequiredData()) return;
    let str =
      _d.method_display +
      " " +
      (method === "pickup" ? "from" : "to") +
      " " +
      (method === "pickup" ? _d.location_display : _d.suburb_display);
    el.innerHTML = str;
  });
}

/**
 * This is needed in a couple of places, so we've abstracted it
 * Essentially check whether we have the required combination of
 * method, postcode, suburb & location
 *
 * Currently this is:
 * always need method
 * if pickup, need location
 * if delivery need all 3 (location is fetched via ajax in form)
 */
function haveRequiredData() {
  if (!method) return false;
  if (method === "pickup" && !location) return false;
  if (method === "delivery" && (!location || !postcode || !suburb))
    return false;
  return true;
}

function maybeShowModal() {
  if (!haveRequiredData()) {
    showModal();
  }
}

/**
 * Open the modal
 * also init micromodal if not already
 */
function showModal() {
  ModalService.show("modal-store-chooser", {
    onShow: onShowModal,
    onClose: onCloseModal,
  });
}

/**
 * MODAL METHODS
 */
const saveForm = () => {
  //if (!method || !suburb || !postcode || !location) return;
  //We always need method & location. If delivery we need suburb & postcode

  if (!haveRequiredData()) {
    console.error("Some required data missing", {
      method,
      location,
      suburb,
      postcode,
    });
    return;
  }

  Cookies.set(
    cookieName,
    { method, suburb, postcode, location },
    { expires: 365 }
  );
  Cookies.set(cookieName + "_location", location, { expires: 365 }); // we also set the cookie name + location to just the location for use with the cache key

  renderDomData();

  window.location.reload();
};

/**
 * MODAL EVENT HANDLERS
 */
const onSavePostcode = () => {
  if (!postcode || !suburb || !location) return;
  method = "delivery";
  saveForm();
};

const onSelectLocation = function () {
  method = "pickup";
  location = this.dataset.scLocation;
  postcode = null;
  suburb = null;
  saveForm();
};

const cacheName = "wc_sc_postcode";
const fetchData = async (value) => {
  const cache = await caches.open(cacheName);
  let response;

  const cacheKey = data.ajax_url + "?action=postcode_search&q=" + value;

  try {
    response = await cache.match(cacheKey);
    if (response) response = response.clone();
  } catch (e) {
    //Do nothing
  }

  if (!response) {
    try {
      response = await fetch(data.ajax_url, {
        method: "POST",
        credentials: "include", // include, *same-origin, omit
        headers: {
          "Content-Type": "application/x-www-form-urlencoded; charset=utf-8",
        },
        body: "action=postcode_search&q=" + value,
      });

      if (response.ok) {
        //This is async, but we dont care about when it finishes, so no await
        cache.put(cacheKey, response.clone());
      }
    } catch (e) {
      console.error("Failed to fetch", e);
      //Do nothing
    }
  }

  if (response) {
    return await response.json();
  }
  return [];
};

/*function fetchSuburbs() {
  let resolve, reject, cancelled;
  const promise = new Promise((resolveFromPromise, rejectFromPromise) => {
    resolve = resolveFromPromise;
    reject = rejectFromPromise;
  });

  Promise.resolve().then(wrapWithCancel(fetchData)).then(resolve).then(reject);

  return {
    promise,
    cancel: () => {
      cancelled = true;
      reject({ reason: "cancelled" });
    },
  };

  function wrapWithCancel(fn) {
    return (data) => {
      if (!cancelled) {
        return fn(data);
      }
    };
  }
}*/

/**
 * Bind data  & events etc when modal opens
 */
function onShowModal(modal) {
  const saveButtonSelector = '[data-sc-action="save-postcode"]';

  //Initial state
  if (suburb && postcode) {
    modal.querySelector("#autoComplete").value = `${suburb} ${postcode}`;

    modal.querySelector(saveButtonSelector).removeAttribute("disabled");
  }

  Array.from(modal.querySelectorAll("[data-sc-location]")).forEach((button) => {
    if (location && method === "pickup") {
      if (button.dataset.scLocation !== location)
        button.classList.remove("btn-outline");
    } else {
      button.classList.remove("btn-outline");
    }
  });

  //BIND EVENTS
  addEventListener(
    "click",
    '[data-sc-action="save-postcode"]',
    onSavePostcode,
    modal
  );

  addEventListener("click", "[data-sc-location]", onSelectLocation, modal);

  const onSelection = (feedback) => {
    const selection = feedback.selection.value;
    // Render selected choice to selection div
    modal.querySelector("#autoComplete").value =
      selection.name + " " + selection.postcode;

    postcode = selection.postcode;
    suburb = selection.suburb;
    location = selection.location;

    if (postcode && suburb && location) {
      modal
        .querySelector('[data-sc-action="save-postcode"]')
        .removeAttribute("disabled");
    } else {
      modal
        .querySelector('[data-sc-action="save-postcode"]')
        .setAttribute("disabled", "disabled");
    }
  };

  //let promise, cancel;

  new autoComplete({
    data: {
      // Data src [Array, Function, Async] | (REQUIRED)
      src: async () => {
        const value = document.querySelector("#autoComplete").value;
        if (!value) {
          return [];
        }
        if (value.length < 3) return [];
        const data = await fetchData(value);
        console.log("fetched", data);
        return data;
      },
      key: ["name", "postcode"],
      cache: false, //caching is handled manually
    },
    placeHolder: "Postcode or suburb", // Place Holder text                 | (Optional)
    selector: "#autoComplete", // Input field selector              | (Optional)
    threshold: 2, // Min. Chars length to start Engine | (Optional)
    //searchEngine: "strict", // Search Engine type/mode           | (Optional)
    maxResults: 10, // Max. number of rendered results | (Optional)
    //highlight: true, // Highlight matching results      | (Optional)
    noResults: () => {
      const result = document.createElement("span");
      result.setAttribute("class", "no_result");
      result.setAttribute("tabindex", "1");
      result.innerHTML = "No Results";
      document.querySelector("#autoComplete_list").appendChild(result);
    },
    resultsList: {
      render: true,
      container: (source) => {
        source.setAttribute("id", "autoComplete_list");
      },
      destination: document.querySelector("#autoComplete"),
      position: "afterend",
      element: "div",
    },
    resultItem: {
      content: (data, source) => {
        //console.log("item", data);
        //source.innerHTML = data.match;
        //source.innerHTML = data.value.postcode + " " + data.value.name;
        const str = data.value.name + " " + data.value.postcode;
        const val = document.querySelector("#autoComplete").value.toLowerCase();

        const strMatch = str.toLowerCase().split(val);
        if (strMatch.length === 2) {
          source.innerHTML = "";
          source.appendChild(
            document.createTextNode(str.substr(0, strMatch[0].length))
          );
          const span = document.createElement("span");
          span.classList.add("font-bold");
          span.innerHTML = str.substr(strMatch[0].length, val.length);
          source.appendChild(span);
          source.appendChild(
            document.createTextNode(str.substr(strMatch[0].length + val.length))
          );
        } else {
          source.innerHTML = str;
        }
      },
      element: "button",
    },
    onSelection: onSelection,
  });

  ["focus", "blur"].forEach(function (eventType) {
    const resultsList = document.querySelector("#autoComplete_list");

    document
      .querySelector("#autoComplete")
      .addEventListener(eventType, function () {
        // Hide results list & show other elemennts
        if (eventType === "blur") {
          resultsList.style.display = "none";
        } else if (eventType === "focus") {
          // Show results list & hide other elemennts
          resultsList.style.display = "block";
        }
      });
  });
}

function onCloseModal(modal) {
  //TODO event listeners are not removed

  removeEventListener("click", "[data-sc-location]", onSelectLocation, modal);
}
