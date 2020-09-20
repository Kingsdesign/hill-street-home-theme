import scData from "../util/sc-data";
//import "jquery";
//import { trigger } from "../util/dom-help";

function SingleProduct() {
  const init = () => {
    //console.log("singleProduct init");
  };

  const leave = () => {
    //console.log("singleProduct leave");
  };

  const finalize = () => {
    //console.log("singleProduct finalize");

    //Maybe hide addons
    const isAddonsHidden =
      scData("location") === "devonport" &&
      window.main_data &&
      window.main_data.single_product &&
      window.main_data.single_product.hide_addons;

    Array.from(document.querySelectorAll(".addons-wrapper")).forEach((el) => {
      if (isAddonsHidden) {
        el.remove();
      } else {
        el.classList.remove("hidden");
      }
    });

    //Maybe show undeliverable
    fetch(window.main_data.ajax_url, {
      method: "POST",
      credentials: "include", // include, *same-origin, omit
      headers: {
        "Content-Type": "application/x-www-form-urlencoded; charset=utf-8",
      },
      body:
        "action=product_deliverable&product=" +
        window.main_data.single_product.product_id,
    })
      .then((r) => {
        if (r.ok) return r;
        throw new Error(r.statusText);
      })
      .then((r) => r.json())
      .then((resp) => {
        const isUndeliverable = resp === false;
        Array.from(document.querySelectorAll(".delivery-no-fresh")).forEach(
          (el) => {
            if (isUndeliverable) {
              el.classList.remove("hidden");
            } else {
              el.remove();
            }
          }
        );

        if (isUndeliverable) {
          Array.from(
            document.querySelectorAll(".single_add_to_cart_button")
          ).forEach((el) => {
            el.remove();
          });
        }
      });
  };

  return {
    init,
    finalize,
    leave,
  };
}

export default SingleProduct();
