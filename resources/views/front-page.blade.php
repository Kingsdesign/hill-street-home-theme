@extends('layouts.app')

@section('content')
@while(have_posts()) @php the_post() @endphp
@if($campaign)
<div class="campaign-hero">
  <div class="campaign-hero_wrap">
    <div class="campaign-hero_inner-wrap relative">
      @if($campaign['banner'])
      <div class="ar-holder portrait lg:landscape">
        @php echo wp_get_attachment_image( $campaign['banner'], 'full' ) @endphp
      </div>
      @endif
      @if($campaign['heading'])
      <div class="campaign-hero_heading-wrap absolute bottom-0 left-0 w-full">
        <div class="campaign-hero_heading text-white p-4 text-2xl md:text-4xl block md:inline-block pr-20">
          {{$campaign['heading']}}
        </div>
      </div>
      @endif
    </div>
    @if($campaign['content'])
    <div class="campaign-hero_content bg-gray-800 py-8 lg:py-10 text-white">
      <div class="container">
        {!!do_shortcode($campaign['content'])!!}
      </div>
    </div>
    @endif
  </div>
</div>
@endif
<div class="container">

  <div class="product-grid py-12 md:py-20">
    {{-- @if($categories) --}}
    {{--@php echo do_shortcode( '[product_categories columns=3 ids="'.implode(",",$categories).'"]' ) @endphp --}}
    {{-- {!! FrontPage::product_categories($categories, ['columns'=>3])!!} --}}
    {{-- @endif --}}

    {{-- @foreach($grid as $gridItem) --}}

    {{-- @endforeach --}}

    {!! FrontPage::grid()!!}

  </div>
</div>

<div class="bg-gray-100">
  <div class="container py-12 text-center">
    @php the_content() @endphp
  </div>
</div>

<div class="py-12">
  @include('partials.instagram')
  {{--@php echo do_shortcode( '[simple_instagram_feed ig="hillstreethome"]' ) @endphp--}}
</div>

<div class="md:grid contact-block grid-cols-2 grid-rows-2">
  <div class="bg-gray-100 flex items-center justify-center py-6 px-4 col-start-1">
    <div>
      <h3 class="text-2xl mb-4">West Hobart</h3>
      <p class="mb-3"><strong>Address</strong><br>70 Arthur St, West Hobart TAS 7000</p>
      <p class="mb-3"><strong>Phone</strong><br><a href="tel:62346849" class="initial-none">(03) 6234 6849</a></p>
    </div>
  </div>
  <div class="bg-gray-200 flex items-center justify-center py-6 px-4 col-start-1">
    <div>
      <h3 class="text-2xl mb-4">Devonport</h3>
      <p class="mb-3"><strong>Address</strong><br>42/54 Oldaker St, Devonport TAS 7310</p>
      <p class="mb-3"><strong>Phone</strong><br><a href="tel:0361275355" class="initial-none">(03) 6127 5355</a></p>
    </div>
  </div>
  <div class="bg-gray-100 flex items-center justify-center py-6 px-4 col-start-1">
    <div>
      <h3 class="text-2xl mb-4">Sandy Bay</h3>
      <p class="mb-3"><strong>Address</strong><br>2 Churchill Ave, Sandy Bay TAS 7005</p>
      <p class="mb-3"><strong>Phone</strong><br><a href="tel:0362404881" class="initial-none">(03) 6240 4881</a></p>
    </div>
  </div>
  <div class="row-start-1 col-start-2 row-span-3 bg-cover bg-center h-64 md:h-auto"
    style="background-image: url(@php echo wp_get_attachment_image_src( 54, 'large')[0] @endphp)">
  </div>
</div>
@endwhile
@endsection