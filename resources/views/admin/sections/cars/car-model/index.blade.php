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

        /* Sortable column styles */
        .sortable {
            cursor: pointer;
            user-select: none;
            transition: background-color 0.2s;
        }

        .sortable:hover {
            background-color: rgba(0, 0, 0, 0.05);
        }

        .sort-icon {
            font-size: 14px;
            margin-left: 5px;
            opacity: 0.5;
            transition: opacity 0.2s;
        }

        .sortable:hover .sort-icon {
            opacity: 1;
        }

        .sortable .la-sort-up,
        .sortable .la-sort-down {
            opacity: 1;
        }

        /* Filter row styles */
        .filter-row th {
            padding: 8px;
            background-color: #f8f9fa;
        }

        .filter-input {
            width: 100%;
            padding: 6px 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 13px;
        }

        .filter-input:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
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
                <table class="custom-table" id="car-models-table">
                    <thead>
                        <tr>
                            <th>{{ __('Image') }}</th>
                            <th class="sortable" data-column="car-type">
                                {{ __('Car Type') }} <i class="las la-sort sort-icon"></i>
                            </th>
                            <th class="sortable" data-column="model-name">
                                {{ __('Model Name') }} <i class="las la-sort sort-icon"></i>
                            </th>
                            <th>{{ __('Status') }}</th>
                            <th></th>
                        </tr>
                        <tr class="filter-row">
                            <th></th>
                            <th>
                                <input type="text" class="form-control form-control-sm filter-input" 
                                       id="filter-car-type" placeholder="{{ __('Search car type...') }}">
                            </th>
                            <th>
                                <input type="text" class="form-control form-control-sm filter-input" 
                                       id="filter-model-name" placeholder="{{ __('Search model...') }}">
                            </th>
                            <th></th>
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

            // Filter functionality
            $('.filter-input').on('keyup', function() {
                filterTable();
            });

            // Sorting functionality
            let sortDirection = {};
            $('.sortable').on('click', function() {
                const column = $(this).data('column');
                const $icon = $(this).find('.sort-icon');
                
                // Reset other column icons
                $('.sortable').not(this).find('.sort-icon').removeClass('la-sort-up la-sort-down').addClass('la-sort');
                
                // Toggle sort direction
                if (!sortDirection[column] || sortDirection[column] === 'desc') {
                    sortDirection[column] = 'asc';
                    $icon.removeClass('la-sort la-sort-down').addClass('la-sort-up');
                } else {
                    sortDirection[column] = 'desc';
                    $icon.removeClass('la-sort la-sort-up').addClass('la-sort-down');
                }
                
                sortTable(column, sortDirection[column]);
            });

            function filterTable() {
                const carTypeFilter = $('#filter-car-type').val().toLowerCase();
                const modelNameFilter = $('#filter-model-name').val().toLowerCase();

                $('#car-models-table tbody tr').each(function() {
                    const carType = $(this).find('td:eq(1)').text().toLowerCase();
                    const modelName = $(this).find('td:eq(2)').text().toLowerCase();

                    const carTypeMatch = carType.includes(carTypeFilter);
                    const modelNameMatch = modelName.includes(modelNameFilter);

                    if (carTypeMatch && modelNameMatch) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            }

            function sortTable(column, direction) {
                const tbody = $('#car-models-table tbody');
                const rows = tbody.find('tr').get();

                rows.sort(function(a, b) {
                    let aVal, bVal;

                    if (column === 'car-type') {
                        aVal = $(a).find('td:eq(1)').text().trim();
                        bVal = $(b).find('td:eq(1)').text().trim();
                    } else if (column === 'model-name') {
                        aVal = $(a).find('td:eq(2)').text().trim();
                        bVal = $(b).find('td:eq(2)').text().trim();
                    }

                    if (direction === 'asc') {
                        return aVal.localeCompare(bVal);
                    } else {
                        return bVal.localeCompare(aVal);
                    }
                });

                tbody.empty().append(rows);
            }
        })
    </script>
@endpush
