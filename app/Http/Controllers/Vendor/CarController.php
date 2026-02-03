<?php

namespace App\Http\Controllers\Vendor;

use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Helpers\Response;
use App\Models\Vendor\Cars\Car;
use App\Models\Admin\Cars\CarArea;
use App\Models\Admin\Cars\CarType;
use App\Models\Admin\Cars\CarModel;
use App\Models\Admin\Branch;
use App\Models\BranchDeliverySetting;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Admin\Language;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use ParagonIE\Sodium\Compat;

class CarController extends Controller
{
    public function index()
    {
        $page_title = __("My Cars");
        $cars = Car::where('vendor_id', auth()->guard('vendor')->user()->id)->paginate(6);

        return view('vendor-end.sections.my-car.index', compact('page_title', 'cars'));
    }
    /**
     * Method for show car create page
     * @param string $slug
     * @param \Illuminate\Http\Request  $request
     */
    public function create()
    {
        $page_title = __('Car Create');
        $car_type = CarType::orderBy('name', 'ASC')->get();
        $branches = Branch::where('status', true)->orderBy('name', 'ASC')->get();
        $languages = Language::get();
        return view('vendor-end.sections.my-car.add', compact(
            'page_title',
            'car_type',
            'branches',
            'languages',
        ));
    }
    /**
     * Method for get all car models based on type
     * @param \Illuminate\Http\Request  $request
     */
    public function getCarModels(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type_id'  => 'required|integer',
        ]);
        if ($validator->fails()) {
            return Response::error($validator->errors()->all());
        }

        $models = CarModel::where('car_type_id', $request->type_id)
            ->where('status', true)
            ->orderBy('name', 'ASC')
            ->get();

        return Response::success([__('Data fetch successfully')], ['models' => $models], 200);
    }
    /**
     * Method for store car
     * @param string $slug
     * @param \Illuminate\Http\Request  $request
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type'            => 'required|integer|exists:car_types,id',
            'car_model_id'    => 'required|integer|exists:car_models,id',
            'branch_id'       => 'required|integer|exists:branches,id',
            'seat'            => 'required|numeric|min:1|max:100',
            'year'            => 'required|integer|min:1900|max:' . (date('Y') + 1),
            'fees'            => 'required|numeric|min:0', // Keep fees for backward compatibility
            'price_per_day'   => 'required|numeric|min:0',
            'price_per_week'  => 'required|numeric|min:0',
            'price_per_month' => 'required|numeric|min:0',
            'delivery_available' => 'nullable|boolean',
            'allowance_km'    => 'nullable|numeric|min:0',
            'allowance_price_per_km' => 'nullable|numeric|min:0',
        ]);

        $basic_field_name = [
            'car_title'       => "required",
        ];

        $car_title = $this->contentValidate($request, $basic_field_name);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput($request->all());
        }

        $validated                   = $validator->validate();

        // Validate that car_model belongs to selected car_type
        $carModel = CarModel::where('id', $validated['car_model_id'])
            ->where('car_type_id', $validated['type'])
            ->where('status', true)
            ->first();

        if (!$carModel) {
            return back()->withErrors(['car_model_id' => __('Selected car model does not belong to the selected vehicle type or is inactive.')])->withInput();
        }

        $validated['vendor_id']      = auth()->guard('vendor')->user()->id;
        $validated['slug']           = Str::uuid();
        $validated['car_type_id']    = $validated['type'];
        $validated['car_title']      = $car_title;
        $validated['approval']       = 1; // Auto-approve vendor cars

        // Use image from the selected CarModel instead of file upload
        $validated['image'] = $carModel->image;

        $validated = Arr::except($validated, ['type', 'delivery_available']);

        DB::beginTransaction();
        try {
            $car = Car::create($validated);

            // Create or update delivery settings for this branch-vendor combination
            BranchDeliverySetting::updateOrCreate(
                [
                    'branch_id' => $request->branch_id,
                    'vendor_id' => auth()->guard('vendor')->user()->id,
                ],
                [
                    'delivery_available' => $request->delivery_available ?? false,
                    // delivery_price is managed in settings, not here
                    'vendor_price' => $request->vendor_price ?? 0,
                ]
            );

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            report($e);
            if (config('app.debug')) {
                return back()->with(['error' => [$e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine()]])->withInput();
            } else {
                return back()->with(['error' => [__('Something went wrong! Please try again.')]]);
            }
        }
        return redirect()->route('vendor.car.index')->with(['success' => [__('Car Created Successfully!')]]);
    }
    /**
     * Method for update car status
     * @param string $slug
     * @param \Illuminate\Http\Request  $request
     */
    public function statusUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'data_target'  => 'required|numeric|exists:cars,id',
            'status'       => 'required|boolean',
        ]);

        if ($validator->fails()) {
            $errors = ['error' => $validator->errors()];
            return Response::error($errors);
        }

        $validated = $validator->validate();
        $cars = Car::find($validated['data_target']);
        try {
            $cars->update([
                'status'   => ($validated['status']) ? false : true,
            ]);
        } catch (Exception $e) {
            $errors = ['error' => [__('Something went wrong! Please try again.')]];
            return Response::error($errors, null, 500);
        }
        $success = ['success' => [__('Car status updated successfully!')]];
        return Response::success($success);
    }
    /**
     * Method for show car edit page
     * @param string $slug
     * @param \Illuminate\Http\Request  $request
     */
    public function edit($id)
    {
        $page_title = __('Car Edit');
        $cars  = Car::find($id);
        if (!$cars) return back()->with(['error' => [__('Car Does not exists')]]);

        $car_type = CarType::where('status', true)->get();
        $branches = Branch::where('status', true)->orderBy('name', 'ASC')->get();
        $languages = Language::get();

        // Get existing delivery settings for this branch-vendor combination
        $delivery_setting = null;
        if ($cars->branch_id) {
            $delivery_setting = BranchDeliverySetting::where('branch_id', $cars->branch_id)
                ->where('vendor_id', auth()->guard('vendor')->user()->id)
                ->first();
        }

        return view('vendor-end.sections.my-car.edit', compact(
            'page_title',
            'cars',
            'car_type',
            'branches',
            'languages',
            'delivery_setting',
        ));
    }
    /**
     * Method for update car
     * @param string $slug
     * @param \Illuminate\Http\Request  $request
     */
    public function update(Request $request, $id)
    {
        $car = Car::where('id', $id)
            ->where('vendor_id', auth()->guard('vendor')->user()->id)
            ->first();

        if (!$car) {
            return back()->with(['error' => [__('Car not found or access denied.')]]);
        }

        $validator = Validator::make($request->all(), [
            'type'            => 'required|integer|exists:car_types,id',
            'car_model_id'    => 'required|integer|exists:car_models,id',
            'branch_id'       => 'required|integer|exists:branches,id',
            'seat'            => 'required|numeric|min:1|max:100',
            'year'            => 'required|integer|min:1900|max:' . (date('Y') + 1),
            'fees'            => 'required|numeric|min:0', // Keep fees for backward compatibility
            'price_per_day'   => 'required|numeric|min:0',
            'price_per_week'  => 'required|numeric|min:0',
            'price_per_month' => 'required|numeric|min:0',
            'delivery_available' => 'nullable|boolean',
            'allowance_km'    => 'nullable|numeric|min:0',
            'allowance_price_per_km' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $basic_field_name = [
            'car_title'       => "required",
        ];

        $car_title = $this->contentValidate($request, $basic_field_name);

        $validated                 = $validator->validate();

        // Validate that car_model belongs to selected car_type
        $carModel = CarModel::where('id', $validated['car_model_id'])
            ->where('car_type_id', $validated['type'])
            ->where('status', true)
            ->first();

        if (!$carModel) {
            return back()->withErrors(['car_model_id' => __('Selected car model does not belong to the selected vehicle type or is inactive.')])->withInput();
        }

        $validated['vendor_id']    = auth()->guard('vendor')->user()->id;
        $validated['car_type_id']  = $validated['type'];
        $validated['car_title']    = $car_title;

        // Use image from the selected CarModel
        $validated['image'] = $carModel->image;

        $validated = Arr::except($validated, ['type', 'delivery_available']);

        DB::beginTransaction();
        try {
            $car->update($validated);

            // Create or update delivery settings for this branch-vendor combination
            BranchDeliverySetting::updateOrCreate(
                [
                    'branch_id' => $request->branch_id,
                    'vendor_id' => auth()->guard('vendor')->user()->id,
                ],
                [
                    'delivery_available' => $request->delivery_available ?? false,
                    // delivery_price is managed in settings, not here
                    'vendor_price' => $request->vendor_price ?? 0,
                ]
            );

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            report($e);
            if (config('app.debug')) {
                return back()->with(['error' => [$e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine()]])->withInput();
            } else {
                return back()->with(['error' => [__('Something went wrong! Please try again.')]]);
            }
        }
        return redirect()->route('vendor.car.index')->with(['success' => [__('Car Updated Successfully!')]]);
    }
    /**
     * Method for delete car
     * @param string $slug
     * @param \Illuminate\Http\Request  $request
     */
    public function delete(request $request)
    {
        $request->validate([
            'target'    => 'required|numeric',
        ]);
        $cars = Car::find($request->target);
        try {
            delete_file(get_files_path('site-section') . '/' . $cars->image);
            $cars->delete();
        } catch (Exception $e) {
            report($e);
            if (config('app.debug')) {
                return back()->with(['error' => [$e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine()]])->withInput();
            } else {
                return back()->with(['error' => [__('Something went wrong! Please try again.')]]);
            }
        }
        return back()->with(['success'  => [__('Car Deleted Successfully!')]]);
    }
    /**
     * Method for image validate
     * @param string $slug
     * @param \Illuminate\Http\Request  $request
     */
    public function imageValidate($request, $input_name, $old_image = null)
    {
        if ($request->hasFile($input_name)) {
            $image_validated = Validator::make($request->only($input_name), [
                $input_name => "image|mimes:png,jpg,webp,jpeg,svg",
            ])->validate();
            $image = get_files_from_fileholder($request, $input_name);
            $upload = upload_files_from_path_dynamic($image, 'site-section', $old_image);
            return $upload;
        }
        return false;
    }


    /**
     * Method for validate request data and re-decorate language wise data
     * @param object $request
     * @param array $basic_field_name
     * @return array $language_wise_data
     */
    public function contentValidate($request, $basic_field_name, $modal = null)
    {
        $languages = Language::get();

        $current_local = get_default_language_code();
        $validation_rules = [];
        $language_wise_data = [];
        foreach ($request->all() as $input_name => $input_value) {
            foreach ($languages as $language) {
                $input_name_check = explode("_", $input_name);
                $input_lang_code = array_shift($input_name_check);
                $input_name_check = implode("_", $input_name_check);
                if ($input_lang_code == $language['code']) {
                    if (array_key_exists($input_name_check, $basic_field_name)) {
                        $langCode = $language['code'];
                        if ($current_local == $langCode) {
                            $validation_rules[$input_name] = $basic_field_name[$input_name_check];
                        } else {
                            $validation_rules[$input_name] = str_replace("required", "nullable", $basic_field_name[$input_name_check]);
                        }
                        $language_wise_data[$langCode][$input_name_check] = $input_value;
                    }
                    break;
                }
            }
        }
        if ($modal == null) {
            $validated = Validator::make($request->all(), $validation_rules)->validate();
        } else {
            $validator = Validator::make($request->all(), $validation_rules);
            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput()->with("modal", $modal);
            }
            $validated = $validator->validate();
        }

        return $language_wise_data;
    }
}
