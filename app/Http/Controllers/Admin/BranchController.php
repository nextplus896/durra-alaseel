<?php

namespace App\Http\Controllers\Admin;

use Exception;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Helpers\Response;
use App\Models\Admin\Branch;
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
            'name' => 'required|string|max:255|unique:branches,name',
            'address' => 'nullable|string|max:500',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'service_radius_km' => 'required|numeric|min:0.1|max:500',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $validated = $validator->validate();

        $validated['slug'] = Str::slug($validated['name']);
        $validated['status'] = true;
        $validated['last_edit_by'] = auth()->user()->id;

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
            'name' => 'required|string|max:255|unique:branches,name,' . $id,
            'address' => 'nullable|string|max:500',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'service_radius_km' => 'required|numeric|min:0.1|max:500',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $validated = $validator->validate();
        $validated['slug'] = Str::slug($validated['name']);
        $validated['last_edit_by'] = auth()->user()->id;

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
}
