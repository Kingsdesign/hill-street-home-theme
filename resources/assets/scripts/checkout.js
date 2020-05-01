import { ucFirst } from "./util/string-helpers";
import { ready, selectElements, addEventListener } from "./util/dom-help";
import {
  startOfToday,
  add,
  isAfter,
  startOfTomorrow,
  isBefore,
} from "date-fns";

const data = window.custom_checkout_data;
const Cookies = window.Cookies;

const cookieName = data.cookie_name;

const cookieData = (() => {
  try {
    return JSON.parse(Cookies.get(cookieName));
  } catch (e) {
    return null;
  }
})();

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
  const datePicker = document.querySelector("#date.checkout-date-picker");
  if (datePicker) {
    //datePicker.datepicker('option','minDate')
    const cutoffTime = add(startOfToday(), { hours: 11 }); //11am today
    const isAfterCutoff = isAfter(new Date(), cutoffTime);

    let minDate = startOfToday();
    if (isAfterCutoff) {
      minDate = startOfTomorrow();
    }
    $(datePicker).datepicker("option", "minDate", minDate);
    //$(datePicker).datepicker("option", "defaultDate", 1);
    /*$(datePicker).datepicker("option", "beforeShowDay", (date) => {
      if (isBefore(date, minDate)) return [false];
      return [true];
    });*/
  }
  //$("#date.date-picker").datepicker("option", "minDate", new Date(2007, 1 - 1, 1));
});
