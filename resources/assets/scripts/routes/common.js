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

//import modalConfig from "../util/modalConfig";
import ModalService from "../services/modalService";

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

export default {
  init() {
    //let currentScripts = null;

    //MicroModal.init(modalConfig);

    observer.observe();

    var cl = cloudinary.Cloudinary.new({ cloud_name: "hshome" });
    // replace 'demo' with your cloud name in the line above
    cl.responsive();

    barba.init({
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
      var routes = new Router({
        home,
        //aboutUs,
        singleProduct,
        archive,
      });
      routes.loadEvents();
      cl.responsive();
    });
    barba.hooks.afterLeave((data) => {
      window.scrollTo(0, 0);
      // Set <body> classes for "next" page
      var nextHtml = data.next.html;

      replaceAssets(nextHtml);

      var response = nextHtml.replace(
        /(<\/?)body( .+?)?>/gi,
        "$1notbody$2>",
        nextHtml
      );
      var bodyClasses = $(response).filter("notbody").attr("class");
      $("body").attr("class", bodyClasses + " barba-transitioned");
    });
  },

  finalize() {
    //Mobile nav
    let isOpen = false;

    const isOpenSideEffect = () => {
      if (isOpen) {
        document.getElementById("primary-navigation").classList.add("active");
        document.body.classList.add("noscroll");
      } else {
        document
          .getElementById("primary-navigation")
          .classList.remove("active");
        document.body.classList.remove("noscroll");
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
  },
};

function diffAssets(
  currentAssets,
  newAssets,
  matchFn,
  { unchanged = false } = {}
) {
  let removedAssets = currentAssets.filter((x) => notIncludes(x, newAssets));
  let addedAssets = newAssets.filter((x) => notIncludes(x, currentAssets));

  let unchangedAssets = unchanged
    ? newAssets.filter((x) => includes(x, currentAssets))
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

function getAssetsFromDOM(context) {
  const scripts = {
    inline: context.querySelectorAll("script:not([src])"),
    linked: context.querySelectorAll("script[src]"),
  };
  const styles = {
    inline: context.querySelectorAll("style"),
    linked: context.querySelectorAll('link[rel="stylesheet"]'),
  };
  return { scripts, styles };
}

function replaceAssets(html) {
  var tempDOM = document.createElement("div");
  tempDOM.innerHTML = html;

  const d = document;
  const body = d.body;
  const head = d.getElementsByTagName("head")[0];

  const newAssets = getAssetsFromDOM(tempDOM);
  const currentAssets = getAssetsFromDOM(document);

  //----
  // Styles
  const stylesDiff = diffAssets(
    Array.from(currentAssets.styles.linked),
    Array.from(newAssets.styles.linked),
    (a, b) => {
      return a.href === b.href;
    }
  );
  const inlineStylesDiff = diffAssets(
    Array.from(currentAssets.styles.inline),
    Array.from(newAssets.styles.inline),
    (a, b) => {
      return a.id && b.id && a.id === b.id;
    }
  );

  //Add & removed linked styles
  stylesDiff.removed.forEach((asset) => {
    asset.remove();
  });
  stylesDiff.added.forEach((asset) => {
    const s = document.createElement("link");
    s.rel = "stylesheet";
    if (asset.id) s.id = asset.id;

    s.href = asset.href;
    head.appendChild(s);
  });

  //Add & remove inline styles
  ////TODO this is dev only
  inlineStylesDiff.removed.forEach((asset) => {
    asset.remove();
  });
  inlineStylesDiff.added.forEach((asset) => {
    const s = document.createElement("style");
    if (asset.type) s.type = asset.type;
    if (asset.id) s.id = asset.id;

    s.innerHTML = asset.innerHTML;
    head.appendChild(s);
  });

  const alwaysReloadScripts = [
    //"/plugins/woocommerce/assets/js/frontend/single-product.js",
    //"/plugins/woo-product-variation-swatches/assets/js/rtwpvs.js",
    //"/wp-content/plugins/woocommerce/assets/js/frontend/woocommerce.js",
    //"/wp-content/plugins/woocommerce/assets/js/frontend/cart-fragments.js",
    //"/plugins/woocommerce/assets/js/frontend/add-to-cart.js",
    //"/plugins/woocommerce/assets/js/frontend/add-to-cart-variation.js",

    //
    //"/themes/hillsthome/jquery-3.5.0.min.js",
    //"/plugins/woocommerce/assets/js/jquery-blockui/jquery.blockUI.js",
    /*"/plugins/woocommerce/assets/js/frontend/add-to-cart.js",
    "/plugins/woocommerce/assets/js/frontend/single-product.js",
    //"/plugins/woocommerce/assets/js/js-cookie/js.cookie.js",
    "/plugins/woocommerce/assets/js/frontend/woocommerce.js",
    "/plugins/woocommerce/assets/js/frontend/cart-fragments.js",
    //"/plugins/woocommerce/assets/js/jquery-tiptip/jquery.tipTip.min.js",
    "/plugins/duracelltomi-google-tag-manager/js/gtm4wp-form-move-tracker.js",
    "/plugins/duracelltomi-google-tag-manager/js/gtm4wp-woocommerce-enhanced.js",
    //"/wp-includes/js/underscore.min.js",
    //"/wp-includes/js/wp-util.js",
    "/wp-content/plugins/woo-product-variation-swatches/assets/js/rtwpvs.js",
    //"/wp-content/themes/hillsthome/dist/scripts/main.js",
    //"/wp-content/themes/hillsthome/dist/scripts/store-chooser.js",
    //"/wp-content/plugins/woocommerce/assets/js/accounting/accounting.js",
    "/wp-content/plugins/woocommerce-product-addons/assets/js/addons.js",*/

    //"/wp-content/t/themes/hillsthome/jquery-3.5.0.min.js",
    "/wp-content/plugins/woocommerce/assets/js/frontend/add-to-cart.js",
    //"/wp-content/plugins/woocommerce/assets/js/jquery-blockui/jquery.blockUI.js",
    "/wp-content/plugins/woocommerce/assets/js/frontend/single-product.js",
    //"/wp-content/plugins/woocommerce/assets/js/js-cookie/js.cookie.js",
    "/wp-content/plugins/woocommerce/assets/js/frontend/woocommerce.js",
    "/wp-content/plugins/woocommerce/assets/js/frontend/cart-fragments.js",
    "/wp-content/plugins/woocommerce/assets/js/jquery-tiptip/jquery.tipTip.min.js",
    "/wp-content/plugins/duracelltomi-google-tag-manager/js/gtm4wp-form-move-tracker.js",
    "/wp-content/plugins/duracelltomi-google-tag-manager/js/gtm4wp-woocommerce-enhanced.js",
    //"/wp-includes/js/underscore.min.js",
    //"/wp-includes/js/wp-util.js",
    "/wp-content/plugins/woo-product-variation-swatches/assets/js/rtwpvs.js",
    //"/wp-content/themes/hillsthome/dist/scripts/main.js",
    //"/wp-content/themes/hillsthome/dist/scripts/store-chooser.js",
    "/wp-content/plugins/woocommerce/assets/js/frontend/add-to-cart-variation.js",
    "/wp-content/plugins/woocommerce/assets/js/accounting/accounting.js",
    "/wp-content/plugins/woocommerce-product-addons/assets/js/addons.js",
  ];

  const assetInList = (src, array) => {
    return (
      array.findIndex((item) => src.substr(item.length * -1) === item) !== -1
    );
  };

  //-----
  // Scripts
  const scriptsDiff = diffAssets(
    Array.from(currentAssets.scripts.linked),
    Array.from(newAssets.scripts.linked),
    (a, b) => {
      return a.src === b.src;
    },
    { unchanged: true }
  );
  /*const inlineScriptsDiff = diffAssets(
    Array.from(currentAssets.scripts.inline),
    Array.from(newAssets.scripts.inline),
    (a, b) => {
      return a.id && b.id && a.id === b.id;
    },
    { unchanged: true }
  );*/

  //Add & remove inline scripts
  //This seems to break some localisation stuff
  /*inlineScriptsDiff.removed.forEach((asset) => {
    asset.remove();
  });*/
  /*inlineScriptsDiff.added.forEach((asset) => {
    const s = document.createElement("script");
    if (asset.type) s.type = asset.type;
    if (asset.id) s.id = asset.id;

    s.innerHTML = asset.innerHTML;
    body.appendChild(s);
  });*/

  //Remove and add ALL inline scripts
  Array.from(currentAssets.scripts.inline).forEach((asset) => {
    if (asset.id === "__bs_script__") return;
    asset.remove();
  });
  Array.from(newAssets.scripts.inline).forEach((asset) => {
    if (asset.id === "__bs_script__") return;
    const s = document.createElement("script");
    if (asset.type) s.type = asset.type;
    if (asset.id) s.id = asset.id;

    s.innerHTML = asset.innerHTML;
    body.appendChild(s);
  });

  //Add & removed linked scripts
  scriptsDiff.removed.forEach((asset) => {
    asset.remove();
  });

  //Reload always reload scripts
  scriptsDiff.unchanged.forEach((asset) => {
    if (asset.src && assetInList(asset.src, alwaysReloadScripts)) {
      const s = document.createElement("script");
      if (asset.type) s.type = asset.type;
      if (asset.id) s.id = asset.id;

      s.src = asset.src;

      asset.remove();

      body.appendChild(s);
    }
  });
  scriptsDiff.added.forEach((asset) => {
    const s = document.createElement("script");
    if (asset.type) s.type = asset.type;
    if (asset.id) s.id = asset.id;

    s.src = asset.src;
    body.appendChild(s);
  });
}
