<div class="glide carousel">
  <div class="glide__track" data-glide-el="track">
    <div class="glide__slides">
      @foreach($slides as $slide)
      <div class="glide__slide carousel-slide">
        <div class="ar-holder portrait xl:landscape">
          @php echo wp_get_attachment_image($slide, 'carousel_slide'); @endphp
        </div>
      </div>
      @endforeach
    </div>
  </div>

  @if ($arrows)
  <div class="carousel-arrows_container">
    <div class="glide__arrows carousel-arrows" data-glide-el="controls">
      <button class="glide__arrow glide__arrow--left carousel-arrow carousel-arrow_left"
        data-glide-dir="<">@svg(MdChevronLeft)
        Prev</button>
      <button class="glide__arrow glide__arrow--right carousel-arrow carousel-arrow_right" data-glide-dir=">">Next
        @svg(MdChevronRight)</button>
    </div>
  </div>
  @endif
</div>