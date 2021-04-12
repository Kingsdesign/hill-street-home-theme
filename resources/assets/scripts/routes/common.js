import lozad from "lozad";
import barba from "@barba/core";
//import MicroModal from "micromodal";
import cloudinary from "cloudinary-core";

import Router from "../util/Router";
import home from "./home";
import archive from "./archive";
//import aboutUs from "./about";
import singleProduct from "./singleProduct";

import { addEventListener } from "../util/dom-help";

import { trigger } from "../util/dom-help";

//DO NOT REMOVE THIS IMPORT - it is used
import ModalService from "../services/modalService";

window.barba = barba;

/*export default {
  init() {
    // JavaScript to be fired on all pages
    const observer = lozad();
    observer.observe();
  },
  finalize() {
    // JavaScript to be fired on all pages, after page specific JS is fired
  },
};*/

function diffScripts(currentScripts, newScripts) {
  const removed = currentScripts.filter((x) => !includes(x, newScripts));
  const added = newScripts.filter((x) => !includes(x, currentScripts));

  return { added, removed };

  function includes(script, array) {
    return !!array.find((s) => s.src === script.src);
  }
}

const observer = lozad();
let routes;

function siteNotice() {
  const localStorageKey = "hsh-site-notice";
  const notice = document.querySelector(".site-notice");
  if (!notice) return;

  const noticeId = notice.getAttribute("data-id");
  if (noticeId === localStorage.getItem(localStorageKey)) {
    notice.remove();
    return;
  }

  notice.classList.remove("hidden");
  addEventListener("click", ".site-notice .site-notice-dismiss", function (e) {
    e.preventDefault();
    notice.remove();
    localStorage.setItem(localStorageKey, noticeId);
    document.body.style.marginTop = 0;
  });

  if (notice.classList.contains("fixed")) {
    document.body.style.marginTop =
      notice.getBoundingClientRect().height + "px";
  }
}

function commonFinalize() {
  siteNotice();
}

export default {
  init() {
    //let currentScripts = null;

    //MicroModal.init(modalConfig);

    observer.observe();

    //var cl = cloudinary.Cloudinary.new({ cloud_name: "hshome" });
    // replace 'demo' with your cloud name in the line above
    //cl.responsive();

    barba.init({
      timeout: 10000,
      requestError: (trigger, action, url, response) => {
        if (action === "click" && typeof response.status === `undefined`) {
          //console.log("Timeout, go to: ", url);
          window.location.assign(url);
        }

        //console.log("Error", { trigger, action, url, response });
        return false;
      },
      prevent: (args) => {
        if (args.href.match(/\/checkout\/?$/)) return true;
        if (args.el.classList.contains("ajax_add_to_cart")) return true;
        if (
          document
            .getElementsByTagName("body")[0]
            .classList.contains("admin-bar") &&
          document.getElementById("wpadminbar").contains(args.el)
        )
          return true;
        return false;
      },
      cacheIgnore: ["/cart/"],
    });
    barba.hooks.afterEnter((data) => {
      if (document.body.classList.contains("barba-transitioned")) {
        document.body.classList.add("barba-transitioned");

        //var nextHtml = data.next.html;
        //replaceAssets(nextHtml);
      }

      trigger("store-chooser::maybe_show", document);

      observer.observe();
      routes = new Router({
        home,
        //aboutUs,
        singleProduct,
        archive,
      });
      setTimeout(() => {
        routes.loadEvents();

        trigger("hsh-fe::after_enter", document);

        commonFinalize();
      });
      //cl.responsive();
    });
    barba.hooks.beforeLeave((data) => {
      routes.unloadEvents();
    });
    barba.hooks.afterLeave((data) => {
      window.scrollTo(0, 0);
      // Set <body> classes for "next" page
      var nextHtml = data.next.html;

      // var response = nextHtml.replace(
      //   /(<\/?)body( .+?)?>/gi,
      //   "$1notbody$2>",
      //   nextHtml
      // );
      // var bodyClasses = $(response).filter("notbody").attr("class");
      // $("body").attr("class", bodyClasses + " barba-transitioned");

      replaceAssets(nextHtml);

      trigger("hsh-fe::after_leave", document);

      if (typeof window.ga !== "undefined") {
        window.ga("gtm1.send", {
          hitType: "pageview",
          page: location.pathname,
        });
      }
    });
  },

  finalize() {
    //Mobile nav
    let isOpen = false;

    const navLinkListner = () => {
      isOpen = false;
      isOpenSideEffect();
    };

    const isOpenSideEffect = () => {
      const primaryNav = document.getElementById("primary-navigation");
      if (isOpen) {
        primaryNav.classList.add("active");
        document.body.classList.add("noscroll");
        Array.from(primaryNav.querySelectorAll("a")).forEach((link) => {
          link.addEventListener("click", navLinkListner);
        });
      } else {
        primaryNav.classList.remove("active");
        document.body.classList.remove("noscroll");
        Array.from(primaryNav.querySelectorAll("a")).forEach((link) => {
          link.removeEventListener("click", navLinkListner);
        });
      }
    };

    addEventListener("click", '[data-mobile-nav="toggle"]', (e) => {
      isOpen = !isOpen;
      isOpenSideEffect();
    });
    addEventListener("click", '[data-mobile-nav="close"]', (e) => {
      if (isOpen === false) return;
      isOpen = false;
      isOpenSideEffect();
    });

    //Get cart count
    setTimeout(() => getCartCount(), 1000);
  },
};

