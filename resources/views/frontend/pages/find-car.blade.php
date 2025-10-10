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
            @if ($component_name =='frontend.sections.find-section')
                @include('frontend.sections.car-search')
            @else
                @include($component_name)
            @endif
        @endif
    @empty
        @include('frontend.sections.demo')
    @endforelse

@endsection
