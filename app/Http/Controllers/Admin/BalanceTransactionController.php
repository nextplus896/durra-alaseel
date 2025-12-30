<?php

namespace App\Http\Controllers\Admin;

use Exception;
use Illuminate\Http\Request;
use App\Models\BalanceTransaction;
use App\Http\Controllers\Controller;

class BalanceTransactionController extends Controller
{
    /**
     * Display all balance transactions.
     */
    public function index()
    {
        $page_title = __("Balance Transaction Logs");
        $transactions = BalanceTransaction::with(['user'])
            ->orderByDesc('id')
            ->paginate(20);

        return view('admin.sections.balance-transactions.index', compact(
            'page_title',
            'transactions'
        ));
    }

    /**
     * Display recharge transactions only.
     */
    public function recharges()
    {
        $page_title = __("Recharge Logs");
        $transactions = BalanceTransaction::with(['user'])
            ->where('type', BalanceTransaction::TYPE_RECHARGE)
            ->orderByDesc('id')
            ->paginate(20);

        return view('admin.sections.balance-transactions.index', compact(
            'page_title',
            'transactions'
        ));
    }

    /**
     * Display booking deduction transactions only.
     */
    public function deductions()
    {
        $page_title = __("Booking Deduction Logs");
        $transactions = BalanceTransaction::with(['user', 'booking'])
            ->where('type', BalanceTransaction::TYPE_BOOKING_DEDUCTION)
            ->orderByDesc('id')
            ->paginate(20);

        return view('admin.sections.balance-transactions.index', compact(
            'page_title',
            'transactions'
        ));
    }

    /**
     * Display refund transactions only.
     */
    public function refunds()
    {
        $page_title = __("Refund Logs");
        $transactions = BalanceTransaction::with(['user', 'booking'])
            ->where('type', BalanceTransaction::TYPE_REFUND)
            ->orderByDesc('id')
            ->paginate(20);

        return view('admin.sections.balance-transactions.index', compact(
            'page_title',
            'transactions'
        ));
    }

    /**
     * Search balance transactions.
     */
    public function search(Request $request)
    {
        $search = $request->search;
        $page_title = __("Search Results");

        $transactions = BalanceTransaction::with(['user', 'booking'])
            ->search($search)
            ->orderByDesc('id')
            ->paginate(20);

        return view('admin.sections.balance-transactions.index', compact(
            'page_title',
            'transactions'
        ));
    }

    /**
     * View transaction details.
     */
    public function details($id)
    {
        $transaction = BalanceTransaction::with(['user', 'booking'])->find($id);
        if (!$transaction) {
            return back()->with(['error' => [__('Transaction not found')]]);
        }

        $page_title = __("Transaction Details - :trx", ['trx' => $transaction->trx_id]);

        return view('admin.sections.balance-transactions.details', compact(
            'page_title',
            'transaction'
        ));
    }
}
