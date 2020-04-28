@extends('layouts.app')

@section('content')
@include('partials.page-header')

<div class="container py-12 max-w-2xl">
  @if (!have_posts())
  <div class="alert alert-warning">
    {{ __('Sorry, no results were found.', 'sage') }}
  </div>
  {!! get_search_form(false) !!}
  @endif

  @while(have_posts()) @php the_post() @endphp
  @include('partials.content-search')
  @endwhile

</div>

@endsection