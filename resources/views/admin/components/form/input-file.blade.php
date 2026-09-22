@if (isset($label) && $label != false)
    @php
        $for_id = preg_replace('/[^A-Za-z0-9\-]/', '', Str::lower($label));
    @endphp
    <label for="{{ $for_id ?? "" }}">{!! $label ?? "" !!}</label>
@endif
@php
    $dz_accept = $accept ?? "image/*";
    $dz_old_files = $old_files ?? "";
    $dz_old_path = $old_files_path ?? "";
    $dz_for_id = $for_id ?? "dz-".Str::random(6);
@endphp
<div class="dropzone-wrapper" data-preview-name="{{ $dz_old_files }}" data-preview-path="{{ $dz_old_path }}">
    <div class="dropzone" id="{{ $dz_for_id }}-dropzone" data-accepted="{{ $dz_accept }}" {{ $attribute ?? "" }}></div>
    <input type="hidden" name="fileholder-{{ $name ?? "" }}" class="dropzone-hidden-input" value="" accept="{{ $dz_accept }}" data-preview-name="{{ $dz_old_files }}" data-preview-path="{{ $dz_old_path }}">
    <input type="hidden" name="old_{{ $name ?? "image" }}" value="{{ $dz_old_files }}">
    {{-- dummy file input to satisfy hasFile() checks in controllers; synced via JS DataTransfer --}}
    <input type="file" name="{{ $name ?? "" }}" class="dropzone-sync-file" accept="{{ $dz_accept }}" style="display:none">
</div>
@error($name ?? false)
    <span class="invalid-feedback d-block" role="alert">
        <strong>{{ $message }}</strong>
    </span>
@enderror