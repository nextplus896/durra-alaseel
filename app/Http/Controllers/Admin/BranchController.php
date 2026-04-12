<?php

namespace App\Http\Controllers\Admin;

use Exception;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Helpers\Response;
use App\Models\Admin\Branch;
use App\Models\BranchWorkingHour;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class BranchController extends Controller
{
    /**
     * Display a listing of all branches.
     */
    public function index()
    {
        $page_title = __("Branch Management");
        $branches = Branch::orderByDesc("id")->paginate(15);

        return view('admin.sections.branch.index', compact(
            'page_title',
            'branches'
        ));
    }

    /**
     * Show the form for creating a new branch.
     */
    public function create()
    {
        $page_title = __("Create New Branch");

        return view('admin.sections.branch.create', compact(
            'page_title'
        ));
    }

    /**
     * Store a newly created branch.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'               => 'required|string|max:255|unique:branches,name',
            'address'            => 'nullable|string|max:500',
            'phone'              => 'nullable|string|max:20',
            'email'              => 'nullable|email|max:255',
            'latitude'           => 'required|numeric|between:-90,90',
            'longitude'          => 'required|numeric|between:-180,180',
            'service_radius_km'  => 'required|numeric|min:0.1|max:500',
            'delivery_enabled'   => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $validated = $validator->validate();

        $validated['slug']             = Str::slug($validated['name']);
        $validated['status']           = true;
        $validated['last_edit_by']     = auth()->user()->id;
        $validated['delivery_enabled'] = $request->boolean('delivery_enabled');

        try {
            Branch::create($validated);
        } catch (Exception $e) {
            return back()->with(['error' => [__('Something went wrong! Please try again.')]]);
        }

        return redirect()->route('admin.branch.index')->with(['success' => [__('Branch created successfully!')]]);
    }

    /**
     * Show the form for editing the specified branch.
     */
    public function edit($id)
    {
        $branch = Branch::find($id);
        if (!$branch) {
            return back()->with(['error' => [__('Branch not found')]]);
        }

        $page_title = __("Edit Branch");

        return view('admin.sections.branch.edit', compact(
            'page_title',
            'branch'
        ));
    }

    /**
     * Update the specified branch.
     */
    public function update(Request $request, $id)
    {
        $branch = Branch::find($id);
        if (!$branch) {
            return back()->with(['error' => [__('Branch not found')]]);
        }

        $validator = Validator::make($request->all(), [
            'name'               => 'required|string|max:255|unique:branches,name,' . $id,
            'address'            => 'nullable|string|max:500',
            'phone'              => 'nullable|string|max:20',
            'email'              => 'nullable|email|max:255',
            'latitude'           => 'required|numeric|between:-90,90',
            'longitude'          => 'required|numeric|between:-180,180',
            'service_radius_km'  => 'required|numeric|min:0.1|max:500',
            'delivery_enabled'   => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $validated = $validator->validate();
        $validated['slug']             = Str::slug($validated['name']);
        $validated['last_edit_by']     = auth()->user()->id;
        $validated['delivery_enabled'] = $request->boolean('delivery_enabled');

        try {
            $branch->update($validated);
        } catch (Exception $e) {
            return back()->with(['error' => [__('Something went wrong! Please try again.')]]);
        }

        return redirect()->route('admin.branch.index')->with(['success' => [__('Branch updated successfully!')]]);
    }

    /**
     * Update branch status.
     */
    public function statusUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'data_target' => 'required|numeric|exists:branches,id',
            'status' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return Response::error(['error' => $validator->errors()]);
        }

        $validated = $validator->validate();
        $branch = Branch::find($validated['data_target']);

        try {
            $branch->update([
                'status' => ($validated['status']) ? false : true,
                'last_edit_by' => auth()->user()->id,
            ]);
        } catch (Exception $e) {
            return Response::error(['error' => [__('Something went wrong! Please try again.')]], null, 500);
        }

        return Response::success(['success' => [__('Branch status updated successfully!')]]);
    }

    /**
     * Delete the specified branch.
     */
    public function delete(Request $request)
    {
        $request->validate([
            'target' => 'required|numeric|exists:branches,id',
        ]);

        $branch = Branch::find($request->target);

        try {
            $branch->delete();
        } catch (Exception $e) {
            return back()->with(['error' => [__('Something went wrong! Please try again.')]]);
        }

        return back()->with(['success' => [__('Branch deleted successfully!')]]);
    }

    /**
     * View delivery settings for a branch (read-only for admin).
     */
    public function deliverySettings($id)
    {
        $branch = Branch::with('deliverySettings.vendor')->find($id);
        if (!$branch) {
            return back()->with(['error' => [__('Branch not found')]]);
        }

        $page_title = __("Delivery Settings - :branch", ['branch' => $branch->name]);

        return view('admin.sections.branch.delivery-settings', compact(
            'page_title',
            'branch'
        ));
    }

    /**
     * Display working hours management page for a branch.
     */
    public function workingHours($id)
    {
        $branch = Branch::with('workingHours')->find($id);
        if (!$branch) {
            return back()->with(['error' => [__('Branch not found')]]);
        }

        $page_title = __('Working Hours - :branch', ['branch' => $branch->name]);

        // Group working hours by day in Saudi order
        $saudiOrder = BranchWorkingHour::SAUDI_DAY_ORDER;
        $dayNames = BranchWorkingHour::DAY_NAMES_AR;
        $dayNamesEn = BranchWorkingHour::DAY_NAMES_EN;

        return view('admin.sections.branch.working-hours', compact(
            'page_title',
            'branch',
            'saudiOrder',
            'dayNames',
            'dayNamesEn'
        ));
    }

    /**
     * Store a new working hour time slot for a branch.
     */
    public function storeWorkingHour(Request $request, $id)
    {
        $branch = Branch::find($id);
        if (!$branch) {
            return back()->with(['error' => [__('Branch not found')]]);
        }

        $validator = Validator::make($request->all(), [
            'day_of_week' => 'required|integer|between:0,6',
            'open_time'   => 'required|date_format:H:i',
            'close_time'  => 'required|date_format:H:i|after:open_time',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Check for overlapping time slots on the same day for this branch
        $conflict = BranchWorkingHour::where('branch_id', $branch->id)
            ->where('day_of_week', $request->day_of_week)
            ->where(function ($query) use ($request) {
                $query->whereBetween('open_time', [$request->open_time, $request->close_time])
                    ->orWhereBetween('close_time', [$request->open_time, $request->close_time])
                    ->orWhere(function ($q) use ($request) {
                        $q->where('open_time', '<=', $request->open_time)
                            ->where('close_time', '>=', $request->close_time);
                    });
            })
            ->exists();

        if ($conflict) {
            return back()->with(['error' => [__('This time slot overlaps with an existing slot on the same day.')]])->withInput();
        }

        try {
            BranchWorkingHour::create([
                'branch_id'   => $branch->id,
                'day_of_week' => $request->day_of_week,
                'open_time'   => $request->open_time,
                'close_time'  => $request->close_time,
                'is_enabled'  => true,
            ]);
        } catch (Exception $e) {
            return back()->with(['error' => [__('Something went wrong! Please try again.')]]);
        }

        return back()->with(['success' => [__('Time slot added successfully!')]]);
    }

    /**
     * Toggle enable/disable for a working hour slot.
     */
    public function toggleWorkingHour(Request $request, $id)
    {
        $slot = BranchWorkingHour::find($id);
        if (!$slot) {
            return Response::error(['error' => [__('Time slot not found')]], null, 404);
        }

        try {
            $slot->update(['is_enabled' => !$slot->is_enabled]);
        } catch (Exception $e) {
            return Response::error(['error' => [__('Something went wrong!')]], null, 500);
        }

        return Response::success(['success' => [__('Time slot updated successfully!')]]);
    }

    /**
     * Delete a working hour time slot.
     */
    public function deleteWorkingHour(Request $request, $id)
    {
        $slot = BranchWorkingHour::find($id);
        if (!$slot) {
            return back()->with(['error' => [__('Time slot not found')]]);
        }

        try {
            $slot->delete();
        } catch (Exception $e) {
            return back()->with(['error' => [__('Something went wrong! Please try again.')]]);
        }

        return back()->with(['success' => [__('Time slot deleted successfully!')]]);
    }
}
