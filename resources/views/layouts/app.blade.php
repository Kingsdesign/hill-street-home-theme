<!doctype html>
<html {!! get_language_attributes() !!}>
@include('partials.head')

<body @php body_class() @endphp data-barba="wrapper">
  @php do_action('get_header') @endphp
  @include('partials.header')
  <div class="wrap" role="document">
    <div class="content">
      <main class="main" data-barba="container" data-barba-namespace="{{ App::namespace() }}">
        @yield('content')
      </main>
    </div>
  </div>
  @php do_action('get_footer') @endphp
  @include('partials.footer')
  @php wp_footer() @endphp
  @include('partials.modals')
</body>

</html>