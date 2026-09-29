@include('layouts.app')
@include('layouts.header')
{{-- The mobile bar every other account screen carries. --}}
<div class="d-none">
    <div class="bg-primary p-3 d-flex align-items-center">
        <a class="toggle togglew toggle-2" href="#"><span></span></a>
        <h4 class="font-weight-bold m-0 text-white">{{ trans('lang.business_account_title') }}</h4>
    </div>
</div>
<div class="siddhi-popular">
    {{-- Same wrapper and spacing as Subscriptions and All Stores, so this page
         sits below the header the way they do rather than against it. --}}
    <div class="container">
        <div class="py-5">

            <h2 class="font-weight-bold mb-1">{{ trans('lang.business_account_title') }}</h2>
            <p class="text-muted mb-4">{{ trans('lang.business_account_intro') }}</p>

            {{-- Where the application has got to. Hidden until the customer's
                 record has been read, so nothing is asserted before it is
                 known. --}}
            <div id="business_state" class="mb-4" style="display:none;">
                <div class="p-4 rounded shadow-sm bg-white">
                    <div class="d-flex align-items-center mb-2">
                        <i id="business_state_icon" class="fa fa-clock-o h4 m-0 mr-2"></i>
                        <h5 class="font-weight-bold m-0" id="business_state_title"></h5>
                    </div>
                    <p class="text-muted mb-3" id="business_state_text"></p>

                    {{-- Shown only on a refusal. A customer refused with no
                         visible explanation simply applies again. --}}
                    <div id="business_reason" class="alert alert-warning" style="display:none;">
                        <strong>{{ trans('lang.business_account_reason_label') }}</strong>
                        <span id="business_reason_text"></span>
                    </div>

                    <div class="row" id="business_submitted_details" style="display:none;">
                        <div class="col-md-4 mb-2">
                            <span class="text-muted small d-block">{{ trans('lang.business_account_company') }}</span>
                            <span class="font-weight-bold" id="business_view_company"></span>
                        </div>
                        <div class="col-md-4 mb-2">
                            <span class="text-muted small d-block">{{ trans('lang.business_account_registration') }}</span>
                            <span class="font-weight-bold" id="business_view_registration"></span>
                        </div>
                        <div class="col-md-4 mb-2">
                            <span class="text-muted small d-block">{{ trans('lang.business_account_document') }}</span>
                            <span id="business_view_document">-</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- The application itself. Shown when there is no application, and
                 again after a refusal so the customer can correct and resend.
                 Never while one is pending or approved. --}}
            <div id="business_form_wrap" style="display:none;">
                <div class="p-4 rounded shadow-sm bg-white">
                    <h5 class="font-weight-bold mb-1" id="business_form_title"></h5>
                    <p class="text-muted small mb-4">{{ trans('lang.business_account_form_intro') }}</p>

                    <div id="business_error" class="alert alert-danger" style="display:none;"></div>

                    <div class="form-group">
                        <label>{{ trans('lang.business_account_company') }}</label>
                        <input type="text" class="form-control" id="business_company" maxlength="120">
                    </div>

                    <div class="form-group">
                        <label>{{ trans('lang.business_account_registration') }}</label>
                        <input type="text" class="form-control" id="business_registration" maxlength="60">
                    </div>

                    <div class="form-group">
                        <label>{{ trans('lang.business_account_document') }}</label>
                        <div class="clearfix"></div>
                        <input type="file" id="business_document" accept="image/*,application/pdf">
                        <small class="form-text text-muted">{{ trans('lang.business_account_document_hint') }}</small>
                        <div id="business_document_state" class="small mt-2"></div>
                    </div>

                    <button type="button" class="btn btn-primary btn-block" id="business_submit">
                        {{ trans('lang.business_account_submit') }}
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>
@include('layouts.footer')
@include('layouts.nav')
<script type="text/javascript">
    /* The customer half of business accounts.
     *
     * Field shapes are fixed by the mobile app, which has been writing these
     * records since 24 September, and by the admin approval screen that reads
     * them. They are restated in APP-SPEC-ADMIN.md section 18. Nothing here
     * invents a field.
     *
     * users/{uid}
     *     accountType: "business"
     *     businessProfile: { companyName, registrationNumber, documentUrl,
     *                        submittedAt, status }
     *
     * submittedAt is an ISO STRING, not a Firestore Timestamp. That is what the
     * app writes and what the admin list sorts on, so this writes the same.
     *
     * THIS SCREEN NEVER DECIDES. It writes status "pending" and never
     * "approved" or "rejected", and it never writes reviewedAt or reviewedBy -
     * those belong to the admin panel alone.
     */

    var businessUserId = user_uuid;
    var businessDatabase = firebase.firestore();
    var businessRef = businessDatabase.collection('users').doc(businessUserId);

    /* The uploaded document's URL, held until the customer submits. An upload
     * that is never submitted leaves a file in Storage and no record, which is
     * the harmless direction to fail in. */
    var businessDocumentUrl = '';
    var businessUploading = false;

    var BUSINESS_MAX_BYTES = 5 * 1024 * 1024;

    $(document).ready(async function () {
        jQuery("#overlay").show();

        try {
            await loadBusinessProfile();
        } catch (e) {
            console.error('business account could not be read', e);
            showBusinessForm(null);
        }

        jQuery("#overlay").hide();
    });

    async function loadBusinessProfile() {
        var snapshot = await businessRef.get();
        var user = snapshot.exists ? snapshot.data() : null;
        var profile = (user && user.businessProfile) ? user.businessProfile : null;

        /* An application with no status reads as pending: the app wrote the
         * request, so something is awaiting a decision either way. */
        var status = profile ? (profile.status || 'pending') : '';

        if (status === 'pending' || status === 'approved') {
            renderBusinessState(status, profile);
            return;
        }

        if (status === 'rejected') {
            renderBusinessState(status, profile);
            showBusinessForm(profile);
            return;
        }

        showBusinessForm(null);
    }

    function renderBusinessState(status, profile) {
        var icon = 'fa fa-clock-o h4 m-0 mr-2 text-warning';
        var title = "{{ trans('lang.business_account_pending_title') }}";
        var text = "{{ trans('lang.business_account_pending_text') }}";

        if (status === 'approved') {
            icon = 'fa fa-check-circle h4 m-0 mr-2 text-success';
            title = "{{ trans('lang.business_account_approved_title') }}";
            text = "{{ trans('lang.business_account_approved_text') }}";
        } else if (status === 'rejected') {
            icon = 'fa fa-times-circle h4 m-0 mr-2 text-danger';
            title = "{{ trans('lang.business_account_rejected_title') }}";
            text = "{{ trans('lang.business_account_rejected_text') }}";
        }

        $('#business_state_icon').attr('class', icon);
        $('#business_state_title').text(title);
        $('#business_state_text').text(text);

        if (status === 'rejected' && profile && profile.rejectionReason) {
            $('#business_reason_text').text(' ' + profile.rejectionReason);
            $('#business_reason').show();
        } else {
            $('#business_reason').hide();
        }

        if (profile) {
            $('#business_view_company').text(profile.companyName || '-');
            $('#business_view_registration').text(profile.registrationNumber || '-');
            $('#business_view_document').html(businessDocumentLink(profile.documentUrl));
            $('#business_submitted_details').show();
        }

        $('#business_state').show();
    }

    function showBusinessForm(profile) {
        var title = "{{ trans('lang.business_account_form_title') }}";

        if (profile) {
            title = "{{ trans('lang.business_account_form_title_again') }}";
            $('#business_company').val(profile.companyName || '');
            $('#business_registration').val(profile.registrationNumber || '');
        }

        $('#business_form_title').text(title);
        $('#business_form_wrap').show();
    }

    /* A Firebase Storage download URL ARRIVES ALREADY PERCENT-ENCODED. Running
     * encodeURI over it turns %2F into %252F and the link 404s silently, so the
     * URL is used exactly as stored and only the characters that would break
     * out of an HTML attribute are escaped. */
    function businessAttrUrl(url) {
        if (!url) {
            return '';
        }

        return String(url)
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function businessDocumentLink(url) {
        if (!url) {
            return '-';
        }

        return '<a href="' + businessAttrUrl(url) + '" target="_blank" rel="noopener noreferrer">' +
            '<i class="fa fa-file-text-o mr-1"></i>' +
            "{{ trans('lang.business_account_view_document') }}" +
            '</a>';
    }

    function businessError(message) {
        $('#business_error').text(message).show();
        window.scrollTo(0, 0);
    }

    /* The file name is never reused. A customer's own upload naming their
     * company would otherwise be guessable from another account. */
    function businessUuid() {
        if (window.crypto && typeof window.crypto.randomUUID === 'function') {
            return window.crypto.randomUUID();
        }

        var bytes = new Uint8Array(16);

        if (window.crypto && typeof window.crypto.getRandomValues === 'function') {
            window.crypto.getRandomValues(bytes);
        } else {
            for (var i = 0; i < bytes.length; i++) {
                bytes[i] = Math.floor(Math.random() * 256);
            }
        }

        var out = '';

        for (var j = 0; j < bytes.length; j++) {
            out += ('0' + bytes[j].toString(16)).slice(-2);
        }

        return out;
    }

    function businessExtension(name) {
        var parts = String(name || '').split('.');

        if (parts.length < 2) {
            return 'bin';
        }

        var ext = parts.pop().toLowerCase().replace(/[^a-z0-9]/g, '');

        return ext === '' ? 'bin' : ext;
    }

    $('#business_document').on('change', function (event) {
        var file = event.target.files ? event.target.files[0] : null;

        businessDocumentUrl = '';
        $('#business_document_state').text('');

        if (!file) {
            return;
        }

        if (file.size > BUSINESS_MAX_BYTES) {
            businessError("{{ trans('lang.business_account_document_too_large') }}");
            $(this).val('');
            return;
        }

        $('#business_error').hide();
        businessUploading = true;
        $('#business_document_state').text("{{ trans('lang.business_account_uploading') }}");

        var path = 'business_documents/' + businessUserId + '/' + businessUuid() + '.' + businessExtension(file.name);
        var task = firebase.storage().ref().child(path).put(file);

        task.on('state_changed', function () {
        }, function (error) {
            console.error('business document upload failed', error);
            businessUploading = false;
            $('#business_document_state').text('');
            businessError("{{ trans('lang.business_account_upload_failed') }}");
        }, function () {
            task.snapshot.ref.getDownloadURL().then(function (downloadUrl) {
                businessUploading = false;
                businessDocumentUrl = downloadUrl;
                $('#business_document_state').text("{{ trans('lang.business_account_uploaded') }}");
            }).catch(function (error) {
                console.error('business document url could not be read', error);
                businessUploading = false;
                $('#business_document_state').text('');
                businessError("{{ trans('lang.business_account_upload_failed') }}");
            });
        });
    });

    $('#business_submit').on('click', async function () {
        var company = $.trim($('#business_company').val());
        var registration = $.trim($('#business_registration').val());

        $('#business_error').hide();

        if (company === '') {
            businessError("{{ trans('lang.business_account_company_required') }}");
            return;
        }

        if (registration === '') {
            businessError("{{ trans('lang.business_account_registration_required') }}");
            return;
        }

        if (businessUploading) {
            businessError("{{ trans('lang.business_account_still_uploading') }}");
            return;
        }

        if (businessDocumentUrl === '') {
            businessError("{{ trans('lang.business_account_document_required') }}");
            return;
        }

        jQuery("#overlay").show();

        try {
            /* Re-read before writing. A tab left open since before an admin
             * decided would otherwise overwrite that decision with a fresh
             * pending request. */
            var snapshot = await businessRef.get();
            var existing = (snapshot.exists && snapshot.data().businessProfile)
                ? (snapshot.data().businessProfile.status || 'pending')
                : '';

            if (existing === 'pending' || existing === 'approved') {
                jQuery("#overlay").hide();
                businessError("{{ trans('lang.business_account_already_open') }}");
                window.setTimeout(function () {
                    window.location.reload();
                }, 2500);
                return;
            }

            /* WRITTEN AS A WHOLE MAP, unlike the admin panel's dotted-path
             * updates. The admin writes one field and must not erase the
             * evidence around it; this write SUPPLIES all of that evidence, and
             * replacing the map is what clears a previous refusal's
             * rejectionReason, reviewedAt and reviewedBy. Leaving them behind
             * would show an old refusal's reason against a new request.
             *
             * The five fields below are the whole of it. No decision field is
             * written here, only cleared by being absent from a request the
             * admin has not yet seen. */
            await businessRef.update({
                'accountType': 'business',
                'businessProfile': {
                    companyName: company,
                    registrationNumber: registration,
                    documentUrl: businessDocumentUrl,
                    submittedAt: new Date().toISOString(),
                    status: 'pending'
                }
            });

            window.location.reload();
        } catch (error) {
            console.error('business account could not be submitted', error);
            jQuery("#overlay").hide();
            businessError("{{ trans('lang.business_account_submit_failed') }}");
        }
    });
</script>
