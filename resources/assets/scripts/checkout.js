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
} from "date-fns";

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

  if (data.date_restrictions.length) {
    $(datePicker).datepicker("option", "beforeShowDay", (showDate) => {
      let isEnabled = true;
      data.date_restrictions.restrictions.forEach((restriction) => {
        // if (restriction.type !== "disable") return;
        //If the restriction is not for the current method, we don't care
        if (restriction.location.indexOf(method) === -1) return;
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
