<div id="modal-search" aria-hidden="true" class="modal">

  <!-- [2] -->
  <div tabindex="-1" class="modal-container">

    <button aria-label="Close modal" data-modal-close="modal-search"
      class="top-0 right-0 absolute md:mr-4 md:mt-4 text-3xl md:text-4xl p-3 flex items-center"><span
        class="sr-only">Close</span>@svg(MdClose)</button>

    <!-- [3] -->
    <div role="dialog" aria-modal="true" aria-labelledby="modal-search--title" class="modal-content">


      <header>
        <h2 id="modal-search--title" class="text-lg mb-3">
          Search
        </h2>
      </header>

      <div id="modal-search--content">
        {!! get_search_form(false) !!}
      </div>

    </div>
  </div>
</div>

<div id="modal-store-chooser" aria-hidden="true" class="modal">

  <!-- [2] -->
  <div tabindex="-1" class="modal-container">

    <div class="modal-backdrop" data-modal-close="modal-store-chooser"></div>

    <!-- [3] -->
    <div role="dialog" aria-modal="true" aria-labelledby="modal-store-chooser--title" class="modal-content">

      <button aria-label="Close modal" data-modal-close="modal-store-chooser"
        class="top-0 right-0 absolute text-2xl md:text-4xl p-3 flex items-center"><span
          class="sr-only">Close</span>@svg(MdClose)</button>


      <header class="mx-auto max-w-lg px-8 pt-8 pb-4">
        <h2 id="modal-store-chooser--title" class="text-center text-2xl">
          Store Chooser
        </h2>
      </header>

      <div id="modal-store-chooser--content">
        <div class="mx-auto max-w-lg px-4 md:px-8 pb-8">
          <p class="mb-3 text-sm md:text-base">Products vary from store to store. Please select your delivery method and
            suburb.</p>
          {{--<div class="mt-3 md:mt-6 mb-6">
            <span class="label italic block mb-3">Delivery method</span>
            <div class="sm:grid grid-cols-2 gap-4">
              <button class="btn btn-dark btn-large w-full mb-3 block flex items-center justify-center sc-method-button"
                data-sc-method="delivery" id="sc-delivery">Delivery</button>
              <button class="btn btn-dark btn-large w-full mb-3 block flex items-center justify-center sc-method-button"
                data-sc-method="pickup" id="sc-pickup">Click
                &amp;
                Collect</button>
            </div>
          </div>--}}
          <div class="flex items-center -mx-2 flex-wrap">
            <span class="label block text-lg font-bold px-2 min-w-full md:min-w-0 text-center">Delivery to</span>
            <div class="flex-grow px-2 min-w-full md:min-w-0 my-3">
              <div class="relative">
                <input id="autoComplete" tabindex="1" class="border border-solid border-gray-800 w-full px-3 py-3">
              </div>
            </div>
            <div class="px-2 min-w-full md:min-w-0">
              <button class="btn btn-primary block py-3 mx-auto" type="button" disabled="disabled"
                data-sc-action="save-postcode">Go</button>
            </div>
          </div>

          <div class="my-6">
            <span class="block text-center text-lg font-bold">or Click &amp; Collect at</span>
          </div>

          <div class="flex items-center justify-center -mx-2 -my-2 flex-wrap">
            @php
            $locations = get_terms( array(
            'taxonomy' => 'location',
            'hide_empty' => false,
            ) ); @endphp

            @foreach($locations as $location)
            <div class="px-2 py-2">
              <button class="btn btn-primary btn-large block btn-outline" type="button"
                data-sc-location="{{$location->slug}}">{{$location->name}}</button>
            </div>
            @endforeach
          </div>

          {{--<div>
            <span class="mb-6 md:mt-12 block text-center md:text-lg font-bold bg-blue-50 py-3" data-sc-if="%location%"
              data-sc-tpl="header_string"></span>
          </div>
          <div>
            <div class="flex justify-center">
              <button class="btn btn-primary btn-large w-full max-w-xs block" disabled="disabled"
                id="sc-save">Save</button>
            </div>
          </div>--}}
        </div>
      </div>

    </div>
  </div>
</div>