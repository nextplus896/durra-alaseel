@extends('admin.layouts.master')

@push('css')
    <style>
        .fileholder {
            min-height: 374px !important;
        }

        .fileholder-files-view-wrp.accept-single-file .fileholder-single-file-view,
        .fileholder-files-view-wrp.fileholder-perview-single .fileholder-single-file-view {
            height: 330px !important;
        }
    </style>
@endpush

@section('page-title')
    @include('admin.components.page-title', ['title' => __($page_title)])
@endsection

@section('breadcrumb')
    @include('admin.components.breadcrumb', [
        'breadcrumbs' => [
            [
                'name' => __('Dashboard'),
                'url' => setRoute('admin.dashboard'),
            ],
        ],
        'active' => __('Branch Management'),
    ])
@endsection

@section('content')
    <div class="table-area">
        <div class="table-wrapper">
            <div class="table-header">
                <h5 class="title">{{ __($page_title) }}</h5>
                <div class="table-btn-area">
                    @include('admin.components.link.custom', [
                        'text' => __('Add Branch'),
                        'class' => 'btn btn--base',
                        'href' => setRoute('admin.branch.create'),
                        'permission' => 'admin.branch.create',
                    ])
                </div>
            </div>
            <div class="table-responsive">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Address') }}</th>
                            <th>{{ __('Service Radius (km)') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Last Edit By') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($branches ?? [] as $key => $item)
                            <tr data-item="{{ $item }}">
                                <td>{{ $item->name ?? '' }}</td>
                                <td>{{ Str::limit($item->address ?? '-', 40) }}</td>
                                <td>{{ $item->service_radius_km }} {{ __('km') }}</td>
                                <td>
                                    @include('admin.components.form.switcher', [
                                        'name' => 'status',
                                        'value' => $item->status,
                                        'options' => [__('Enable') => 1, __('Disable') => 0],
                                        'onload' => true,
                                        'data_target' => $item->id,
                                    ])
                                </td>
                                <td>{{ $item->admin->full_name ?? '-' }}</td>
                                <td>
                                    <a href="{{ setRoute('admin.branch.delivery.settings', $item->id) }}"
                                        class="btn btn--base btn--info"><i class="las la-truck"></i></a>
                                    @include('admin.components.link.edit-default', [
                                        'href' => setRoute('admin.branch.edit', $item->id),
                                        'class' => 'edit-modal-button',
                                        'permission' => 'admin.branch.edit',
                                    ])
                                    <button class="btn btn--base btn--danger delete-modal-button"><i
                                            class="las la-trash-alt"></i></button>
                                </td>
                            </tr>
                        @empty
                            @include('admin.components.alerts.empty', ['colspan' => 6])
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        {{ get_paginate($branches) }}
    </div>
@endsection

@push('script')
    <script>
        $(".delete-modal-button").click(function() {
            var oldData = JSON.parse($(this).parents("tr").attr("data-item"));
            var actionRoute = "{{ setRoute('admin.branch.delete') }}";
            var target = oldData.id;
            var message = `{{ __('Are you sure to delete this branch?') }}`;

            openDeleteModal(actionRoute, target, message);
        });

        $(document).ready(function() {
            // Switcher
            switcherAjax("{{ setRoute('admin.branch.status.update') }}");
        })
    </script>
@endpush
