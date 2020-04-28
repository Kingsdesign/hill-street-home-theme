//import "@babel/polyfill";

//import MicroModal from "micromodal";
import autoComplete from "@tarekraafat/autocomplete.js";
import fetch from "unfetch";
import modalConfig from "./util/modalConfig";
import ModalService from "./services/modalService";

const Cookies = window.Cookies;
const data = window.store_chooser_data;

const cookieName = data.cookie_name;

console.log(data);

const MdCheck = `<svg class="icon" stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 24 24" height="1em" width="1em" xmlns="http://www.w3.org/2000/svg"> <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"></path> </svg>`;

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

ready(() => {
  if (!method || !postcode || !suburb || !location) {
    showModal();
  }

  //Bind data
  renderDomData();

  //Event listeners
  bindEvents();
});

function bindEvents() {
  addEventListener("click", "[data-sc-show]", showModal);
}

function ucFirst(str) {
  if (!str) return "";
  return str.charAt(0).toUpperCase() + str.substr(1).toLowerCase();
}

/**
 * take a location slug (e.g. west-hobart)
 * and convert it to a display name
 * @param {} slug
 */
function locationToDisplay(slug) {
  const parts = slug.split(/[-_]/g);
  return parts.map((p) => ucFirst(p)).join(" ");
}

