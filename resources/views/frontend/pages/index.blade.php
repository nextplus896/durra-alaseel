@extends('frontend.layouts.master')
@php
    $default = get_default_language_code() ?? 'en';
    $default_lng = 'en';
@endphp
@push('css')
@endpush

@section('content')

    @forelse ($page_section->sections ?? [] as $item)
        @php
            $component_name = 'frontend.sections.' .$item->section->key;
        @endphp

        @if(View::exists($component_name))
            @include($component_name)
        @endif
    @empty
        @include('frontend.sections.demo')
    @endforelse

    {{-- <!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
            Start Banner Section
        ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->

    @include('frontend.sections.banner')
    <!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
            End Banner Section
        ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
    <!-- car-find -->
    @include('frontend.sections.car-find')

    @include('frontend.sections.features')

    <!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
                Start Security System
         ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
    @include('frontend.sections.security')
    <!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
                End Security System
         ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->

    <!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
            Why Choice US Section
        ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
    @include('frontend.sections.choose-us')
    <!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
            statistics-section
        ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
    @include('frontend.sections.statistics')
    <!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
            app-section
        ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
    @include('frontend.sections.app') --}}
@endsection
