<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Helpers\Response;
use App\Models\Admin\Cars\CarArea;
use App\Models\Admin\Cars\CarType;
use App\Models\Admin\Cars\CarModel;
use App\Models\Admin\Language;
use App\Models\Vendor\Cars\Car;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Http\Helpers\Api\helpers;

class CarController extends Controller
{
    /**
     * List vendor's own cars with sorting and filtering
     *
     * Query Parameters:
     * - sort: price_asc, price_desc (default: price_desc)
     * - car_type_id: Filter by car type ID
     * - car_model_id: Filter by car model ID
     * - per_page: Items per page (default: 15)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function list(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sort'         => 'nullable|string|in:price_asc,price_desc',
            'car_type_id'  => 'nullable|integer|exists:car_types,id',
            'car_model_id' => 'nullable|integer|exists:car_models,id',
            'per_page'     => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return Response::error($validator->errors()->all(), [], 422);
        }

        $vendorId = auth()->guard('vendor_api')->user()->id;

        $query = Car::query()
            ->where('vendor_id', $vendorId)
            ->with(['type', 'carModel', 'area']);

        // Filter by car type
        if ($request->filled('car_type_id')) {
            $query->where('car_type_id', $request->car_type_id);
        }

        // Filter by car model
        if ($request->filled('car_model_id')) {
            $query->where('car_model_id', $request->car_model_id);
        }

        // Build available filters based on current query (before sorting and pagination)
        $filtersForTypes = (clone $query);
        $typeIds = $filtersForTypes->select('car_type_id')->distinct()->pluck('car_type_id')->filter()->toArray();

        $filtersForModels = (clone $query);
        $modelIds = $filtersForModels->select('car_model_id')->distinct()->pluck('car_model_id')->filter()->toArray();

        // Sorting by price
        $sort = $request->input('sort', 'price_desc');
        if ($sort === 'price_asc') {
            $query->orderBy('fees', 'asc');
        } else {
            $query->orderBy('fees', 'desc');
        }

        // Pagination
        $perPage = $request->input('per_page', 15);
        $cars = $query->paginate($perPage);

        // Transform data for response
        $data = [
            'available_filters' => [
                'car_types' => CarType::whereIn('id', $typeIds)->select('id', 'name', 'slug')->get(),
                'car_models' => CarModel::whereIn('id', $modelIds)->select('id', 'name', 'car_type_id')->get(),
            ],
            'cars' => $cars->getCollection()->map(function ($car) {
                $feesRaw = (float) $car->fees;
                $formattedFees = ((int) $feesRaw == $feesRaw) ? (string) ((int) $feesRaw) : number_format($feesRaw, 2, '.', '');
                return [
                    'id'           => $car->id,
                    'vendor_id'    => $car->vendor_id,
                    'car_title'    => $car->car_title,
                    'car_model'    => $car->car_model,
                    'car_number'   => $car->car_number,
                    'seat'         => $car->seat,
                    'year'         => $car->year,
                    'fees'         => $formattedFees,
                    'price'        => $formattedFees,
                    'image'        => $car->image,
                    'image_url'    => $car->image_url,
                    'status'       => $car->status,
                    'approval'     => $car->approval,
                    'car_type' => $car->type ? [
                        'id'   => $car->type->id,
                        'name' => $car->type->name,
                        'slug' => $car->type->slug,
                    ] : null,
                    'car_model_info' => $car->carModel ? [
                        'id'        => $car->carModel->id,
                        'name'      => $car->carModel->name,
                        'image_url' => $car->carModel->image_url,
                    ] : null,
                    'area' => $car->area ? [
                        'id'   => $car->area->id,
                        'name' => $car->area->name,
                    ] : null,
                    'created_at' => $car->created_at?->toIso8601String(),
                ];
            }),
            'pagination' => [
                'total'        => $cars->total(),
                'per_page'     => $cars->perPage(),
                'current_page' => $cars->currentPage(),
                'last_page'    => $cars->lastPage(),
                'from'         => $cars->firstItem(),
                'to'           => $cars->lastItem(),
            ],
            'data_path' => [
                'base_url'   => url('/'),
                'image_path' => files_asset_path_basename('site-section'),
            ],
        ];

        return Response::success([__('Cars fetched successfully!')], $data, 200);
    }

    public function carArea()
    {
        $car_area = CarArea::all();
        $message = [__('Car Area Fetched Successfully!')];
        return Response::success($message, $car_area);
    }

    public function carType()
    {
        $car_type = CarType::all();
        $message = [__('Car Type Fetched Successfully!')];
        return Response::success($message, $car_type);
    }

    public function getAreaTypes(Request $request)
    {
        $validator    = Validator::make($request->all(), [
            'area'  => 'required|integer',
        ]);
        if ($validator->fails()) {
            return Response::error($validator->errors()->all());
        }
        $area = CarArea::with(['types' => function ($type) {
            $type->with(['type' => function ($car_type) {
                $car_type->where('status', true);
            }]);
        }])->find($request->area);
        if (!$area) return Response::error([__('Area Not Found')], 404);

        return Response::success([__('Types fetch successfully')], ['area' => $area], 200);
    }

    /**
     * Method for store car
     * @param string $slug
     * @param \Illuminate\Http\Request  $request
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'area'        => 'required',
            'type'        => 'required',
            'car_model'   => 'nullable|string',
            'car_number'  => 'nullable|string|max:100',
            'seat'        => 'required|numeric',
            'year'        => 'required|integer|min:1900|max:' . (date('Y') + 1),
            'experience'  => 'nullable|numeric',
            'fees'        => 'required|numeric',
            'daily_insurance'      => 'required|numeric|min:0',
            'deductible_insurance' => 'required|numeric|min:0',
            'image'       => 'required|image|mimes:png,jpg,jpeg,svg,webp',
        ]);

        $basic_field_name = [
            'car_title'       => "required",
        ];

        $car_title = $this->contentValidate($request, $basic_field_name);


        if ($validator->fails()) {
            return Helpers::onlyValidation($validator->errors()->all());
        }

        $validated                   = $validator->validate();
        $validated['vendor_id']      = auth()->guard('vendor_api')->user()->id;
        $validated['slug']           = Str::uuid();
        $validated['car_area_id']    = $validated['area'];
        $validated['car_type_id']    = $validated['type'];
        $validated['car_title']      = $car_title;
        $validated['approval']       = 1; // Auto-approve vendor cars

        if (Car::where('car_number', $validated['car_number'])->exists()) {
            return Response::error([__("Car already exists!")]);
        }

        if ($request->hasFile("image")) {

            $image = upload_file($validated['image'], 'junk-files', $request->image);
            $upload_image = upload_files_from_path_dynamic([$image['dev_path']], 'site-section');
            $validated['image'] = $upload_image;
        }
        $validated = Arr::except($validated, ['area', 'type']);
        try {
            $car = Car::create($validated);
        } catch (Exception $e) {
            return Response::error([__("Something went wrong! Please try again.")], []);
        }
        return Response::success([__("Car Created Successfully!")], []);
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
            $errors = $validator->errors()->all();
            return Response::error($errors);
        }

        $validated = $validator->validate();
        $cars = Car::find($validated['data_target']);
        try {
            $cars->update([
                'status'   => ($validated['status']) ? false : true,
            ]);
        } catch (Exception $e) {
            $errors = [__('Something went wrong! Please try again.')];
            return Response::error($errors, null, 500);
        }
        $success = [__('Car status updated successfully!')];
        return Response::success($success);
    }
    /**
     * Method for show car details
     * @param string $id
     */
    public function details(Request $request)
    {
        $cars  = Car::find($request->id);

        if (!$cars) return Response::error([__("Car Does not exists")], null, 500);

        return Response::success([__("Car Fetch Successfully!")], [$cars]);
    }
    /**
     * Method for update car
     * @param string $slug
     * @param \Illuminate\Http\Request  $request
     */
    public function update(Request $request)
    {
        $car = Car::find($request->id);
        $validator = Validator::make($request->all(), [
            'area'        => 'required',
            'type'        => 'required',
            'car_model'   => 'nullable|string',
            'car_number'  => 'nullable|string|max:100',
            'seat'        => 'required|numeric',
            'year'        => 'required|integer|min:1900|max:' . (date('Y') + 1),
            'experience'  => 'nullable|numeric',
            'fees'        => 'required|numeric',
            'daily_insurance'      => 'required|numeric|min:0',
            'deductible_insurance' => 'required|numeric|min:0',
            'image'       => 'nullable|image|mimes:png,jpg,jpeg,svg,webp',
        ]);

        $basic_field_name = [
            'car_title'       => "required",
        ];

        $car_title = $this->contentValidate($request, $basic_field_name);

        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            return Response::error($errors);
        };

        $validated                 = $validator->validate();
        $validated['vendor_id']    = auth()->guard('vendor_api')->user()->id;
        $validated['slug']         = Str::uuid();
        $validated['car_area_id']  = $validated['area'];
        $validated['car_type_id']  = $validated['type'];
        $validated['car_title']    = $car_title;

        if ($request->hasFile('image')) {
            $image = upload_file($validated['image'], 'junk-files', $request->image);
            $upload_image = upload_files_from_path_dynamic([$image['dev_path']], 'site-section');
            $validated['image'] = $upload_image;
        }

        $validated = Arr::except($validated, ['area', 'type']);

        try {
            $car->update($validated);
        } catch (Exception $e) {
            $errors = [__('Something went wrong! Please try again.')];
            return Response::error($errors, null, 500);
        }

        return Response::success([__("Car Updated Successfully!")]);
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
            $errors = [__('Something went wrong! Please try again.')];
            return Response::error($errors, null, 500);
        }

        return Response::success([__("Car Deleted Successfully!")]);
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
