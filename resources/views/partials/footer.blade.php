<footer class="site-footer">
  <div class="container-fluid">
    <div class="flex -mx-2 flex-wrap">
      <div class="px-4 w-full md:w-1/2 lg:w-1/4 mb-12">
        <div class="px-4">
          <a class="brand" href="{{ home_url('/') }}">
            {{ App::footer_logo() }}
          </a>
        </div>
      </div>
      <div class="px-4 w-full md:w-1/2 lg:w-1/4 mb-12">
        <h3 class="text-2xl mb-6">
          More
        </h3>
        <nav class="nav-footer">
          @if (has_nav_menu('footer_navigation'))
          {!! wp_nav_menu(['theme_location' => 'footer_navigation', 'menu_class' => 'nav']) !!}
          @endif
        </nav>
        <div class="border-b border-solid border-white mx-12 py-8 lg:hidden"></div>
      </div>
      <div class="px-4 w-full md:w-1/2 lg:w-1/4 mb-12">
        <h3 class="text-2xl mb-6">Contact</h3>
        <p><strong>Phone</strong></p>
        <p>West Hobart <a href="tel:0362346849">(03) 6234 6849</a></p>
        <p>Devonport <a href="tel:0362346849">(03) 6127 5355</a></p>
        <div class="mb-6"></div>
        <p><strong>Email</strong></p>
        <p><a href="mailto:hampers@hillstreetgrocer.com">hampers@hillstreetgrocer.com</a></p>
        <div class="border-b border-solid border-white mx-12 py-8 md:hidden"></div>
      </div>
      <div class="px-4 w-full md:w-1/2 lg:w-1/4 mb-12">
        <h3 class="text-2xl mb-6">Legal</h3>
        <nav class="nav-footer">
          @if (has_nav_menu('legal_navigation'))
          {!! wp_nav_menu(['theme_location' => 'legal_navigation', 'menu_class' => 'nav']) !!}
          @endif
        </nav>
        <p data-sc-if="%location%==='devonport'">Hill Street Home (Hill Street North Pty Ltd) – ABN 68 604 544
          818</p>
        <p data-sc-else>Hill Street Home (M & D Nikitaras Pty Ltd) - ABN 71 090 743 196</p>
        <p>All prices in AUD</p>
      </div>
    </div>
  </div>
  <div class="container">
    @php dynamic_sidebar('sidebar-footer') @endphp
  </div>
</footer>
<div class="footer-notice text-sm">
  <div class="container max-w-4xl">
    <div class="py-12">
      <p><strong>Warning:</strong> Under the Liquor Licensing Act 1990 it is an offence: (a) for liquor to be delivered
        to a person under the age
        of 18 years. <strong>Penalty:</strong> fine not exceeding 20 penalty points ($3,140 for 2016-17); (b) for a
        person under the age of 18
        years to purchase liquor. <strong>Penalty:</strong> Fine not exceeding 10 penalty points ($1,570 for 2016-17).
      </p>
    </div>
  </div>
</div>