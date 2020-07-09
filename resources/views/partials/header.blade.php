<div class="header_top-bar" id="top-bar">
  <div class="container-fluid">
    <div class="flex -mx-2 justify-between items-center">
      <div class="px-2">
        <a class="block initial-none" href="https://hillstreetgrocer.com/">@svg(MdChevronLeft) <span
            class="hidden md:inline-block">Back</span> to Hill Street <span
            class="hidden md:inline-block">Grocer</span></a>
      </div>
      <div class="px-2 flex">
        <div class="current-store mr-2">
          <div class="current-store_display px-2 md:border-t-4 md:border-solid border-b-4 md:pb-1 pt-1 md:mt-0">
            <span data-sc-tpl="header_string" class="hidden md:block"></span>
            <span data-sc-val="method_display" class="md:hidden"></span>
          </div>
        </div>
        <div class="change-store">
          <button class="btn btn-primary btn-outline change-store_button border-none md:border-solid"
            data-sc-show="modal">Change</button>
        </div>
      </div>
    </div>
  </div>
</div>
<header class="site-header">
  <div class="container-fluid">
    <!-- Main Header (brand/nav toggle) -->
    <div class="flex -mx-2 justify-between items-center md:justify-center pt-2 pb-4">
      <div class="px-2">
        <div class="site-header_logo pr-12 md:pr-0">
          <a class="brand block" href="{{ home_url('/') }}">
            {{ App::header_logo() }}
          </a>
        </div>
      </div>
      <div class="px-2 md:hidden">
        <div>
          <button class="primary-nav-toggle flex items-center p-2 md:w-auto" data-mobile-nav="toggle"><span
              class="sr-only">Menu</span>
            @svg(MdMenu)</button>
        </div>
      </div>
    </div>
    <!-- Navbar -->
    <nav class="nav-primary" id="primary-navigation">
      <div class="md:hidden absolute right-0 top-0">
        <button class="primary-nav-close text-3xl p-2 flex items-center" data-mobile-nav="toggle"><span
            class="sr-only">Close</span>@svg(MdClose)</button>
      </div>
      <div class="nav-primary-inner max-w-xs md:max-w-full px-4 md:px-0 pt-12 md:pt-0 mx-auto w-full md:flex">

        <div class="px-2 w-full md:w-1/4 lg:w-1/5 text-center md:text-left">
          <button data-modal-trigger="modal-search" class="link" data-mobile-nav="close">Search
            @svg(MdSearch)</button>
        </div>
        <div class="px-2 w-dull md:w-1/2 lg:w-3/5 text-center">
          @if (has_nav_menu('primary_navigation'))
          {!! wp_nav_menu(['theme_location' => 'primary_navigation', 'menu_class' => 'nav']) !!}
          @endif
        </div>
        <div class="px-2 w-full md:w-1/4 lg:w-1/5 text-center md:text-right">
          <a href="{{ App::relative_url(wc_get_cart_url()) }}">
            Cart
            @svg(MdShoppingCartOutline)
            <span id="header-cart-indicator"></span>
          </a>
        </div>
      </div>
    </nav>
  </div>
</header>