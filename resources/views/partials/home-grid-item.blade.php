<li class="product-category product last grid-item-custom-link bg-blue-100 {{!$image?'no-image':''}}">

  <a href="{{$link['url']}}">
    @if($image)
    {!!wp_get_attachment_image($image, 'woocommerce_thumbnail')!!}
    @endif
    <h2 class="woocommerce-loop-category__title">
      {!!$title!!}</h2>
  </a></li>