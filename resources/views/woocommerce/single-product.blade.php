{{--
The Template for displaying all single products

This template can be overridden by copying it to yourtheme/woocommerce/single-product.php.

HOWEVER, on occasion WooCommerce will need to update template files and you
(the theme developer) will need to copy the new files to your theme to
maintain compatibility. We try to do this as little as possible, but it does
happen. When this occurs the version of the template file will be bumped and
the readme will list any important changes.

@see 	    https://docs.woocommerce.com/document/template-structure/
@author 		WooThemes
@package 	WooCommerce/Templates
@version     1.6.4
--}}

@extends('layouts.app')

@section('content')
@php
do_action('get_header', 'shop');
do_action('woocommerce_before_main_content');
@endphp

@while(have_posts())
@php
the_post();
do_action('woocommerce_shop_loop');
@endphp
{{-- Product title for mobile
  it's in this template to be consistent with archive-product
  it could just as easily be in content-single-product
   --}}
<div class="product-header bg-blue-50 md:hidden">
  <div class="container  py-4 text-center">
    <h1 class="product_title entry-title text-3xl">{!! the_title() !!}</h1>
  </div>
</div>
{{-- Breadcrumbs --}}
<div class="md:bg-blue-50 text-gray-700 py-4">
  <div class="container-fluid">
    @php woocommerce_breadcrumb() @endphp
  </div>
</div>
@php
wc_get_template_part('content', 'single-product');
@endphp
@endwhile

@php
do_action('woocommerce_after_main_content');
do_action('get_sidebar', 'shop');
do_action('get_footer', 'shop');
@endphp
@endsection