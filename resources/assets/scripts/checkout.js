import { ucFirst } from "./util/string-helpers";
import { ready, selectElements, addEventListener } from "./util/dom-help";
import {
  startOfToday,
  add,
  isAfter,
  startOfTomorrow,
  isBefore,
  startOfDay,
  parse,
  isEqual,
  isSameDay,
  endOfYesterday,
  endOfToday,
  endOfDay,
  isSunday,
  isSaturday,
  format,
} from "date-fns";

import $ from "jquery";

const data = window.custom_checkout_data;
const Cookies = window.Cookies;

const cookieName = data.cookie_name;

const cookieData = getCookieData();

//console.log("cookiedata", cookieData);

ready(() => {
  //Conditional label/placeholer on date
  if (cookieData.method) {
    selectElements("#cfw-customer-info #date").forEach((e) => {
      e.placeholder = e.placeholder.replace("order", cookieData.method);
    });
    selectElements('#cfw-customer-info label[for="date"]').forEach((e) => {
      e.innerHTML = ucFirst(cookieData.method) + " " + e.innerHTML;
    });
  }

  // addEventListener("click", ".cfw-next-tab", function (e) {
  //   //TODO validations
  //   e.preventDefault();
  // });

  $(window.cfwEventData.elements.tabContainerElId).on(
    "easytabs:before",
    function (event, $clicked, $targetPanel, settings) {
      const $currentPanel = $(
        window.cfwEventData.elements.tabContainerElId + " .cfw-panel.active"
      );
      if ($currentPanel[0].id === "cfw-customer-info") {
        var $phone = $currentPanel.find("#phone_field #phone");
        if ($phone && $phone.get(0) && $phone.parsley().validate() !== true) {
          return false;
        }

        var $date = $currentPanel.find("#date_field #date");
        if ($date && $date.get[0] && $date.parsley().validate() !== true) {
          return false;
        }

        var $time = $currentPanel.find('#time_field [name="time"]');
        var timeValid = true;
        $time.each(function () {
          $(this).attr("data-parsley-required", true);
          if ($(this).parsley().validate() !== true) {
            timeValid = false;
          }
        });
        if (!timeValid) {
          return false;
        }
      }
    }
  );

  setTimeout(() => {
    restrictDatePicker();
  });

  //Restrict date picker options
  //$("#date.date-picker").datepicker("option", "minDate", new Date(2007, 1 - 1, 1));

  initValidateDate();

  initCardMessage();
  addFulfilmentDateDisclaimer();
});

function addFulfilmentDateDisclaimer() {
  const fulfilmentDateField = document.querySelector(
    ".cfw-input-wrap-row #date_field"
  );
  if (!fulfilmentDateField) return;
  const el = document.createElement("div");
  el.classList.add("fulfilment-date-disclaim");
  el.classList.add("formHelp");
  el.innerHTML = window.hsh_checkout.fulfilment_date_disclaimer;
  fulfilmentDateField.appendChild(el);
}

function initCardMessage() {
  const cardMessageField = document.getElementById("card_message_field");
  if (!cardMessageField) return;

  const cardMessageInput = cardMessageField.querySelector("#card_message");
  if (!cardMessageInput) return;

  const maxlength = +cardMessageInput.getAttribute("maxlength");
  console.log({ maxlength });
  if (!maxlength) return;

  const cardMessageDisplay = document.createElement("div");
  cardMessageDisplay.className = "card-message-maxlength";
  cardMessageDisplay.classList.add("formHelp");
  const cardMessageDisplayText = (length) =>
    `${length}/${maxlength} characters`;

  const doValidation = () => {
    const length = cardMessageInput.value.length;
    cardMessageDisplay.innerHTML = cardMessageDisplayText(length);

    if (length === maxlength) {
      cardMessageDisplay.classList.add("card-message-maxlength--invalid");
    } else if (
      cardMessageDisplay.classList.contains("card-message-maxlength--invalid")
    ) {
      cardMessageDisplay.classList.remove("card-message-maxlength--invalid");
    }
  };

  const handleKeyup = () => {
    doValidation();
  };

  cardMessageField.appendChild(cardMessageDisplay);
  doValidation();

  cardMessageInput.addEventListener("keyup", handleKeyup);
}

