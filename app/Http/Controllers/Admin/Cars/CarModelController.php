<?php

namespace App\Http\Controllers\Admin\Cars;

use App\Http\Controllers\Controller;
use App\Http\Helpers\Response;
use App\Models\Admin\Cars\CarModel;
use App\Models\Admin\Cars\CarType;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CarModelController extends Controller
{
    public function index()
    {
        $page_title = __("Car Models");
        $car_models = CarModel::with('carType')->orderByDesc("id")->get();
        $car_types = CarType::where('status', true)->orderBy('name', 'ASC')->get();

        return view('admin.sections.cars.car-model.index', compact(
            'page_title',
            'car_models',
            'car_types',
        ));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'car_type_id' => 'required|exists:car_types,id',
            'name' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:png,jpg,jpeg,svg,webp',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput()->with('modal', 'car-model-add');
        }

        $validated = $validator->validate();
        $validated['status'] = true;

        if ($request->hasFile('image')) {
            $image = get_files_from_fileholder($request, 'image');
            $upload = upload_files_from_path_dynamic($image, 'car-models');
            $validated['image'] = $upload;
        }

        try {
            CarModel::create($validated);
        } catch (Exception $e) {
            return back()->with(['error' => [__('Something went wrong! Please try again.')]]);
        }

        return back()->with(['success' => [__('Car Model added successfully!')]]);
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'target' => 'required|exists:car_models,id',
            'car_type_id' => 'required|exists:car_types,id',
            'name' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:png,jpg,jpeg,svg,webp',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput()->with('modal', 'car-model-edit');
        }

        $validated = $validator->validate();
        $carModel = CarModel::find($validated['target']);

        if (!$carModel) {
            return back()->with(['error' => [__('Car Model not found!')]]);
        }

        $data = [
            'car_type_id' => $validated['car_type_id'],
            'name' => $validated['name'],
        ];

        if ($request->hasFile('image')) {
            $image = get_files_from_fileholder($request, 'image');
            $upload = upload_files_from_path_dynamic($image, 'car-models', $carModel->image);
            $data['image'] = $upload;
        }

        try {
            $carModel->update($data);
        } catch (Exception $e) {
            return back()->with(['error' => [__('Something went wrong! Please try again.')]]);
        }

        return back()->with(['success' => [__('Car Model updated successfully!')]]);
    }

    public function statusUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'data_target' => 'required|numeric|exists:car_models,id',
            'status' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            $errors = ['error' => $validator->errors()];
            return Response::error($errors);
        }

        $validated = $validator->validate();
        $carModel = CarModel::find($validated['data_target']);

        try {
            $carModel->update([
                'status' => ($validated['status']) ? false : true,
            ]);
        } catch (Exception $e) {
            $errors = ['error' => [__('Something went wrong! Please try again.')]];
            return Response::error($errors, null, 500);
        }

        $success = ['success' => [__('Car Model status updated successfully!')]];
        return Response::success($success);
    }

    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'target' => 'required|exists:car_models,id',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        $carModel = CarModel::find($request->target);

        try {
            if ($carModel->image) {
                delete_file(get_files_path('car-models') . '/' . $carModel->image);
            }
            $carModel->delete();
        } catch (Exception $e) {
            return back()->with(['error' => [__('Something went wrong! Please try again.')]]);
        }

        return back()->with(['success' => [__('Car Model deleted successfully!')]]);
    }
}