function getCartCount() {
  const data = window.main_data;
  const cartIndicator = document.getElementById("header-cart-indicator");
  if (!cartIndicator) return;
  return fetch(data.ajax_url, {
    method: "POST",
    credentials: "include", // include, *same-origin, omit
    headers: {
      "Content-Type": "application/x-www-form-urlencoded; charset=utf-8",
    },
    body: "action=cart_count",
  })
    .then((r) => {
      if (r.ok) return r;
      console.error(`Cart count fetch failed`);
      throw new Error(r.statusText);
    })
    .then((r) => r.json())
    .then((resp) => {
      if (!resp || !resp.html) return;
      cartIndicator.innerHTML = resp.html;
    });
}

function diffAssets(
  currentAssets,
  newAssets,
  matchFn,
  { unchanged = false } = {}
) {
  let removedAssets = currentAssets.filter((x) => notIncludes(x, newAssets));
  let addedAssets = newAssets.filter((x) => notIncludes(x, currentAssets));

  let unchangedAssets = unchanged
    ? removedAssets.length
      ? currentAssets.filter((x) => notIncludes(x, removedAssets))
      : currentAssets
    : null;

  function includes(asset, arr) {
    let _includes = false;
    for (let i = 0; i < arr.length && !_includes; i++) {
      if (matchFn(asset, arr[i])) _includes = true;
    }
    return includes;
  }

  function notIncludes(asset, arr) {
    return !includes(asset, arr);
  }

  return {
    added: addedAssets,
    removed: removedAssets,
    unchanged: unchangedAssets,
  };
}

/**
 * Get all assets ( scripts and styles ) from a DOM
 * retain order within scripts/styles
 * @param {} context
 */
function getAssetsFromDOM(context) {
  const scripts = context.querySelectorAll("script");

  const styles = context.querySelectorAll('style, link[rel="stylesheet"]');

  return { scripts, styles };
}

//Super cheap css-in-js
function setStyle(el, style) {
  Object.keys(style).forEach((key) => {
    el.style[key] = style[key];
  });
}

function addOverlay() {
  const overlay = document.createElement("div");
  setStyle(overlay, {
    position: "fixed",
    top: 0,
    left: 0,
    width: "100%",
    height: "100%",
    background: "#fff",
    zIndex: 10000,
    opacity: 1,
    transition: "0.2s opacity ease-out",
  });
  overlay.id = "transition-overlay";
  document.body.appendChild(overlay);
  return overlay;
}

function showOverlay() {
  let overlay = document.getElementById("transition-overlay");
  if (!overlay) {
    overlay = addOverlay();
  } else {
    overlay.style.opacity = 1;
    overlay.style.display = "block";
  }
  return overlay;
}

function hideOverlay() {
  let overlay = document.getElementById("transition-overlay");
  if (overlay) {
    overlay.style.opacity = 0;
    setTimeout(() => {
      overlay.style.display = "none";
    }, 200);
  }
}

