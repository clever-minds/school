<?php

namespace App\Http\Controllers;

use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Throwable;
use Carbon\Carbon;
use App\Repositories\Expense\ExpenseInterface;
use App\Repositories\ExpenseCategory\ExpenseCategoryInterface;
use App\Repositories\SessionYear\SessionYearInterface;
use App\Services\CachingService;
use App\Services\SessionYearsTrackingsService;

class ExpenseTransController extends Controller
{
    private ExpenseInterface $expense;
    private ExpenseCategoryInterface $expenseCategory;
    private SessionYearInterface $sessionYear;
    private CachingService $cache;
    private SessionYearsTrackingsService $sessionYearsTrackingsService;

     public function __construct(ExpenseInterface $expense, ExpenseCategoryInterface $expenseCategory, SessionYearInterface $sessionYear, CachingService $cache, SessionYearsTrackingsService $sessionYearsTrackingsService) {
        $this->expense = $expense;
        $this->expenseCategory = $expenseCategory;
        $this->sessionYear = $sessionYear;
        $this->cache = $cache;
        $this->sessionYearsTrackingsService = $sessionYearsTrackingsService;
    }
    public function index()
    {
        ResponseService::noFeatureThenRedirect('Expense Management');
        ResponseService::noAnyPermissionThenRedirect(['expense-create', 'expense-list']);

        // Get today’s opening & closing balance
        $today = Carbon::today()->toDateString();
        $expenseCategory = $this->expenseCategory->builder()->pluck('name', 'id')->toArray();

      $yesterday = now()->subDay()->toDateString();


        $openingBalance = DB::table('manage_expenses')
            ->whereDate('created_at', $yesterday)
            ->selectRaw("
                COALESCE(SUM(CASE WHEN type = 'credit' THEN amount ELSE 0 END), 0)
                -
                COALESCE(SUM(CASE WHEN type = 'debit' THEN amount ELSE 0 END), 0)
                AS closing_balance
            ")
            ->value('closing_balance');
        $todayCredits = DB::table('manage_expenses')
            ->whereDate('created_at', $today)
            ->where('type', 'credit')
            ->sum('amount');

        $todayDebits = DB::table('manage_expenses')
            ->whereDate('created_at', $today)
            ->where('type', 'debit')
            ->sum('amount');
            

      $todayTransactions = DB::table('manage_expenses')
                ->whereDate('created_at', $today)
                ->orderBy('created_at', 'asc') // by date
                ->orderBy('id', 'asc')               // by insertion order
                ->get();

            $runningBalance = $openingBalance;

            foreach ($todayTransactions as $tx) {
                if ($tx->type === 'credit') {
                    $runningBalance += $tx->amount;
                } else {
                    $runningBalance -= $tx->amount;
                }
                $tx->remaining_balance = $runningBalance; // attach for display
            }

            $closingBalance = $runningBalance; // last value
        $sessionYear = $this->sessionYear->builder()->pluck('name', 'id');
        $current_session_year = app(CachingService::class)->getDefaultSessionYear();

        $months = sessionYearWiseMonth();


        return view('expense-trans.index', compact('openingBalance', 'closingBalance', 'todayCredits', 'todayDebits','expenseCategory','sessionYear', 'current_session_year','months'));
    }

    public function store(Request $request)
    {
        ResponseService::noFeatureThenSendJson('Expense Management');
        ResponseService::noPermissionThenSendJson('expense-create');

        $request->validate([
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $latestBalance = DB::table('manage_expenses')
                ->orderBy('id', 'desc')
                ->value('balance') ?? 0;

            $newBalance =  $request->amount;

            DB::table('manage_expenses')->insert([
                'title' => $request->title,
                'description' => $request->description,
                'amount' => $request->amount,
                'type' => "credit",
                'balance' => $newBalance,
                'transaction_date' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            if(!empty($trans)){
                \DB::table('expance_trans_log')->insert([
                    'expnace_trans_id' => $trans->id,
                    'user_id' => auth()->id(),
                    'trans_type' => 'update',
                    'amount' => $trans->amount,
                    'description' => 'Updated: '.$trans->description,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            DB::commit();
            ResponseService::successResponse('Amount added successfully');
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e, "ManageExpenseController -> store()");
            ResponseService::errorResponse();
        }
    }

    // public function todaySummary()
    // {
    //     $today = Carbon::today()->toDateString();

    //     $openingBalance = DB::table('manage_expenses')
    //         ->whereDate('transaction_date', '<', $today)
    //         ->orderBy('transaction_date', 'desc')
    //         ->value('balance') ?? 0;

    //     $todayCredits = DB::table('manage_expenses')
    //         ->whereDate('transaction_date', $today)
    //         ->where('type', 'credit')
    //         ->sum('amount');

    //     $todayDebits = DB::table('manage_expenses')
    //         ->whereDate('transaction_date', $today)
    //         ->where('type', 'debit')
    //         ->sum('amount');

    //     $closingBalance = $openingBalance + $todayCredits - $todayDebits;

    //     $data = [
    //         'opening_balance' => $openingBalance,
    //         'credits' => $todayCredits,
    //         'debits' => $todayDebits,
    //         'closing_balance' => $closingBalance,
    //     ];

    //     ResponseService::successResponse('Today summary fetched successfully', $data);
    // }

    public function todaySummary(Request $request)
{
    $offset = $request->get('offset', 0);
    $limit = $request->get('limit', 10);
    $sort = $request->get('sort', 'transaction_date');
    $order = $request->get('order', 'desc');

    $query = DB::table('manage_expenses')
    ->orderBy('created_at', 'desc');
    $total = $query->count();

    $rows = $query->skip($offset)->take($limit)->get()->map(function($item, $index) use ($offset) {
        $logs = DB::table('expance_trans_log')
        ->where('expnace_trans_id', $item->id)
        ->orderBy('created_at', 'desc')
        ->get()
        ->map(function($log) {
            return [
                'amount' => $log->amount,
                'description' => $log->description, // If you store old & new separately, use proper values
                'updated_by_name' => DB::table('users')
                    ->where('id', $log->user_id)
                    ->select(DB::raw("CONCAT(first_name, ' ', last_name) as full_name"))
                    ->value('full_name'),
                'updated_at' => Carbon::parse($log->created_at)->format('d-m-Y H:i'),
            ];
        });
        return [
            'no' => $offset + $index + 1,
             'id' => $item->id,
            'title' => $item->title,
            'type' => ucfirst($item->type),
            'amount' => number_format($item->amount, 2),
            'balance' => number_format($item->balance, 2),
            'transaction_date' => Carbon::parse($item->updated_at)->format('d-m-Y'),
            'logs' => $logs,
        ];
    });

    return response()->json([
        'total' => $total,
        'rows' => $rows
    ]);
}

public function update(Request $request, $id)
{
    DB::beginTransaction(); // Transaction start

    try {
   $trans = \App\Models\ManageExpense::findOrFail($id);

   
        // Log update
        \DB::table('expance_trans_log')->insert([
            'expnace_trans_id' => $trans->id,
            'user_id' => auth()->id(),
            'trans_type' =>  $trans->type,
            'amount' => $trans->amount,
            'description' => 'Updated: ' . $request->description,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
            \App\Models\Expense::where('id', $trans->exp_id)->update([
                'amount' =>  $request->amount,
            ]);
        $trans->update([
                'title' => $request->title,
                'amount' => $request->amount,
                'description' => $request->description,
                'updated_at' => now(),
            ]);

        DB::commit(); // Commit if everything ok
        return response()->json(['success' => true]);

    } catch (\Exception $e) {
        DB::rollBack(); // Rollback on error
        return response()->json(['success' => false, 'error' => $e->getMessage()]);
    }
}

public function destroy($id)
{
    DB::beginTransaction();

    try {
      $trans = DB::table('manage_expenses')->where("exp_id",$id)->first();
             
        // Log delete first
        \DB::table('expance_trans_log')->insert([
            'expnace_trans_id' => $trans->id,
            'user_id' => auth()->id(),
            'trans_type' => 'delete',
            'amount' => $trans->amount,
            'description' => 'Deleted: ' . $trans->description,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $trans->delete();
        DB::table('expenses')->where("exp_id",$id)->first();

        DB::commit();
        return response()->json(['success' => true]);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['success' => false, 'error' => $e->getMessage()]);
    }
}


}