function restrictDatePicker() {
  const datePicker = document.querySelector("#date.checkout-date-picker");
  if (!datePicker) return;

  //The min date is today
  let minDate = startOfToday();
  let cutoffTime = null;

  //Get cookie data
  const location = cookieData.location;
  const method = cookieData.method;
  if (!location || !method) return;

  //Get the default for the location&method
  const defaultSetting =
    data.date_restrictions.defaults[location] &&
    data.date_restrictions.defaults[location][method]
      ? data.date_restrictions.defaults[location][method]
      : null;

  if (defaultSetting) {
    minDate = add(startOfToday(), { days: +defaultSetting.day_offset });
    if (defaultSetting.time_cutoff) {
      let cutoffTimeParts = defaultSetting.time_cutoff.split(":");
      let timeOffset = {};
      if (typeof cutoffTimeParts[0] !== "undefined")
        timeOffset.hours = cutoffTimeParts[0];
      if (typeof cutoffTimeParts[1] !== "undefined")
        timeOffset.minutes = cutoffTimeParts[1];
      cutoffTime = add(startOfToday(), timeOffset);
    }
  }

  //If there is a cutoff time, and it has passed
  if (cutoffTime && isAfter(new Date(), cutoffTime)) {
    minDate = add(minDate, { days: 1 });
  }

  //Set the mindate
  $(datePicker).datepicker("option", "minDate", minDate);

  //$(datePicker).datepicker("option", "defaultDate", 1);
  /*$(datePicker).datepicker("option", "beforeShowDay", (date) => {
      if (isBefore(date, minDate)) return [false];
      return [true];
    });*/

  //Is between, not inclusive of end date
  const isBetween = (testDate, startDate, endDate) => {
    return (
      isBefore(startDate, endOfDay(testDate)) &&
      isAfter(endDate, startOfDay(testDate))
    );
  };

  $(datePicker).datepicker("option", "beforeShowDay", (showDate) => {
    let isEnabled = true;
    if (data.date_restrictions.restrictions.length) {
      data.date_restrictions.restrictions.forEach((restriction) => {
        // if (restriction.type !== "disable") return;
        //If the restriction is not for the current method, we don't care

        if (restriction.location.indexOf(location) === -1) return;
        if (restriction.method.indexOf(method) === -1) return;

        const date = startOfDay(
          parse(restriction.date, "yyyy-MM-dd", new Date())
        );
        const end_date = restriction.end_date
          ? startOfDay(parse(restriction.end_date, "yyyy-MM-dd", new Date()))
          : null;

        if (end_date && !isBefore(date, end_date)) return;

        //If showDate is the same as date, or if it's a range, is within range (inclusive)
        const shouldApplyRestriction =
          isSameDay(showDate, date) ||
          (end_date &&
            (isBetween(showDate, date, end_date) ||
              isSameDay(end_date, showDate)));

        if (!shouldApplyRestriction) return;

        if (restriction.type === "disable") {
          isEnabled = false;
          return;
        }
      });
    }

    // Restrict by day of week
    if (
      data.date_restrictions.days &&
      typeof data.date_restrictions.days[location] !== "undefined" &&
      typeof data.date_restrictions.days[location][method] !== "undefined"
    ) {
      if (
        data.date_restrictions.days[location][method].indexOf(
          format(showDate, "EEEE").toLowerCase()
        ) > -1
      ) {
        isEnabled = false;
      }
    }

    //CUSTOM restriction for devonport deliveries on sundays
    if (
      location === "devonport" &&
      method === "delivery" &&
      +cookieData.postcode !== 7310 &&
      isSaturday(showDate)
      // (isSunday(showDate) || isSaturday(showDate)) // DEVNOPORT DELIVERY TEMP MOTHERSDAY
    ) {
      isEnabled = false;
    }

    return [isEnabled];
  });
}

function getCookieData() {
  try {
    return JSON.parse(Cookies.get(cookieName));
  } catch (e) {
    return null;
  }
}

function initValidateDate() {
  loadDateFromStorage();
  bindValidationEvents();
  window.addEventListener("hashchange", bindValidationEvents, false);
}

