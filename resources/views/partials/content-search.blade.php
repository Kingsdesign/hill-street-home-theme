<article @php post_class('border-b border-solid border-blue-100 px-3 py-3 bg-blue-50 last:border-none') @endphp>
  <header>
    <h2 class="entry-title text-xl"><a href="{{ get_permalink() }}">{!! get_the_title() !!}</a></h2>
    @if (get_post_type() === 'post')
    @include('partials/entry-meta')
    @endif
  </header>
  <div class="entry-summary">
    @php the_excerpt() @endphp
  </div>
</article>