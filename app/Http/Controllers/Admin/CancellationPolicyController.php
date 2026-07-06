<?php

namespace App\Http\Controllers\Admin;

use Exception;
use App\Constants\CancellationPolicyConst;
use App\Http\Controllers\Controller;
use App\Http\Helpers\Response;
use App\Http\Requests\Admin\StoreCancellationPolicyRequest;
use App\Models\Admin\CancellationPolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Manages the global cancellation policy from the admin panel.
 *
 * Single-record architecture — there is always exactly one row.
 * If none exists (e.g. fresh install before migration seed ran),
 * index() will auto-create a default record.
 */
class CancellationPolicyController extends Controller
{
    /**
     * Display the cancellation policy configuration page.
     */
    public function index()
    {
        $page_title = __('Cancellation Policy');

        // Auto-create default if the migration seed did not run
        $policy = CancellationPolicy::first() ?? $this->createDefault();

        $deduction_type_labels = CancellationPolicyConst::DEDUCTION_TYPE_LABELS;
        $fee_type_labels       = CancellationPolicyConst::FEE_TYPE_LABELS;

        return view('admin.sections.cancellation-policy.index', compact(
            'page_title',
            'policy',
            'deduction_type_labels',
            'fee_type_labels',
        ));
    }

    /**
     * Update the global cancellation policy.
     */
    public function update(StoreCancellationPolicyRequest $request)
    {
        $policy = CancellationPolicy::first();

        if (!$policy) {
            return back()->with(['error' => [__('Cancellation policy not found!')]]);
        }

        try {
            $policy->update([
                'cancellation_window_hours' => $request->integer('cancellation_window_hours'),
                'deduction_type'            => $request->input('deduction_type'),
                'deduction_value'           => $request->input('deduction_value', 0),
                'service_fee_type'          => $request->input('service_fee_type'),
                'service_fee_value'         => $request->input('service_fee_value', 0),
                'last_edit_by'              => auth()->guard('admin')->id(),
            ]);
        } catch (Exception $e) {
            return back()->with(['error' => [__('Something went wrong! Please try again.')]]);
        }

        return back()->with(['success' => [__('Cancellation policy updated successfully!')]]);
    }

    /**
     * Toggle the active/inactive status via AJAX.
     *
     * Expected request body: { data_target: <policy_id>, status: <current_bool> }
     * Returns JSON — consumed by the admin switcherAjax() JS helper.
     */
    public function statusUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'data_target' => 'required|numeric|exists:cancellation_policies,id',
            'status'      => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return Response::error(['error' => $validator->errors()]);
        }

        $validated = $validator->validated();
        $policy    = CancellationPolicy::find($validated['data_target']);

        try {
            $policy->update([
                'is_active'    => !$validated['status'],
                'last_edit_by' => auth()->guard('admin')->id(),
            ]);
        } catch (Exception $e) {
            return Response::error([__('Something went wrong! Please try again.')], null, 500);
        }

        return Response::success([__('Cancellation policy status updated successfully!')]);
    }

    // -------------------------------------------------------------------------
    // Private Helpers
    // -------------------------------------------------------------------------

    /**
     * Create the default global policy record.
     * Mirrors the migration seed values.
     */
    private function createDefault(): CancellationPolicy
    {
        return CancellationPolicy::create([
            'cancellation_window_hours' => 4,
            'deduction_type'            => CancellationPolicyConst::DEDUCTION_DAY,
            'deduction_value'           => 1.00,
            'service_fee_type'          => CancellationPolicyConst::FEE_PERCENTAGE,
            'service_fee_value'         => 10.00,
            'is_active'                 => true,
        ]);
    }
}
