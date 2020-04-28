import Glide from "@glidejs/glide";

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
  },
};
