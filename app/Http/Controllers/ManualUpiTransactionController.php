<?php

namespace App\Http\Controllers;

use App\Models\ManualUpiTransaction;
use Illuminate\Http\Request;

class ManualUpiTransactionController extends Controller
{
    public function index()
    {
        if (!auth()->user()->can('manual-upi-transaction-list')) {
            abort(403, 'Unauthorized action.');
        }

        $transactions = ManualUpiTransaction::with('student.user')->latest()->paginate(15);
        return view('manual_upi_transactions.index', compact('transactions'));
    public function accept($id)
    {
        if (!auth()->user()->can('manual-upi-transaction-list')) {
            abort(403, 'Unauthorized action.');
        }

        $transaction = ManualUpiTransaction::findOrFail($id);
        $transaction->status = 'success';
        $transaction->save();

        return redirect()->back()->with('success', __('Transaction accepted successfully.'));
    }

    public function reject($id)
    {
        if (!auth()->user()->can('manual-upi-transaction-list')) {
            abort(403, 'Unauthorized action.');
        }

        $transaction = ManualUpiTransaction::findOrFail($id);
        $transaction->status = 'failed';
        $transaction->save();

        return redirect()->back()->with('success', __('Transaction rejected successfully.'));
    }
}