function renderDomData() {
  //Generic data binding
  const _d = {
    method,
    postcode,
    suburb,
    location,
    method_display: ucFirst(method),
    postcode_display: postcode,
    suburb_display: ucFirst(suburb),
    location_display: locationToDisplay(location),
  };
  Array.from(document.querySelectorAll("[data-sc-val]")).forEach((el) => {
    if (el.tagName === "input") {
      el.value = _d[el.dataset.scVal];
    } else {
      el.innerHTML = _d[el.dataset.scVal];
    }
  });

  //Conditional render
  Array.from(document.querySelectorAll("[data-sc-if]")).forEach((el) => {
    const key = el.dataset["scIf"];
    //console.log("testing if", key, _d[key]);
    if (!_d[key]) {
      el.style.display = "none";
    } else {
      el.style.display = "";
    }
  });

  //Custom
  Array.from(
    document.querySelectorAll('[data-sc-tpl="header_string"]')
  ).forEach((el) => {
    if (!method || !postcode || !suburb) return;
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
 * Open the modal
 * also init micromodal if not already
 */
function showModal() {
  /*MicroModal.init(modalConfig);

  MicroModal.show("modal-store-chooser", {
    onShow: onShowModal,
  });*/
  ModalService.show("modal-store-chooser", { onShow: onShowModal });
}

/**
 * Bind data  & events etc when modal opens
 */
function onShowModal(modal) {
  //modalConfig.onShow(modal);
  /*const [state, setState] = State(
    {
      method: "",
      postcode: "",
      suburb: "",
    },
    
  );*/

  /**
   * DONT USE OLD VAL RIGHT NOW
   */
  const sideEffects = {
    method: (oldVal, newVal) => {
      //console.log("method changed", { oldVal, newVal });
      //Method updated
      const selectedButton = document.querySelector(
        `#modal-store-chooser--content #sc-${newVal}`
      );
      const buttons = document.querySelectorAll(
        `#modal-store-chooser--content #sc-delivery,#modal-store-chooser--content #sc-pickup`
      );
      Array.from(buttons).forEach((btn) => {
        btn.classList.add("btn-outline");
        Array.from(btn.querySelectorAll(".icon")).forEach((icon) =>
          icon.remove()
        );
      });
      if (!selectedButton) return;
      selectedButton.classList.remove("btn-outline");
      const iconTmp = document.createElement("div");
      iconTmp.innerHTML = MdCheck;
      const icon = iconTmp.querySelector("svg");
      selectedButton.appendChild(icon);

      validateForm();
    },
    postcode: (oldVal, newVal) => {
      //  console.log("postcode changed", { oldVal, newVal });
      validateForm();
    },
    suburb: (oldVal, newVal) => {
      // console.log("suburb changed", { oldVal, newVal });
      validateForm();
    },
    location: (oldVal, newVal) => {
      // console.log("suburb changed", { oldVal, newVal });
      validateForm();
    },
  };

  const setMethod = function (e) {
    if (this.dataset.scMethod) {
      //setState({ method: this.dataset.scMethod });
      method = this.dataset.scMethod;
      sideEffects.method(method, this.dataset.scMethod);
    }
  };

  const onSelection = (feedback) => {
    const selection = feedback.selection.value;
    //console.log("Selection: ", selection);
    // Render selected choice to selection div
    document.querySelector("#autoComplete").value =
      selection.name + " " + selection.postcode;

    /*setState({
      postcode: selection.postcode,
      suburb: selection.suburb,
    });*/
    postcode = selection.postcode;
    suburb = selection.suburb;
    location = selection.location;
    sideEffects.postcode(postcode, selection.postcode);
    sideEffects.suburb(suburb, selection.suburb);
    sideEffects.location(location, selection.location);
  };

  const validateForm = () => {
    if (method && postcode && suburb) {
      const btn = document.querySelector(
        "#modal-store-chooser--content #sc-save"
      );
      if (!btn) return;

      btn.removeAttribute("disabled");

      renderDomData();
    } /*else {

    }*/
  };

  const saveForm = () => {
    if (!method || !suburb || !postcode || !location) return;

    Cookies.set(
      cookieName,
      { method, suburb, postcode, location },
      { expires: 365 }
    );
    //MicroModal.close("modal-store-chooser");
    renderDomData();

    window.location.reload();
  };

  addEventListener("click", "[data-sc-method]", setMethod);
  addEventListener("click", "#sc-save", saveForm);

  //Set inital values
  if (method) {
    sideEffects.method("", method);
  }
  if (suburb && postcode) {
    document.querySelector("#autoComplete").value = `${suburb} ${postcode}`;
    //sideEffects.suburb("",lo)
  }

  new autoComplete({
    data: {
      // Data src [Array, Function, Async] | (REQUIRED)
      src: () => {
        const value = document.querySelector("#autoComplete").value;
        if (!value) {
          //console.log("NO VALUE!");
          return [];
        }
        if (value.length < 3) return [];

        return fetch(data.ajax_url, {
          method: "POST",
          credentials: "include", // include, *same-origin, omit
          headers: {
            "Content-Type": "application/x-www-form-urlencoded; charset=utf-8",
          },
          body: "action=postcode_search&q=" + value,
        })
          .then((r) => {
            if (r.ok) return r;
            throw new Error(r.statusText);
          })
          .then((r) => r.json())
          .then((resp) => {
            console.log(resp);
            if (!resp) {
              //console.log("NO RESPONSE!");
              return [];
            }
            //console.log("resp", resp);
            /*const lines = resp.trim().split("\n");
            const suburbs = lines
              .map((line) => {
                if (!line) return false;
                const lineParts = line.split("|");
                if (lineParts.length < 4) return false;
                return {
                  name: lineParts[2],
                  suburb: lineParts[2],
                  postcode: lineParts[1],
                  state: lineParts[3],
                };
              })
              .filter((s) => !!s);
            //console.log("Suburbs", suburbs);
            return suburbs;*/
            return resp;
          });
      },
      key: ["name", "postcode"],
      cache: false,
    },
    placeHolder: "Postcode or suburb", // Place Holder text                 | (Optional)
    selector: "#autoComplete", // Input field selector              | (Optional)
    threshold: 2, // Min. Chars length to start Engine | (Optional)
    //searchEngine: "strict", // Search Engine type/mode           | (Optional)
    //maxResults: 5, // Max. number of rendered results | (Optional)
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

function ready(fn) {
  if (document.readyState != "loading") {
    fn();
  } else {
    document.addEventListener("DOMContentLoaded", fn);
  }
}

function addEventListener(eventName, elementSelector, handler) {
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
          handler.call(target, e);
          break;
        }
      }
    },
    false
  );
}
