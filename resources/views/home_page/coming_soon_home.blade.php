@include('layouts.app')
@include('layouts.header')

{{--
    The home screen for a service that exists but has nothing behind it yet.

    A section must carry a service type, and each type has its own home screen.
    The Finance services of Document 1 page 9 - Tontine, Loan, Investment - and
    the AI Assistant under Others belong to Document 2, which is not built in
    any panel. They are listed so the grouped section list matches page 9.

    Without this screen a customer choosing one of them would be sent back to
    "set location", where they would choose it again and be sent back again -
    a loop with no way out. An honest message is better than a shop that has
    nothing to do with what they tapped.
--}}

<div class="d-none">
    <div class="bg-primary p-3 d-flex align-items-center">
        <a class="toggle togglew toggle-2" href="#"><span></span></a>
        <h4 class="font-weight-bold m-0 text-white" id="coming_soon_title_mobile"></h4>
    </div>
</div>

<div class="siddhi-popular">
    <div class="container">
        <div class="py-5">
            <div class="row justify-content-center">
                <div class="col-md-7">
                    <div class="bg-white rounded shadow-sm text-center p-5">
                        <h1 class="display-4 mb-4">&#128640;</h1>

                        <h2 class="font-weight-bold mb-2" id="coming_soon_title"></h2>
                        <p class="text-muted mb-4">{{ trans('lang.service_coming_soon_text') }}</p>

                        {{-- The way out. Without this the customer is stuck on a
                             screen with nothing on it. --}}
                        <a href="#" data-toggle="modal" data-target="#select_store_model"
                           class="btn btn-primary btn-lg">
                            {{ trans('lang.service_coming_soon_choose') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@include('layouts.footer')
@include('layouts.nav')

<script type="text/javascript">
    /* The service's own name, so the screen says "Tontine" rather than
     * something generic. It is the name the customer just tapped, held in the
     * same cookie every other screen reads. */
    var comingSoonName = "<?php echo addslashes($_COOKIE['section_name'] ?? ''); ?>";

    $(document).ready(function () {
        var heading = comingSoonName !== ''
            ? comingSoonName
            : "{{ trans('lang.service_coming_soon_title') }}";

        $('#coming_soon_title').text(heading);
        $('#coming_soon_title_mobile').text(heading);
    });
</script>
