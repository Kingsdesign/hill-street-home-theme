import Glide from "@glidejs/glide";
import scData from "../util/sc-data";

const galleries = [];

export default {
  init() {},
  finalize() {
    const galleryElements = document.querySelectorAll(".glide");
    Array.from(galleryElements).forEach((galleryEl) => {
      const carousel = new Glide(galleryEl, {
        type: "carousel",
      });

      carousel.mount();

      galleries.push(carousel);
    });
    // JavaScript to be fired on the home page, after the init JS

    /**
     * trigger submit on category sort change
     */
    Array.from(document.querySelectorAll("form.woocommerce-ordering")).forEach(
      (form) => {
        Array.from(form.querySelectorAll("select.orderby")).forEach(
          (select) => {
            select.addEventListener("change", (e) => {
              form.submit();
            });
          }
        );
      }
    );

    //Hide some categories
    if (scData("location") === "devonport") {
      Array.from(
        document.querySelectorAll(
          ".product-category.product-category--wine-and-spirits"
        )
      ).forEach((el) => {
        el.remove();
      });
    }
  },
  leave() {
    for (let i = 0; i < galleries.length; i++) {
      galleries[i].destroy();
      galleries.splice(i, 1);
    }
  },
};