function replaceAssets(html) {
  const neverReloadScripts = [
    { id: "sage/main.js-js" },
    { id: "sage/store-chooser.js-js" },
    "/wp-includes/js/admin-bar.min.js",
  ];
  const neverReloadStyles = [
    "/wp-includes/css/dist/block-library/style.min.css",
    "/wp-includes/css/dashicons.min.css",
    "/wp-includes/css/admin-bar.min.css",
    /\/wp-content\/themes\/hillsthome\/dist\/styles\/main(?:_(?:.*?))?\.css$/,
    "https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,400;0,700;1,400&display=swap",
    "https://fonts.googleapis.com/css2?family=Playfair+Display&display=swap",
  ];

  //showOverlay();

  const doc = new DOMParser().parseFromString(html, "text/html");

  const head = document.getElementsByTagName("head")[0];

  //Blanket replace head
  //TODO use a scalpel instead
  //const newHead = doc.getElementsByTagName("head")[0];
  //head.innerHTML = newHead.innerHTML;

  //Replace body classnames
  const newBody = doc.getElementsByTagName("body")[0];
  document.body.className = newBody.className;

  //Add head assets
  const newHeadAssets = getAssetsFromDOM(doc.getElementsByTagName("head")[0]);
  Array.from(newHeadAssets.styles).forEach((assetEl) =>
    insertStyle(assetEl, head, neverReloadStyles)
  );
  Array.from(newHeadAssets.scripts).forEach((assetEl) =>
    insertScript(assetEl, head, neverReloadScripts)
  );

  //Remove all head assets
  const headAssets = getAssetsFromDOM(head);
  Array.from(headAssets.scripts).forEach((assetEl) =>
    removeAsset(assetEl, neverReloadScripts)
  );
  Array.from(headAssets.styles).forEach((assetEl) =>
    removeAsset(assetEl, neverReloadStyles)
  );

  //Remove all body assets
  const bodyAssets = getAssetsFromDOM(document.body);
  Array.from(bodyAssets.scripts).forEach((assetEl) =>
    removeAsset(assetEl, neverReloadScripts)
  );
  Array.from(bodyAssets.styles).forEach((assetEl) => removeAsset(assetEl));

  //Add new body assets
  const newBodyAssets = getAssetsFromDOM(newBody);
  Array.from(newBodyAssets.scripts).forEach((assetEl) =>
    insertScript(assetEl, document.body, neverReloadScripts)
  );
  Array.from(newBodyAssets.styles).forEach((assetEl) =>
    insertStyle(assetEl, document.body)
  );
}

const assetInList = ({ src, id = null }, array) => {
  return (
    array.findIndex((item) => {
      let isRegexp = false;
      let itemSrc = null;
      let itemId = null;
      if (typeof item === "object") {
        if (item.src) {
          itemSrc = item.src;
        }
        if (item.id) {
          itemId = item.id;
        }
      }

      if (typeof item === "string") {
        itemSrc = item;
      } else if (item instanceof RegExp) {
        itemSrc = item;
        isRegexp = true;
      }

      if (!itemSrc && !itemId) return false;

      if (!isRegexp) {
        if (itemId && id) {
          return id === itemId;
        }
        if (itemSrc) {
          return src.substr(itemSrc * -1) === itemSrc;
        }
      } else {
        if (itemSrc) {
          return !!itemSrc.exec(src);
        }
      }

      return false;
    }) !== -1
  );
};

const shouldSkipAsset = (assetEl, neverReload) => {
  return (
    (assetEl.tagName.toLowerCase() === "script" &&
      assetEl.src &&
      assetInList({ src: assetEl.src, id: assetEl.id }, neverReload)) ||
    assetEl.id === "__bs_script__" ||
    (assetEl.tagName.toLowerCase() === "link" &&
      assetEl.getAttribute("href") &&
      assetInList({ src: assetEl.getAttribute("href") }, neverReload))
  );
};

function removeAsset(assetEl, neverReload = []) {
  if (shouldSkipAsset(assetEl, neverReload)) {
    return;
  }
  assetEl.remove();
}

function insertStyle(assetEl, parent, neverReload = []) {
  if (shouldSkipAsset(assetEl, neverReload)) {
    return;
  }

  // const s = document.createElement(assetEl.tagName.toLowerCase());

  // if (assetEl.type) s.type = assetEl.type;
  // if (assetEl.id) s.id = assetEl.id;

  // if (assetEl.getAttribute("href")) {
  //   s.setAttribute("href", assetEl.getAttribute("href"));
  // } else {
  //   s.innerHTML = assetEl.innerHTML;
  // }
  parent.appendChild(assetEl);
}

function insertScript(assetEl, parent, neverReload = []) {
  if (shouldSkipAsset(assetEl, neverReload)) {
    return;
  }

  const s = document.createElement("script");
  if (assetEl.type) s.type = assetEl.type;
  if (assetEl.id) s.id = assetEl.id;

  if (assetEl.src) {
    s.src = assetEl.src;
  } else {
    s.innerHTML = assetEl.innerHTML;
  }
  parent.appendChild(s);
}
