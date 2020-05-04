import "jquery";
import Glide from "@glidejs/glide";

//Init gallery
const galleries = document.querySelectorAll(
  ".woocommerce-product-gallery.woocommerce-product-gallery--with-images"
);
Array.from(galleries).forEach(initGallery);

function initGallery(galleryEl) {
  console.log("init gallery");
  galleryEl.classList.add("glide");
  const glideTrack = document.createElement("div");
  glideTrack.classList.add("glide__track");
  glideTrack.dataset.glideEl = "track";

  const glideSlides = galleryEl.getElementsByClassName(
    "woocommerce-product-gallery__wrapper"
  )[0];
  glideSlides.classList.add("glide__slides");

  //Add glide__slide to images
  Array.from(
    glideSlides.getElementsByClassName("woocommerce-product-gallery__slide")
  ).forEach((slide) => {
    slide.classList.add("glide__slide");
  });

  // Wrap slides in track and insert in galleryEl
  galleryEl.appendChild(glideTrack);
  glideTrack.appendChild(glideSlides);

  const carousel = new Glide(galleryEl, {
    type: "carousel",
  });

  // Automated height on Carousel build
  /*carousel.on("build.after", function () {
        glideHandleHeight();
      });

      // Automated height on Carousel change
      carousel.on("run.after", function () {
        glideHandleHeight();
      });*/

  //Load full size for thumbnails
  //We're assuming no jumps and only one image visible at once
  carousel.on("run", (move) => {
    const activeSlide = carousel.selector.querySelectorAll(
      ".glide__slide.glide__slide--active"
    )[0];
    const nextSlide =
      move.direction === "<"
        ? activeSlide.previousElementSibling
        : activeSlide.nextElementSibling;
    const nextImage = nextSlide.getElementsByTagName("img")[0];
    if (nextImage.classList.contains("loaded")) return;
    nextImage.classList.add("loaded");

    //console.log(nextImage.dataset.large_image);
    nextImage.src = nextImage.dataset.large_image;
    nextImage.removeAttribute("srcset");
    //TODO maybe get wc to give full size srcset?
  });

  carousel.mount();

  /// Integrate with poocommerce

  const onFoundVariation = function (e, variation) {
    //Loop through all slides, find matching image src
    const slides = document.querySelectorAll(
      ".woocommerce-product-gallery .glide__slide:not(.glide__slide--clone) .woocommerce-product-gallery__image"
    );
    const slideIndex = Array.from(slides).findIndex(
      (slide) => slide.dataset.thumb === variation.image.gallery_thumbnail_src
    );
    if (slideIndex === -1) return;
    carousel.go(`=${slideIndex}`);
  };

  Array.from(document.querySelectorAll("form.variations_form")).forEach(
    (form) => {
      jQuery(form).on("found_variation", onFoundVariation);
    }
  );

  // Resize height
  function glideHandleHeight() {
    const activeSlide = document.querySelector(".glide__slide--active");
    const activeSlideHeight = activeSlide ? activeSlide.offsetHeight : 0;

    const glideTrack = document.querySelector(".glide__track");
    const glideTrackHeight = glideTrack ? glideTrack.offsetHeight : 0;

    if (activeSlideHeight !== glideTrackHeight) {
      glideTrack.style.height = `${activeSlideHeight}px`;
    }
  }
}
