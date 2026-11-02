<?php
$default = get_default_language_code() ?? 'en';
$default_lng = 'en';
?>
@extends('vendor-end.layouts.master')

@push('css')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">

<style>
    /* Fleet Page Modern Styles & Cairo Font */
    .fleet-page-wrapper {
        padding-bottom: 40px;
        font-family: 'Cairo', 'DM Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
    }
    
    .fleet-page-wrapper h3,
    .fleet-page-wrapper h4,
    .fleet-page-wrapper p,
    .fleet-page-wrapper span,
    .fleet-page-wrapper small,
    .fleet-page-wrapper strong,
    .fleet-page-wrapper button,
    .fleet-page-wrapper a,
    .fleet-page-wrapper input,
    .fleet-page-wrapper select {
        font-family: 'Cairo', 'DM Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
    }

    /* Fleet Header & Metrics */
    .fleet-header-card {
        background: #ffffff;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
        border: 1px solid #edf2f7;
        margin-bottom: 24px;
    }
    .fleet-title-area h3 {
        font-size: 1.55rem;
        font-weight: 800;
        color: #1a202c;
        margin-bottom: 6px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .fleet-title-area p {
        color: #718096;
        font-size: 0.95rem;
        font-weight: 600;
        margin-bottom: 0;
    }

    /* Metric Badges / Stats */
    .fleet-stat-badge {
        display: flex;
        align-items: center;
        gap: 12px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        padding: 10px 16px;
        border-radius: 12px;
    }
    .fleet-stat-badge .stat-icon {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        flex-shrink: 0;
    }
    .stat-icon.total { background: #e0e7ff; color: #4338ca; }
    .stat-icon.active { background: #dcfce7; color: #15803d; }
    .stat-icon.inactive { background: #fee2e2; color: #b91c1c; }
    .fleet-stat-badge .stat-info small {
        display: block;
        font-size: 0.82rem;
        color: #64748b;
        font-weight: 700;
    }
    .fleet-stat-badge .stat-info strong {
        font-size: 1.35rem;
        font-weight: 900;
        color: #0f172a;
        line-height: 1.2;
    }

    /* Filter Toolbar */
    .fleet-filter-bar {
        background: #ffffff;
        border-radius: 14px;
        padding: 16px 20px;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.03);
        border: 1px solid #edf2f7;
        margin-bottom: 20px;
    }
    .fleet-filter-bar .form-control,
    .fleet-filter-bar .form-select,
    .fleet-filter-bar .btn {
        height: 46px !important;
        border-radius: 10px;
        font-size: 0.92rem;
        font-weight: 600;
        box-sizing: border-box;
    }
    .fleet-filter-bar .form-control,
    .fleet-filter-bar .form-select {
        border: 1px solid #cbd5e1;
        background-color: #f8fafc;
        transition: all 0.2s ease;
        padding-top: 0;
        padding-bottom: 0;
        line-height: 44px;
    }
    .fleet-filter-bar .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0 16px;
    }
    .fleet-filter-bar .form-control:focus,
    .fleet-filter-bar .form-select:focus {
        border-color: var(--primary-color, #4338ca);
        background-color: #ffffff;
        box-shadow: 0 0 0 3px rgba(67, 56, 202, 0.12);
    }
    
    /* RTL Specific Fixes for Select dropdown arrows */
    body.rtl .fleet-filter-bar .form-select {
        background-position: left 0.75rem center !important;
        padding-left: 2.25rem !important;
        padding-right: 0.85rem !important;
    }
    body:not(.rtl) .fleet-filter-bar .form-select {
        background-position: right 0.75rem center !important;
        padding-right: 2.25rem !important;
        padding-left: 0.85rem !important;
    }
    .search-input-group {
        position: relative;
        height: 46px;
    }
    .search-input-group .form-control {
        height: 100% !important;
    }
    .search-input-group i {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: 1.1rem;
        pointer-events: none;
    }
    body.rtl .search-input-group i {
        right: 14px;
        left: auto;
    }
    body.rtl .search-input-group input {
        padding-right: 40px !important;
        padding-left: 14px !important;
    }
    body:not(.rtl) .search-input-group i {
        left: 14px;
        right: auto;
    }
    body:not(.rtl) .search-input-group input {
        padding-left: 40px !important;
        padding-right: 14px !important;
    }

    /* Fleet View Switcher Toolbar */
    .fleet-view-toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #ffffff;
        border-radius: 14px;
        padding: 12px 20px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
        border: 1px solid #edf2f7;
        margin-bottom: 22px;
    }
    .fleet-count-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 0.92rem;
        font-weight: 700;
        color: #475569;
    }
    .fleet-count-badge i {
        font-size: 1.25rem;
        color: var(--primary-color, #4338ca);
    }
    .fleet-count-badge strong {
        color: #0f172a;
        font-weight: 900;
    }
    .view-mode-toggle-group {
        display: inline-flex;
        background: #f1f5f9;
        padding: 4px;
        border-radius: 10px;
        gap: 4px;
    }
    .btn-view-mode {
        border: none;
        background: transparent;
        color: #64748b;
        font-size: 0.88rem;
        font-weight: 800;
        padding: 6px 14px;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .btn-view-mode:hover {
        color: #0f172a;
    }
    .btn-view-mode.active {
        background: #ffffff;
        color: var(--primary-color, #4338ca);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    }
    .btn-view-mode i {
        font-size: 1.15rem;
    }

    /* Base Vehicle Card Styles */
    .vehicle-card {
        background: #ffffff;
        border-radius: 18px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 18px rgba(0, 0, 0, 0.04);
        overflow: hidden;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
    }
    .vehicle-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 14px 30px rgba(0, 0, 0, 0.08);
        border-color: #cbd5e1;
    }
    .vehicle-img-wrapper {
        position: relative;
        background: linear-gradient(135deg, #f8fafc 0%, #edf2f7 100%);
        overflow: hidden;
    }
    .vehicle-img-wrapper img {
        width: 100%;
        object-fit: cover;
        transition: transform 0.4s ease;
    }
    .vehicle-card:hover .vehicle-img-wrapper img {
        transform: scale(1.04);
    }

    /* Status Switcher on Top of Image */
    .vehicle-badges-top {
        position: absolute;
        top: 12px;
        left: 12px;
        right: 12px;
        display: flex;
        z-index: 2;
    }
    body.rtl .vehicle-badges-top {
        justify-content: flex-start;
    }
    body:not(.rtl) .vehicle-badges-top {
        justify-content: flex-end;
    }
    
    .vehicle-switcher-wrap {
        background: rgba(255, 255, 255, 0.95);
        padding: 4px 6px;
        border-radius: 20px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        backdrop-filter: blur(8px);
    }
    .vehicle-switcher-wrap .toggle-container .switch-toggles {
        height: 28px !important;
        min-width: 130px !important;
        max-width: 130px !important;
        border-radius: 20px !important;
        background-color: #e2e8f0 !important;
        box-shadow: none !important;
    }
    .vehicle-switcher-wrap .toggle-container .switch-toggles::after {
        height: 28px !important;
        border-radius: 20px !important;
        background: #dc2626 !important;
        box-shadow: none !important;
    }
    .vehicle-switcher-wrap .toggle-container .switch-toggles.active::after {
        background: #16a34a !important;
        box-shadow: none !important;
    }
    .vehicle-switcher-wrap .toggle-container .switch-toggles .switch {
        line-height: 28px !important;
        font-size: 11px !important;
        font-weight: 800 !important;
        border-radius: 20px !important;
    }

    /* Common Inner Meta */
    .vehicle-header-meta {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 10px;
        margin-bottom: 6px;
    }
    .vehicle-title {
        font-size: 1.15rem;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
        line-height: 1.35;
    }
    .plate-number-badge {
        background: #f1f5f9;
        color: #334155;
        font-size: 0.76rem;
        font-weight: 700;
        padding: 3px 8px;
        border-radius: 6px;
        border: 1px dashed #cbd5e1;
        letter-spacing: 0.5px;
        white-space: nowrap;
        flex-shrink: 0;
    }
    .vehicle-branch-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        padding: 4px 10px;
        border-radius: 8px;
        font-size: 0.8rem;
        color: #334155;
        font-weight: 600;
        margin-bottom: 12px;
        width: fit-content;
    }
    .vehicle-branch-chip i {
        color: var(--primary-color, #4338ca);
    }

    /* Specs Strip */
    .c1-specs-strip {
        display: flex;
        align-items: center;
        gap: 10px;
        color: #334155;
        font-size: 0.88rem;
        font-weight: 600;
        margin-bottom: 12px;
        flex-wrap: wrap;
    }
    .c1-specs-strip i {
        font-size: 1.05rem;
        color: #64748b;
    }
    .c1-specs-strip .spec-dot {
        width: 5px;
        height: 5px;
        background: #94a3b8;
        border-radius: 50%;
    }

    /* Chips */
    .c1-chips-container {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 14px;
    }
    .c1-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 7px 12px;
        border-radius: 10px;
        font-size: 0.88rem;
        font-weight: 600;
        line-height: 1.3;
    }
    .c1-chip i {
        font-size: 1.1rem;
    }
    .c1-chip strong {
        font-weight: 700;
    }
    .c1-chip.km {
        background: #f0fdf4;
        color: #15803d;
        border: 1.5px solid #86efac;
    }
    .c1-chip.ins {
        background: #eff6ff;
        color: #1d4ed8;
        border: 1.5px solid #93c5fd;
    }
    .c1-chip.ded {
        background: #f8fafc;
        color: #334155;
        border: 1.5px solid #cbd5e1;
    }

    /* Pricing Box */
    .c1-pricing-card {
        background: #f8fafc;
        border: 1.5px solid #e2e8f0;
        border-radius: 14px;
        padding: 10px 14px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: all 0.2s ease;
    }
    .c1-pricing-card:hover {
        background: #ffffff;
        border-color: #86efac;
        box-shadow: 0 4px 12px rgba(22, 163, 74, 0.08);
    }
    .c1-pricing-main small {
        display: block;
        font-size: 0.78rem;
        color: #166534;
        font-weight: 600;
        margin-bottom: 1px;
    }
    .c1-pricing-main .price-val {
        font-size: 1.45rem;
        font-weight: 700;
        color: #15803d;
        line-height: 1;
    }
    .c1-pricing-main .price-cur {
        font-size: 0.8rem;
        font-weight: 600;
        color: #166534;
        margin-inline-start: 2px;
    }
    .c1-pricing-tiers {
        font-size: 0.8rem;
        font-weight: 600;
        color: #475569;
        text-align: start;
        line-height: 1.4;
    }
    .c1-pricing-tiers strong {
        color: #0f172a;
        font-weight: 700;
        font-size: 0.86rem;
    }

    /* Action Buttons */
    .vehicle-actions {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
    }
    .btn-action-edit {
        background: #f8fafc;
        color: #1e293b;
        border: 1.5px solid #cbd5e1;
        padding: 8px 12px;
        border-radius: 10px;
        font-weight: 600;
        font-size: 0.86rem;
        text-align: center;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        text-decoration: none;
    }
    .btn-action-edit:hover {
        background: var(--primary-color, #4338ca);
        border-color: var(--primary-color, #4338ca);
        color: #ffffff;
    }
    .btn-action-delete {
        background: #fef2f2;
        color: #dc2626;
        border: 1.5px solid #fecaca;
        padding: 8px 12px;
        border-radius: 10px;
        font-weight: 600;
        font-size: 0.86rem;
        text-align: center;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        cursor: pointer;
    }
    .btn-action-delete:hover {
        background: #dc2626;
        color: #ffffff;
        border-color: #dc2626;
    }

    /* ========================================================
       VIEW MODE 1: GRID-4 (Default: 4 Cards per row on XL/XXL)
       ======================================================== */
    .fleet-cars-grid:not(.is-list-view) .vehicle-card {
        display: flex;
        flex-direction: column;
        height: 100%;
    }
    .fleet-cars-grid:not(.is-list-view) .vehicle-img-wrapper {
        width: 100%;
        height: 185px;
    }
    .fleet-cars-grid:not(.is-list-view) .vehicle-body {
        padding: 16px;
        display: flex;
        flex-direction: column;
        flex-grow: 1;
    }
    .fleet-cars-grid:not(.is-list-view) .vehicle-info-col {
        display: flex;
        flex-direction: column;
        flex-grow: 1;
    }
    .fleet-cars-grid:not(.is-list-view) .vehicle-side-col {
        margin-top: auto;
    }
    .fleet-cars-grid:not(.is-list-view) .c1-pricing-card {
        margin-bottom: 12px;
    }

    /* ========================================================
       VIEW MODE 2: LIST (Full width Horizontal Rows)
       ======================================================== */
    .fleet-cars-grid.is-list-view .vehicle-card {
        display: flex;
        flex-direction: row;
        align-items: stretch;
        min-height: 200px;
    }
    .fleet-cars-grid.is-list-view .vehicle-img-wrapper {
        width: 280px;
        min-width: 280px;
        height: auto;
    }
    body.rtl .fleet-cars-grid.is-list-view .vehicle-img-wrapper {
        border-radius: 0 18px 18px 0;
    }
    body:not(.rtl) .fleet-cars-grid.is-list-view .vehicle-img-wrapper {
        border-radius: 18px 0 0 18px;
    }
    .fleet-cars-grid.is-list-view .vehicle-img-wrapper img {
        height: 100%;
        object-fit: cover;
    }
    .fleet-cars-grid.is-list-view .vehicle-body {
        display: flex;
        flex-direction: row;
        align-items: stretch;
        justify-content: space-between;
        padding: 18px 22px;
        gap: 24px;
        flex-grow: 1;
    }
    .fleet-cars-grid.is-list-view .vehicle-info-col {
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }
    .fleet-cars-grid.is-list-view .vehicle-branch-chip {
        margin-bottom: 8px;
    }
    .fleet-cars-grid.is-list-view .c1-specs-strip {
        margin-bottom: 8px;
    }
    .fleet-cars-grid.is-list-view .c1-chips-container {
        margin-bottom: 0;
    }
    .fleet-cars-grid.is-list-view .vehicle-side-col {
        width: 250px;
        min-width: 250px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        border-inline-start: 1px solid #edf2f7;
        padding-inline-start: 22px;
    }
    .fleet-cars-grid.is-list-view .c1-pricing-card {
        margin-bottom: 12px;
    }

    /* Responsive Adaptation for List View */
    @media (max-width: 991px) {
        .fleet-cars-grid.is-list-view .vehicle-card {
            flex-direction: column;
        }
        .fleet-cars-grid.is-list-view .vehicle-img-wrapper {
            width: 100%;
            height: 200px;
            border-radius: 18px 18px 0 0 !important;
        }
        .fleet-cars-grid.is-list-view .vehicle-body {
            flex-direction: column;
            gap: 16px;
        }
        .fleet-cars-grid.is-list-view .vehicle-side-col {
            width: 100%;
            border-inline-start: none;
            border-top: 1px solid #edf2f7;
            padding-inline-start: 0;
            padding-top: 16px;
        }
    }

    /* Empty Fleet State */
    .empty-fleet-card {
        background: #ffffff;
        border-radius: 18px;
        border: 2px dashed #cbd5e1;
        padding: 50px 20px;
        text-align: center;
    }
    .empty-fleet-icon {
        width: 80px;
        height: 80px;
        background: #f1f5f9;
        color: #94a3b8;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 40px;
        margin: 0 auto 16px auto;
    }
</style>
@endpush

@section('content')
<div class="fleet-page-wrapper">
    {{-- Header Banner with Statistics & Add Button --}}
    <div class="fleet-header-card">
        <div class="row align-items-center g-3">
            <div class="col-xl-5 col-lg-4 col-md-12">
                <div class="fleet-title-area">
                    <h3>
                        <i class="las la-car-side text-primary"></i> 
                        {{ __('Cars Fleet') }}
                    </h3>
                    <p>{{ __('Manage and monitor your vehicles across branches') }}</p>
                </div>
            </div>
            <div class="col-xl-5 col-lg-5 col-md-8">
                <div class="d-flex flex-wrap gap-2">
                    <div class="fleet-stat-badge flex-fill">
                        <div class="stat-icon total">
                            <i class="las la-warehouse"></i>
                        </div>
                        <div class="stat-info">
                            <small>{{ __('Total Vehicles') }}</small>
                            <strong>{{ $total_cars ?? count($cars) }}</strong>
                        </div>
                    </div>
                    <div class="fleet-stat-badge flex-fill">
                        <div class="stat-icon active">
                            <i class="las la-check-circle"></i>
                        </div>
                        <div class="stat-info">
                            <small>{{ __('Active in Rental') }}</small>
                            <strong>{{ $active_cars ?? 0 }}</strong>
                        </div>
                    </div>
                    <div class="fleet-stat-badge flex-fill">
                        <div class="stat-icon inactive">
                            <i class="las la-eye-slash"></i>
                        </div>
                        <div class="stat-info">
                            <small>{{ __('Inactive / Hidden') }}</small>
                            <strong>{{ $inactive_cars ?? 0 }}</strong>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-lg-3 col-md-4 text-md-end text-start">
                <a href="{{ setRoute('vendor.car.create') }}" class="btn--base d-inline-flex align-items-center gap-2 px-3 py-2 fw-bold">
                    <i class="las la-plus-circle fs-5"></i> {{ __('Add car') }}
                </a>
            </div>
        </div>
    </div>

    {{-- Filter & Search Toolbar --}}
    <div class="fleet-filter-bar">
        <form action="{{ setRoute('vendor.car.index') }}" method="GET" id="fleetFilterForm">
            <div class="row g-2 align-items-center">
                {{-- Search Box --}}
                <div class="col-xl-4 col-lg-4 col-md-5">
                    <div class="search-input-group">
                        <input type="text" name="search" class="form-control" 
                            placeholder="{{ __('Search car model, plate...') }}"
                            value="{{ request('search') }}">
                        <i class="las la-search"></i>
                    </div>
                </div>

                {{-- Branch Filter --}}
                <div class="col-xl-3 col-lg-3 col-md-4">
                    <select class="form-select" name="branch_id" onchange="document.getElementById('fleetFilterForm').submit();">
                        <option value="">{{ __('All Branches') }}</option>
                        @foreach ($branches ?? [] as $branch)
                            <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Status Filter --}}
                <div class="col-xl-2 col-lg-2 col-md-3">
                    <select class="form-select" name="status" onchange="document.getElementById('fleetFilterForm').submit();">
                        <option value="all">{{ __('All Statuses') }}</option>
                        <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>{{ __('Active') }}</option>
                        <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>{{ __('Inactive') }}</option>
                    </select>
                </div>

                {{-- Submit & Clear Buttons --}}
                <div class="col-xl-3 col-lg-3 col-md-12 d-flex gap-2 justify-content-md-end">
                    <button type="submit" class="btn btn-dark d-inline-flex align-items-center gap-1 px-3 fw-bold">
                        <i class="las la-filter"></i> {{ __('Filter') }}
                    </button>
                    @if (request()->filled('branch_id') || (request()->filled('status') && request('status') !== 'all') || request()->filled('search'))
                        <a href="{{ setRoute('vendor.car.index') }}" class="btn btn-outline-danger d-inline-flex align-items-center gap-1 px-3 fw-bold" title="{{ __('Clear Filter') }}">
                            <i class="las la-times-circle"></i> {{ __('Reset') }}
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    {{-- View Switcher Toolbar (Grid-4 vs List) --}}
    <div class="fleet-view-toolbar">
        <div class="fleet-count-badge">
            <i class="las la-car"></i>
            <span>{{ __('Showing') }}: <strong>{{ $cars->total() ?? count($cars) }}</strong> {{ __('Vehicles') }}</span>
        </div>
        <div class="view-mode-toggle-group">
            <button type="button" class="btn-view-mode active" id="btnViewGrid" onclick="changeFleetView('grid')" title="{{ __('Grid View') }}">
                <i class="las la-th"></i>
                <span>{{ __('Grid') }}</span>
            </button>
            <button type="button" class="btn-view-mode" id="btnViewList" onclick="changeFleetView('list')" title="{{ __('List View') }}">
                <i class="las la-list"></i>
                <span>{{ __('List') }}</span>
            </button>
        </div>
    </div>

    {{-- Cars Grid / List Container --}}
    <div class="fleet-cars-grid" id="fleetCarsContainer">
        <div class="row g-3 g-xl-4" id="fleetCarsRow">
            @forelse ($cars ?? [] as $car)
                @php
                    $title = $car->car_title->$default->car_title ?? (($car->type->name ?? '') . ' ' . ($car->carModel->name ?? ''));
                    $carImage = $car->image_url;
                    $fallbackImage = asset('backend/images/default/default.webp');
                @endphp
                <div class="car-col col-xxl-3 col-xl-3 col-lg-4 col-md-6 col-12">
                    <div class="vehicle-card">
                        {{-- Vehicle Image & Status Switcher --}}
                        <div class="vehicle-img-wrapper">
                            <img src="{{ $carImage }}" alt="{{ $title }}" loading="lazy" onerror="this.onerror=null;this.src='{{ $fallbackImage }}';">
                            <div class="vehicle-badges-top">
                                <div class="vehicle-switcher-wrap">
                                    <div class="switch-field mb-0">
                                        @include('vendor-end.components.form.switcher', [
                                            'name' => 'currency_status',
                                            'value' => $car->status,
                                            'options' => [__('Enable') => 1, __('Disable') => 0],
                                            'onload' => true,
                                            'data_target' => $car->id,
                                            'permission' => 'vendor.car.status.update',
                                        ])
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Vehicle Details Body --}}
                        <div class="vehicle-body" style="{{ $car->status ? '' : 'opacity: 0.6;' }}">
                            {{-- Info Section (Left in LTR, Right in RTL) --}}
                            <div class="vehicle-info-col">
                                {{-- Header Title & Plate Number --}}
                                <div class="vehicle-header-meta">
                                    <h4 class="vehicle-title" title="{{ $title }}">
                                        {{ Str::limit($title, 35) }}
                                    </h4>
                                    @if(!empty($car->car_number))
                                        <span class="plate-number-badge" title="{{ __('Plate No.') }}">
                                            {{ $car->car_number }}
                                        </span>
                                    @endif
                                </div>

                                {{-- Branch Badge --}}
                                <div class="vehicle-branch-chip">
                                    <i class="las la-map-marker-alt"></i>
                                    <span>{{ $car->branch->name ?? __('No Branch Assigned') }}</span>
                                </div>

                                {{-- Specs Strip --}}
                                <div class="c1-specs-strip">
                                    <span><i class="las la-car-side text-muted"></i> {{ $car->type->name ?? '-' }}</span>
                                    <span class="spec-dot"></span>
                                    <span><i class="las la-calendar text-muted"></i> {{ $car->year ?? '-' }}</span>
                                    <span class="spec-dot"></span>
                                    <span><i class="las la-user-friends text-muted"></i> {{ $car->seat ?? '-' }} {{ __('Seats') }}</span>
                                </div>

                                {{-- Smart Chips for Insurance & KM --}}
                                <div class="c1-chips-container">
                                    <span class="c1-chip km" title="{{ __('Allowed KM') }}">
                                        <i class="las la-tachometer-alt"></i>
                                        <span>{{ __('Allowed KM') }}:</span>
                                        <strong>
                                            @if(isset($car->allowance_km) && $car->allowance_km > 0)
                                                {{ round($car->allowance_km) }} {{ __('km') }}
                                            @else
                                                {{ __('Unlimited') }}
                                            @endif
                                        </strong>
                                    </span>
                                    <span class="c1-chip ins" title="{{ __('Daily Insurance') }}">
                                        <i class="las la-shield-alt"></i>
                                        @if(isset($car->daily_insurance) && $car->daily_insurance > 0)
                                            <span>{{ __('Insurance') }}:</span>
                                            <strong>{{ get_amount($car->daily_insurance) }} {{ get_default_currency_code() }}</strong>
                                        @else
                                            <strong>{{ __('Insurance Included') }}</strong>
                                        @endif
                                    </span>
                                    @if(isset($car->deductible_insurance) && $car->deductible_insurance > 0)
                                        <span class="c1-chip ded" title="{{ __('Deductible Excess') }}">
                                            <i class="las la-file-invoice-dollar"></i>
                                            <span>{{ __('Deductible Excess') }}:</span>
                                            <strong>{{ get_amount($car->deductible_insurance) }} {{ get_default_currency_code() }}</strong>
                                        </span>
                                    @endif
                                </div>
                            </div>

                            {{-- Pricing & Actions Section --}}
                            <div class="vehicle-side-col">
                                {{-- Pricing Strip --}}
                                <div class="c1-pricing-card">
                                    <div class="c1-pricing-main">
                                        <small>{{ __('Daily Price') }}</small>
                                        <div>
                                            <span class="price-val">{{ get_amount($car->fees) }}</span>
                                            <span class="price-cur">{{ get_default_currency_code() }}</span>
                                        </div>
                                    </div>
                                    <div class="c1-pricing-tiers">
                                        @if(!empty($car->price_per_week) && $car->price_per_week > 0)
                                            <div>{{ __('Weekly') }}: <strong>{{ get_amount($car->price_per_week) }} {{ get_default_currency_code() }}</strong></div>
                                        @endif
                                        @if(!empty($car->price_per_month) && $car->price_per_month > 0)
                                            <div>{{ __('Monthly') }}: <strong>{{ get_amount($car->price_per_month) }} {{ get_default_currency_code() }}</strong></div>
                                        @endif
                                    </div>
                                </div>

                                {{-- Action Buttons --}}
                                <div class="vehicle-actions">
                                    <a href="{{ setRoute('vendor.car.edit', $car->id) }}" class="btn-action-edit">
                                        <i class="las la-pen"></i> {{ __('Edit') }}
                                    </a>
                                    <button type="button" class="btn-action-delete delete-modal-button"
                                        data-bs-toggle="modal"
                                        data-bs-target="#delateModal"
                                        data-item="{{ $car->id }}">
                                        <i class="las la-trash-alt"></i> {{ __('Delete') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="empty-fleet-card">
                        <div class="empty-fleet-icon">
                            <i class="las la-car-side"></i>
                        </div>
                        <h4 class="fw-bold mb-2">{{ __('No vehicles found') }}</h4>
                        <p class="text-muted mb-4">{{ __('Try adjusting your search criteria or add new vehicles to your fleet.') }}</p>
                        <a href="{{ setRoute('vendor.car.create') }}" class="btn--base d-inline-flex align-items-center gap-2">
                            <i class="las la-plus-circle"></i> {{ __('Add car') }}
                        </a>
                    </div>
                </div>
            @endforelse
        </div>
    </div>

    {{-- Pagination --}}
    <div class="mt-4 d-flex justify-content-center">
        {{ $cars->links() }}
    </div>
</div>
@endsection

@push('script')
    <script>
        // Fleet View Mode Switcher (Grid-4 vs List)
        function changeFleetView(mode) {
            const container = document.getElementById('fleetCarsContainer');
            const cols = container.querySelectorAll('.car-col');
            const btnGrid = document.getElementById('btnViewGrid');
            const btnList = document.getElementById('btnViewList');

            if (mode === 'list') {
                container.classList.add('is-list-view');
                cols.forEach(col => {
                    col.className = 'car-col col-12';
                });
                if (btnList) btnList.classList.add('active');
                if (btnGrid) btnGrid.classList.remove('active');
            } else {
                container.classList.remove('is-list-view');
                cols.forEach(col => {
                    col.className = 'car-col col-xxl-3 col-xl-3 col-lg-4 col-md-6 col-12';
                });
                if (btnGrid) btnGrid.classList.add('active');
                if (btnList) btnList.classList.remove('active');
            }

            // Save view preference
            localStorage.setItem('fleet_preferred_view', mode);
        }

        // Restore saved view preference on load
        document.addEventListener('DOMContentLoaded', function() {
            const savedView = localStorage.getItem('fleet_preferred_view') || 'grid';
            if (savedView === 'list') {
                changeFleetView('list');
            }
        });

        $('.delete-modal-button').click(function() {
            var oldData = JSON.parse($(this).attr("data-item"));
            var actionRoute = "{{ setRoute('vendor.car.delete') }}";
            var target = oldData;
            var message = `{{ __('Are you sure to delete this car?') }}`;
            openDeleteModal(actionRoute, target, message);
        });
    </script>
    <script>
        $(document).ready(function() {
            switcherAjax("{{ setRoute('vendor.car.status.update') }}");
        })
    </script>
@endpush
