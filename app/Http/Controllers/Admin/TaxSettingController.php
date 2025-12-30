<?php

namespace App\Http\Controllers\Admin;

use Exception;
use Illuminate\Http\Request;
use App\Http\Helpers\Response;
use App\Models\Admin\TaxSetting;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class TaxSettingController extends Controller
{
    /**
     * Display the tax settings page.
     */
    public function index()
    {
        $page_title = __("Tax Settings");
        $tax_setting = TaxSetting::first();

        // Create default if not exists
        if (!$tax_setting) {
            $tax_setting = TaxSetting::create([
                'name' => 'VAT',
                'percentage' => 15.00,
                'status' => true,
                'last_edit_by' => auth()->user()->id,
            ]);
        }

        return view('admin.sections.tax-settings.index', compact(
            'page_title',
            'tax_setting'
        ));
    }

    /**
     * Update the tax settings.
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'percentage' => 'required|numeric|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $validated = $validator->validate();
        $validated['last_edit_by'] = auth()->user()->id;

        try {
            $tax_setting = TaxSetting::first();
            if ($tax_setting) {
                $tax_setting->update($validated);
            } else {
                $validated['status'] = true;
                TaxSetting::create($validated);
            }
        } catch (Exception $e) {
            return back()->with(['error' => [__('Something went wrong! Please try again.')]]);
        }

        return back()->with(['success' => [__('Tax settings updated successfully!')]]);
    }

    /**
     * Update tax status.
     */
    public function statusUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'data_target' => 'required|numeric|exists:tax_settings,id',
            'status' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return Response::error(['error' => $validator->errors()]);
        }

        $validated = $validator->validate();
        $tax_setting = TaxSetting::find($validated['data_target']);

        try {
            $tax_setting->update([
                'status' => ($validated['status']) ? false : true,
                'last_edit_by' => auth()->user()->id,
            ]);
        } catch (Exception $e) {
            return Response::error(['error' => [__('Something went wrong! Please try again.')]], null, 500);
        }

        return Response::success(['success' => [__('Tax status updated successfully!')]]);
    }
}
