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

console.log("cookiedata", cookieData);

ready(() => {
  if (cookieData.method) {
    selectElements("#cfw-customer-info #date").forEach((e) => {
      e.placeholder = e.placeholder.replace("order", cookieData.method);
    });
    selectElements('#cfw-customer-info label[for="date"]').forEach((e) => {
      e.innerHTML = ucFirst(cookieData.method) + " " + e.innerHTML;
    });
  }

  addEventListener("click", ".cfw-next-tab", function (e) {
    //TODO validations
    console.log("click", this);
  });
});

function selectElements(selector, context = document) {
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

/**
 * these 2 shold really be in a util file
 * @param {} str
 */
function ucFirst(str) {
  if (!str) return "";
  return str.charAt(0).toUpperCase() + str.substr(1).toLowerCase();
}

function ucWords(str) {
  if (!str) return "";
  return str
    .split(" ")
    .map((p) => ucFirst(p))
    .join(" ");
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
