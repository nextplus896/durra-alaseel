@if (admin_permission_by_name('admin.car.model.store'))
    <div id="car-model-add" class="mfp-hide large">
        <div class="modal-data">
            <div class="modal-header px-0">
                <h5 class="modal-title">{{ __('Add Car Model') }}</h5>
            </div>
            <div class="modal-form-data">
                <form class="modal-form" method="POST" action="{{ setRoute('admin.car.model.store') }}"
                    enctype="multipart/form-data">
                    @csrf
                    <div class="row mb-10-none">
                        <div class="col-xl-12 col-lg-12 form-group">
                            <label>{{ __('Car Type') }}<span>*</span></label>
                            <select name="car_type_id" class="form--control select2-auto-tokenize">
                                <option selected disabled>{{ __('Select Type') }}</option>
                                @foreach ($car_types ?? [] as $type)
                                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-xl-12 col-lg-12 form-group">
                            @include('admin.components.form.input', [
                                'label' => __('Model Name') . '<span>*</span>',
                                'name' => 'name',
                                'value' => old('name'),
                            ])
                        </div>
                        <div class="col-xl-12 col-lg-12 form-group">
                            <label>{{ __('Image') }}</label>
                            @include('admin.components.form.input-file', [
                                'label' => false,
                                'class' => 'file-holder',
                                'name' => 'image',
                            ])
                        </div>
                        <div
                            class="col-xl-12 col-lg-12 form-group d-flex align-items-center justify-content-between mt-4">
                            <button type="button" class="btn btn--danger modal-close">{{ __('Cancel') }}</button>
                            <button type="submit" class="btn btn--base">{{ __('Add') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('script')
        <script>
            $(document).ready(function() {
                openModalWhenError('car-model-add', '#car-model-add');
            });
        </script>
    @endpush
@endif
