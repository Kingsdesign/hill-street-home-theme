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

  /*addEventListener("click", ".cfw-next-tab", function (e) {
    //TODO validations
    console.log("click", this);
  });*/

  //Restrict date picker options
  restrictDatePicker();
  //$("#date.date-picker").datepicker("option", "minDate", new Date(2007, 1 - 1, 1));

  initValidateDate();
});

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

  if (data.date_restrictions.restrictions.length) {
    $(datePicker).datepicker("option", "beforeShowDay", (showDate) => {
      let isEnabled = true;
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

      //CUSTOM restriction for devonport deliveries on sundays
      if (
        location === "devonport" &&
        method === "delivery" &&
        +cookieData.postcode !== 7310 &&
        isSunday(showDate)
      ) {
        console.log("NO DELIVERY FOR DEVONPORT ON SUNDAY YO EXCEPT TO 7310");
        isEnabled = false;
      }

      return [isEnabled];
    });
  }
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
