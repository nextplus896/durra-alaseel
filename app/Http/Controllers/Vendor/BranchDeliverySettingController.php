<?php

namespace App\Http\Controllers\Vendor;

use Exception;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Admin\Branch;
use App\Models\BranchDeliverySetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BranchDeliverySettingController extends Controller
{
    /**
     * Display the branch delivery settings page
     */
    public function index()
    {
        $page_title = __("Branch Settings");
        $vendorId = auth()->guard('vendor')->user()->id;

        // Get all active branches
        $branches = Branch::where('status', true)->orderBy('name', 'ASC')->get();

        // Get existing settings for this vendor
        $existingSettings = BranchDeliverySetting::where('vendor_id', $vendorId)
            ->get()
            ->keyBy('branch_id');

        // Merge branches with their settings
        $branchSettings = $branches->map(function ($branch) use ($existingSettings) {
            $setting = $existingSettings->get($branch->id);
            return (object) [
                'branch' => $branch,
                'delivery_available' => $setting->delivery_available ?? true,
                'delivery_price' => $setting->delivery_price ?? 0,
                'vendor_price' => $setting->vendor_price ?? 0,
                'has_setting' => $setting !== null,
            ];
        });

        return view('vendor-end.sections.branch-settings.index', compact(
            'page_title',
            'branchSettings',
        ));
    }

    /**
     * Store or update branch delivery settings
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'settings' => 'required|array',
            'settings.*.branch_id' => 'required|integer|exists:branches,id',
            'settings.*.delivery_available' => 'nullable',
            'settings.*.delivery_price' => 'nullable|numeric|min:0',
            'settings.*.vendor_price' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $vendorId = auth()->guard('vendor')->user()->id;
        $settings = $request->input('settings', []);

        DB::beginTransaction();
        try {
            foreach ($settings as $setting) {
                BranchDeliverySetting::updateOrCreate(
                    [
                        'branch_id' => $setting['branch_id'],
                        'vendor_id' => $vendorId,
                    ],
                    [
                        'delivery_available' => isset($setting['delivery_available']) ? true : false,
                        'delivery_price' => $setting['delivery_price'] ?? 0,
                        'vendor_price' => $setting['vendor_price'] ?? 0,
                    ]
                );
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            report($e);
            if (config('app.debug')) {
                return back()->with(['error' => [$e->getMessage()]]);
            } else {
                return back()->with(['error' => [__('Something went wrong! Please try again.')]]);
            }
        }

        return back()->with(['success' => [__('Branch settings updated successfully!')]]);
    }

    /**
     * Update a single branch setting via AJAX
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'branch_id' => 'required|integer|exists:branches,id',
            'delivery_available' => 'nullable|boolean',
            'delivery_price' => 'nullable|numeric|min:0',
            'vendor_price' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $vendorId = auth()->guard('vendor')->user()->id;

        try {
            BranchDeliverySetting::updateOrCreate(
                [
                    'branch_id' => $request->branch_id,
                    'vendor_id' => $vendorId,
                ],
                [
                    'delivery_available' => $request->delivery_available ?? false,
                    'delivery_price' => $request->delivery_price ?? 0,
                    'vendor_price' => $request->vendor_price ?? 0,
                ]
            );
        } catch (Exception $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => __('Something went wrong! Please try again.'),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => __('Setting updated successfully!'),
        ]);
    }
}
