@extends('user.layouts.master')

@push('css')
@endpush

@section('breadcrumb')
    @include('user.components.breadcrumb', [
        'breadcrumbs' => [
            [
                'name' => __('Dashboard'),
                'url' => setRoute('user.dashboard'),
            ],
        ],
        'active' => __('Support Tickets'),
    ])
@endsection

@section('content')
    <div class="row mb-20-none">
        <div class="col-xl-12 col-lg-12 mb-20">
            <div class="custom-card mt-10">
                <div class="dashboard-header-wrapper">
                    <h4 class="title">{{ __('Add New Ticket') }}</h4>
                </div>
                <div class="card-body">
                    <form class="card-form" action="{{ route('user.support.ticket.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <div class="col-xl-12 col-lg-12 form-group">
                                <label>{{ __('Subject') }}<span>*</span></label>
                                <input type="text" class="form--control" name="subject" placeholder="{{ __('Enter Subject') }}...">
                            </div>
                            <div class="col-xl-12 col-lg-12 form-group">
                                <label>{{ __('Message') }} <span class="text--base">*</span></label>
                                <textarea class="form--control" name="message" placeholder="{{ __('Write Here') }}…"></textarea>
                            </div>
                            <div class="col-xl-12 col-lg-12 form-group">
                                <label>{{ __('Attachments') }} <span>({{ __('Optional') }})</span></label>
                                <div class="dropzone-wrapper" data-direct="true" data-file_limit="15" data-max_size="20" data-accepted="image/*">
                                    <div class="dropzone dropzone-standalone" id="fileUpload-dropzone"></div>
                                    <input type="file" name="attachment[]" id="fileUpload" data-direct-input accept="image/*" data-max_size="20" data-file_limit="15" multiple style="display:none">
                                </div>
                            </div>
                        </div>
                        <div class="button pt-3">
                            <button type="submit" class="btn btn--base w-100">{{ __('Add New') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script></script>
@endpush