function loadDateFromStorage() {
  const date = document.getElementById("date");
  if (!date || date.value) return;
  let value = window.localStorage.getItem("hsh-checkout-date");
  if (value) {
    try {
      value = JSON.parse(value);
    } catch (e) {
      value = null;
    }
  }
  if (
    value &&
    value.value &&
    value.timestamp &&
    Date.now() - value.timestamp < 1000 * 60 * 30
  ) {
    date.value = value.value;
  }
}

function bindValidationEvents() {
  //First get the active tab
  const activeTab = document.querySelector(".cfw-panel.active");
  const activeTabID = activeTab.id;
  if (activeTabID !== `cfw-customer-info`) return;

  disableTabChange();
  const dateInput = document.getElementById("date");
  dateInput.setAttribute("readonly", "readonly");
  dateInput.addEventListener("change", handleDateChange);
  $(dateInput).datepicker("option", "onSelect", handleDateChange);

  if (validateDate()) {
    enableTabChange();
  }
}

function disableTabChange() {
  Array.from(document.querySelectorAll("[data-tab]")).forEach(
    disableTabChangeEl
  );
  Array.from(document.querySelectorAll('a[href^="#cfw"]')).forEach(
    disableTabChangeEl
  );
}
function disableTabChangeEl(el) {
  el.setAttribute("disabled", "disabled");
  el.classList.add("disabled");
  if (el.dataset.tab) {
    el.setAttribute("data-tab-disabled", el.dataset.tab);
    el.removeAttribute("data-tab");
  }
  var href;
  if (el.href && (href = el.href.match(/#(cfw-(.*?))$/))) {
    el.setAttribute("data-tab-href", href[0]);
    el.setAttribute("href", "#");
  }
  el.addEventListener("click", handleTabChangeEl);
  el.parentNode.replaceChild(el, el);
}
function enableTabChange() {
  Array.from(document.querySelectorAll("[data-tab-disabled]")).forEach(
    enableTabChangeEl
  );
  Array.from(document.querySelectorAll("[data-tab-href]")).forEach(
    enableTabChangeEl
  );
}
function enableTabChangeEl(el) {
  el.removeAttribute("disabled");
  el.classList.remove("disabled");
  if (el.dataset.tabDisabled) {
    el.setAttribute("data-tab", el.dataset.tabDisabled);
    el.removeAttribute("data-tab-disabled");
  }
  if (el.dataset.tabHref) {
    el.setAttribute("href", el.dataset.tabHref);
    el.removeAttribute("data-tab-href");
  }
  el.removeEventListener("click", handleTabChangeEl);
}
function handleDateChange(e) {
  const value = typeof e === "object" ? "" : e;
  window.localStorage.setItem(
    "hsh-checkout-date",
    JSON.stringify({ value, timestamp: Date.now() })
  );
  if (validateDate()) {
    enableTabChange();
    removeInvalidDateMessage();
  } else {
    disableTabChange();
    showInvalidDateMessage();
  }
}
function handleTabChangeEl(e) {
  e.preventDefault();
  if (validateDate()) {
    enableTabChange();
    //  this.dispatchEvent(e);
    return;
  }
  showInvalidDateMessage();
}
function validateDate() {
  const dateEl = document.getElementById("date");
  const dateValue = dateEl.value;
  if (dateValue) {
    return true;
  }
  return false;
}
function showInvalidDateMessage() {
  if (document.getElementById("date-error")) return;
  const dateEl = document.getElementById("date");
  const parent = dateEl.parentNode;
  const errorMessage = document.createElement("li");
  errorMessage.className = "date-error";
  errorMessage.style.display = "block";
  errorMessage.innerText = "This value is required.";

  const errorMessageList = document.createElement("ul");
  errorMessageList.className = "parsley-errors-list filled";
  errorMessageList.id = "date-error";
  errorMessageList.appendChild(errorMessage);

  parent.insertAdjacentElement("afterend", errorMessageList);
}
function removeInvalidDateMessage() {
  const errorMessageList = document.getElementById("date-error");
  if (errorMessageList) {
    errorMessageList.parentNode.removeChild(errorMessageList);
  }
}
