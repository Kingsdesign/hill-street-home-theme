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

function diffAssets(currentAssets, newAssets, matchFn) {
  let removedAssets = currentAssets.filter((x) => notIncludes(x, newAssets));
  let addedAssets = newAssets.filter((x) => notIncludes(x, currentAssets));

  function notIncludes(asset, arr) {
    let includes = false;
    for (let i = 0; i < arr.length && !includes; i++) {
      if (matchFn(asset, arr[i])) includes = true;
    }
    return !includes;
  }

  return { added: addedAssets, removed: removedAssets };
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
  /*stylesDiff.removed.forEach((asset) => {
    asset.remove();
  });*/
  stylesDiff.added.forEach((asset) => {
    const s = document.createElement("link");
    s.rel = "stylesheet";
    if (asset.id) s.id = asset.id;

    s.href = asset.href;
    head.appendChild(s);
  });

  //Add & remove inline styles
  //TODO this is dev only
  /*inlineStylesDiff.removed.forEach((asset) => {
    asset.remove();
  });*/
  inlineStylesDiff.added.forEach((asset) => {
    const s = document.createElement("style");
    if (asset.type) s.type = asset.type;
    if (asset.id) s.id = asset.id;

    s.innerHTML = asset.innerHTML;
    head.appendChild(s);
  });

  //-----
  // Scripts
  const scriptsDiff = diffAssets(
    Array.from(currentAssets.scripts.linked),
    Array.from(newAssets.scripts.linked),
    (a, b) => {
      return a.src === b.src;
    }
  );
  const inlineScriptsDiff = diffAssets(
    Array.from(currentAssets.scripts.inline),
    Array.from(newAssets.scripts.inline),
    (a, b) => {
      return a.id && b.id && a.id === b.id;
    }
  );

  //Add & removed linked scripts
  scriptsDiff.removed.forEach((asset) => {
    asset.remove();
  });
  scriptsDiff.added.forEach((asset) => {
    const s = document.createElement("script");
    if (asset.type) s.type = asset.type;
    if (asset.id) s.id = asset.id;

    s.src = asset.src;
    body.appendChild(s);
  });

  //Add & remove inline scripts
  inlineScriptsDiff.removed.forEach((asset) => {
    asset.remove();
  });
  inlineScriptsDiff.added.forEach((asset) => {
    const s = document.createElement("script");
    if (asset.type) s.type = asset.type;
    if (asset.id) s.id = asset.id;

    s.innerHTML = asset.innerHTML;
    body.appendChild(s);
  });
}

/*function replaceAssets(html) {
  //Scripts & styles
  var tempDOM = document.createElement("div");
  tempDOM.innerHTML = html;

  var newScripts = tempDOM.getElementsByTagName("script");
  var newStyles = tempDOM.querySelectorAll('link[rel="stylesheet"]');
  const newInlineStyles = tempDOM.getElementsByTagName("style");

  const currentScripts = document.getElementsByTagName("script[src]");
  const currentInlineScripts = document.getElementsByTagName("script:not([src]");
  const currentStyles = document.querySelectorAll('link[rel="stylesheet"]');
  const currentInlineStyles = document.getElementsByTagName("style");

  const excludeScripts = [
    "browser-sync/browser-sync-client.js?v=2.24.7",
    "dist/scripts/main.js",
    "jquery-3.5.0.min.js",
  ];
  const excludeStyles = ["dist/styles/main.css"];

  const shouldExcludeAsset = (src, exclusionArray) => {
    let exclude = false;

    exclusionArray.forEach((name) => {
      if (src.substr(name.length * -1, name.length) === name) {
        exclude = true;
        return false;
      }
    });
    return exclude;
  };

  const shouldExcludeScript = (src) => {
    return shouldExcludeAsset(src, excludeScripts);
  };
  const shouldExcludeStyle = (src) => {
    return shouldExcludeAsset(src, excludeStyles);
  };

  //Remove current scripts unless excluded
  //Will remove all inline
  const linkedScripts =[], inlineScripts = [];
  Array.from(currentScripts)
  const scriptsDiff = diffAssets(Array.from(currentScripts), Array.from(newScripts), (a, b) => {
      return a.src && b.src && a.src === b.src;
    })
    scriptsDiff.forEach()
  Array.from(currentScripts).forEach((script) => {
    if (script.src && shouldExcludeScript(script.src)) return;
    if (script.id === "__bs_script__") return;
    script.remove();
  });
  //Remove current styles unless excluded
  Array.from(currentStyles).forEach((style) => {
    if (style.href && shouldExcludeStyle(style.href)) return;
    style.remove();
  });
  Array.from(currentInlineStyles).forEach((style) => {
    style.remove();
  });

  Array.from(newScripts).forEach((script) => {
    if (script.src && shouldExcludeScript(script.src)) return;
    if (script.id === "__bs_script__") return;
    //if (script.src) console.log("script added: ", script.src);
    //script.remove();
    const s = document.createElement("script");
    if (script.type) s.type = script.type;
    if (script.id) s.id = script.id;

    if (script.src) {
      s.src = script.src;
      document.body.appendChild(s);
    } else {
      s.innerHTML = script.innerHTML;
      //document.getElementsByTagName("head")[0].appendChild(s);
      document.body.appendChild(s);
    }
  });
  Array.from(newStyles).forEach((style) => {
    if (style.href && shouldExcludeStyle(style.href)) return;
    if (!style.href) return;

    const s = document.createElement("link");
    s.rel = "stylesheet";
    s.href = style.href;
    document.getElementsByTagName("head")[0].appendChild(s);
  });
  Array.from(newInlineStyles).forEach((style) => {
    const s = document.createElement("style");
    s.innerHTML = style.innerHTML;
    document.getElementsByTagName("head")[0].appendChild(s);
  });

  tempDOM.innerHTML = ""; //probably not necessary
}*/
