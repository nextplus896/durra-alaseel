@extends('frontend.layouts.master')

@push('css')
@endpush

<?php
$default = get_default_language_code() ?? 'en';
$default_lng = 'en';
?>

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
        Vendor Landing page
    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
    @include('frontend.sections.vendor-banner')

    <!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        vendor driver need
    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
    @include('frontend.sections.vendor-require')

    <!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
            Safety Drive
    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
   @include('frontend.sections.vendor-safety') --}}
@endsection


@push('script')
@endpush
