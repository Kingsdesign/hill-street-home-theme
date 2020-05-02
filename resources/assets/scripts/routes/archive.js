import Glide from "@glidejs/glide";
import scData from "../util/sc-data";

export default {
  init() {
    const galleries = document.querySelectorAll(".glide");
    Array.from(galleries).forEach((galleryEl) => {
      const carousel = new Glide(galleryEl, {
        type: "carousel",
      });

      carousel.mount();
    });
  },
  finalize() {
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
};
