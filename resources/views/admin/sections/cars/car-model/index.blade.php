@extends('admin.layouts.master')

@push('css')
    <style>
        .fileholder {
            min-height: 200px !important;
        }

        .fileholder-files-view-wrp.accept-single-file .fileholder-single-file-view,
        .fileholder-files-view-wrp.fileholder-perview-single .fileholder-single-file-view {
            height: 200px !important;
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
        'active' => __('Car Models'),
    ])
@endsection

@section('content')
    <div class="table-area">
        <div class="table-wrapper">
            <div class="table-header">
                <h5 class="title">{{ __($page_title) }}</h5>
                <div class="table-btn-area">
                    @include('admin.components.link.add-default', [
                        'text' => __('Add Model'),
                        'href' => '#car-model-add',
                        'class' => 'modal-btn',
                        'permission' => 'admin.car.model.store',
                    ])
                </div>
            </div>
            <div class="table-responsive">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>{{ __('Image') }}</th>
                            <th>{{ __('Car Type') }}</th>
                            <th>{{ __('Model Name') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($car_models ?? [] as $item)
                            <tr data-item="{{ $item }}">
                                <td>
                                    <ul class="user-list">
                                        <li>
                                            <img src="{{ get_image($item->image ?? null, 'car-models') }}" alt="model">
                                        </li>
                                    </ul>
                                </td>
                                <td>{{ $item->carType->name ?? __('N/A') }}</td>
                                <td>{{ $item->name }}</td>
                                <td>
                                    @include('admin.components.form.switcher', [
                                        'name' => 'status',
                                        'value' => $item->status,
                                        'options' => [__('Enable') => 1, __('Disable') => 0],
                                        'onload' => true,
                                        'data_target' => $item->id,
                                        'permission' => 'admin.car.model.status.update',
                                    ])
                                </td>
                                <td>
                                    @include('admin.components.link.edit-default', [
                                        'href' => 'javascript:void(0)',
                                        'class' => 'edit-modal-button',
                                        'permission' => 'admin.car.model.update',
                                    ])
                                    @include('admin.components.link.delete-default', [
                                        'href' => 'javascript:void(0)',
                                        'class' => 'delete-modal-button',
                                        'permission' => 'admin.car.model.delete',
                                    ])
                                </td>
                            </tr>
                        @empty
                            @include('admin.components.alerts.empty', ['colspan' => 5])
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Add Modal --}}
    @include('admin.components.modals.car-model.add', [
        'car_types' => $car_types,
    ])

    {{-- Edit Modal --}}
    @include('admin.components.modals.car-model.edit', [
        'car_types' => $car_types,
    ])
@endsection

@push('script')
    <script>
        openModalWhenError('car-model-add', '#car-model-add');
        openModalWhenError('car-model-edit', '#car-model-edit');

        $(".delete-modal-button").click(function() {
            var oldData = JSON.parse($(this).parents("tr").attr("data-item"));
            var actionRoute = "{{ setRoute('admin.car.model.delete') }}";
            var target = oldData.id;
            var message = `Are you sure to delete <strong>${oldData.name}</strong> model?`;
            openDeleteModal(actionRoute, target, message);
        });

        $(document).ready(function() {
            switcherAjax("{{ setRoute('admin.car.model.status.update') }}");
        })
    </script>
@endpush
