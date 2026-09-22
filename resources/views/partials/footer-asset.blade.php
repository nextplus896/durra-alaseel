<!-- jquery -->
<script src="{{ asset('frontend/js/jquery-3.6.0.js') }}"></script>
<!-- bootstrap js -->
<script src="{{ asset('frontend/js/bootstrap.bundle.js') }}"></script>
<!-- swipper js -->
<script src="{{ asset('frontend/js/swiper.js') }}"></script>
<!-- lightcase js-->
<script src="{{ asset('frontend/js/lightcase.js') }}"></script>
<!-- odometer js -->
<script src="{{ asset('frontend/js/odometer.js') }}"></script>
<!-- viewport js -->
<script src="{{ asset('frontend/js/viewport.jquery.js') }}"></script>
<!-- AOS js -->
<script src="{{ asset('frontend/js/aos.js') }}"></script>
<!-- smooth scroll js -->
<script src="{{ asset('frontend/js/smoothscroll.js') }}"></script>
<!-- nice select js -->
<script src="{{ asset('frontend/js/jquery.nice-select.js') }}"></script>
<!-- select2 -->
<script src="{{ asset('frontend/js/select2.js') }}"></script>
<meta name="csrf-token" content="{{ csrf_token() }}">
<script>
    window.dropzoneUploadUrl = "{{ setRoute('fileholder.upload') }}";
    window.dropzoneRemoveUrl = "{{ setRoute('fileholder.remove') }}";
</script>
<!-- main -->
<!--  Popup -->
<script src="{{ asset('backend/library/popup/jquery.magnific-popup.js') }}"></script>
<!-- ApexCharts -->
<script src="{{ asset('frontend/js/apexcharts.js') }}"></script>
<script src="{{ asset('frontend/js/main.js') }}"></script>
<script src="{{ asset('backend/library/dropzone/dropzone.min.js') }}"></script>
<script src="{{ asset('backend/js/dropzone-init.js') }}"></script>

<script>
    function openDeleteModal(URL,target,message,actionBtnText = "{{ __('Remove') }}",method = "DELETE"){
    if(URL == "" || target == "") {
        return false;
    }

    if(message == "") {
        message = "{{ __('Are you sure to delete ?') }}";
    }
    var method = `<input type="hidden" name="_method" value="${method}">`;
    openModalByContent(
        {
            content: `<div class="card modal-alert border-0">
                        <div class="card-body">
                            <form method="POST" action="${URL}">
                                <input type="hidden" name="_token" value="${laravelCsrf()}">
                                ${method}
                                <div class="head mb-3">
                                    {{ __('${message}') }}
                                    <input type="hidden" name="target" value="${target}">
                                </div>
                                <div class="foot d-flex align-items-center justify-content-between">
                                    <button type="button" class="modal-close btn btn--info">{{ __("Close") }}</button>
                                    <button type="submit" class="alert-submit-btn btn btn--danger btn-loading">${actionBtnText}</button>
                                </div>
                            </form>
                        </div>
                    </div>`,
        },

    );
}
</script>

@include('admin.partials.notify')
