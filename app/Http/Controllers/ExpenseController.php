<?php

namespace App\Http\Controllers;

use App\Repositories\Expense\ExpenseInterface;
use App\Repositories\ExpenseCategory\ExpenseCategoryInterface;
use App\Repositories\SessionYear\SessionYearInterface;
use App\Services\BootstrapTableService;
use App\Services\CachingService;
use App\Services\ResponseService;
use App\Services\SessionYearsTrackingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Throwable;
use Carbon\Carbon;

class ExpenseController extends Controller
{

    private ExpenseInterface $expense;
    private ExpenseCategoryInterface $expenseCategory;
    private SessionYearInterface $sessionYear;
    private CachingService $cache;
    private SessionYearsTrackingsService $sessionYearsTrackingsService;

    public function __construct(ExpenseInterface $expense, ExpenseCategoryInterface $expenseCategory, SessionYearInterface $sessionYear, CachingService $cache, SessionYearsTrackingsService $sessionYearsTrackingsService)
    {
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

        $expenseCategory = $this->expenseCategory->builder()->pluck('name', 'id')->toArray();
        $sessionYear = $this->sessionYear->builder()->pluck('name', 'id');
        $sessionYearFullData = $this->sessionYear->builder()->get();
        $current_session_year = app(CachingService::class)->getDefaultSessionYear();

        $months = sessionYearWiseMonth();

        return view('expense.index', compact('expenseCategory', 'sessionYear', 'current_session_year','months'));
    }


    public function create() {
        return view('expense.index', compact('expenseCategory', 'sessionYear', 'current_session_year', 'months', 'sessionYearFullData'));
    }


    public function create()
    {
        ResponseService::noFeatureThenRedirect('Expense Management');
        ResponseService::noPermissionThenRedirect('expense-create');
    }


  
public function store(Request $request) {
    ResponseService::noFeatureThenSendJson('Expense Management');
    ResponseService::noPermissionThenSendJson('expense-create');

    $request->validate([
        'title' => 'required|string|max:255',
        'amount' => 'required|numeric|min:0',
        'category_id' => 'required|exists:expense_categories,id',
        'date' => 'required|date',
        'session_year_id' => 'required|exists:session_years,id',
    ]);

    try {
        DB::beginTransaction();

        // --- Get latest balance ---
        $latestBalance = DB::table('manage_expenses')
            ->orderBy('id', 'desc')
            ->value('balance') ?? 0;

        $type = 'debit'; 
        $amount = $request->amount;

        // --- Get allowed negative limit ---
        $negativeLimit = DB::table('school_settings')
            ->where('name', 'expense_negative_limit')
            ->value('data');

        if ($negativeLimit === null) {
            $negativeLimit = 0;
        }

        $newBalance = $latestBalance - $amount;

        if ($newBalance < -$negativeLimit) {
            DB::rollBack();
            return ResponseService::errorResponse('Insufficient Balance');
        }

        $data = [
            'category_id' => $request->category_id,
            'title' => $request->title,
            'ref_no' => $request->ref_no,
            'amount' => $amount,
            'date' => date('Y-m-d', strtotime($request->date)),
            'description' => $request->description,
            'session_year_id' => $request->session_year_id,
            'type' => $type,
            'balance' => $newBalance,
            'transaction_date' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $expense = $this->expense->create($data);
        $data = [ 'exp_id'=>$expense->id,'title' => $request->title, 'amount' => $request->amount, 'description' => $request->description, 'type' => $type, 'balance' => $newBalance, 'transaction_date' => now(), 'created_at' => now(), 'updated_at' => now(), ];
        DB::table('manage_expenses')->insert( $data);
        // --- Optional: tracking session years ---
        $sessionYear = $this->cache->getDefaultSessionYear();
        $this->sessionYearsTrackingsService->storeSessionYearsTracking(
            'App\Models\Expense',
            $expense->id,
            Auth::user()->id,
            $sessionYear->id,
            Auth::user()->school_id,
            null
        );

        DB::commit();
        ResponseService::successResponse('Data Stored Successfully');

    } catch (Throwable $e) {
        DB::rollBack();
        ResponseService::logErrorResponse($e, "Expense Controller -> Store Method");
        ResponseService::errorResponse();
    }
}


  public function show($id)
{
    ResponseService::noFeatureThenRedirect('Expense Management');
    ResponseService::noPermissionThenRedirect('expense-list');

    $offset = request('offset', 0);
    $limit = request('limit', 10);
    $sort = request('sort', 'date');
    $order = request('order', 'DESC');
    $search = request('search');
    $category_id = request('category_id');
    $session_year_id = request('session_year_id');
    $month = request('month');

    // ✅ Base query (exclude vehicle expenses)
    $sql = $this->expense->builder()
        ->with('category')
        ->whereNull('vehicle_id')
        ->where(function ($query) use ($search) {
            $query->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('title', 'LIKE', "%$search%")
                        ->orWhere('ref_no', 'LIKE', "%$search%")
                        ->orWhere('amount', 'LIKE', "%$search%")
                        ->orWhere('date', 'LIKE', "%$search%")
                        ->orWhere('description', 'LIKE', "%$search%")
                        ->orWhereHas('category', function ($q) use ($search) {
                            $q->where('name', 'LIKE', "%$search%");
                        });
                });
            });
        });

    // ✅ Filter: Category
    if ($category_id) {
        if ($category_id != 'salary') {
            $sql->where('category_id', $category_id)->whereNull('staff_id');
        } else {
            $sql->whereNotNull('staff_id');
        }
    }

    // ✅ Filter: Session Year
    if ($session_year_id) {
        $sql->where('session_year_id', $session_year_id);
    }

    // ✅ Filter: Month
    if ($month) {
        $sql->whereMonth('date', $month);
    }

    // ✅ Pagination
    $total = $sql->count();

    if ($offset >= $total && $total > 0) {
        $lastPage = floor(($total - 1) / $limit) * $limit;
        $offset = $lastPage;
    }

    $res = $sql->orderBy($sort, $order)
        ->skip($offset)
        ->take($limit)
        ->get();

    // ✅ Response formatting
    $bulkData = [];
    $bulkData['total'] = $total;
    $rows = [];
    $no = 1;

    foreach ($res as $row) {
        $operate = '';

        if (!$row->month) {
            $operate .= BootstrapTableService::editButton(route('expense.update', $row->id));
            $operate .= BootstrapTableService::deleteButton(route('expense.destroy', $row->id));
        }

        $tempRow = $row->toArray();
        $tempRow['no'] = $no++;
        $tempRow['amount'] = $row->amount;
        $tempRow['category_name'] = $row->staff_id ? 'Salary' : ($row->category->name ?? '-');
        $tempRow['operate'] = $operate;

        $rows[] = $tempRow;
    }

    $bulkData['rows'] = $rows;

    return response()->json($bulkData);
}



  
public function update(Request $request, $id)
{
    ResponseService::noFeatureThenRedirect('Expense Management');
    ResponseService::noPermissionThenSendJson('expense-edit');

    // ✅ Validation (merge of both versions)
    $request->validate([
        'ref_no' => [
            'nullable',
            Rule::unique('expenses', 'ref_no')
                ->ignore($id)
                ->where(function ($query) use ($request) {
                    return $query->where('session_year_id', $request->session_year_id)
                        ->whereNull('vehicle_id')
                        ->whereNull('staff_id');
                }),
        ],
        'title' => 'required|string|max:255',
        'amount' => 'required|numeric|min:0',
        'category_id' => 'required|exists:expense_categories,id',
        'date' => 'required|date',
        'session_year_id' => 'required|exists:session_years,id',
    ], [
        'ref_no.unique' => 'Reference number already exists for the selected session year.'
    ]);

    try {
        DB::beginTransaction();

        $schoolSettings = $this->cache->getSchoolSettings();

        // 🔹 Fetch old expense
        $oldExpense = Expense::find($id);
        if (!$oldExpense) {
            throw new \Exception('Expense not found.');
        }

        $oldAmount = $oldExpense->amount;
        $newAmount = $request->amount;
        $difference = $newAmount - $oldAmount;

        // 🔹 If no change in amount, skip further process
        if ($difference == 0) {
            DB::rollBack();
            return ResponseService::successResponse('No changes in amount.');
        }

        // 🔹 Latest balance
        $latestBalance = DB::table('manage_expenses')
            ->orderBy('id', 'desc')
            ->value('balance') ?? 0;

        // 🔹 Negative limit
        $negativeLimit = DB::table('school_settings')
            ->where('name', 'expense_negative_limit')
            ->value('data') ?? 0;

        // 🔹 Calculate new balance
        $newBalance = $latestBalance - $difference;

        if ($newBalance < -$negativeLimit) {
            DB::rollBack();
            return ResponseService::errorResponse('Insufficient Balance');
        }

        // 🔹 Update Expense Table
        $data = [
            'category_id' => $request->category_id,
            'title' => $request->title,
            'ref_no' => $request->ref_no,
            'amount' => $newAmount,
            'date' => Carbon::createFromFormat($schoolSettings['date_format'], $request->date)->format('Y-m-d'),
            'description' => $request->description,
            'session_year_id' => $request->session_year_id,
            'updated_at' => now(),
        ];

        $this->expense->update($id, $data);

        // 🔹 Find matching manage_expense record
        $expenseRecord = DB::table('manage_expenses')
            ->where('exp_id', $id)
            ->first();

        if (!$expenseRecord) {
            throw new \Exception('Linked manage_expense record not found.');
        }

        // 🔹 Update manage_expenses amount and balance
        DB::table('manage_expenses')
            ->where('exp_id', $id)
            ->update([
                'amount' => $newAmount,
                'balance' => $newBalance,
                'updated_at' => now(),
            ]);

        // 🔹 Log transaction difference in expance_trans_log
        DB::table('expance_trans_log')->insert([
            'expnace_trans_id' => $expenseRecord->id,
            'user_id' => Auth::id(),
            'amount' => abs($difference),
            'description' => "Expense edited: Previous ₹{$oldAmount}, New ₹{$newAmount}",
            'type' => $difference > 0 ? 'debit' : 'credit',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::commit();
        ResponseService::successResponse('Expense and Balance Updated Successfully');

    } catch (Throwable $e) {
        DB::rollBack();
        ResponseService::logErrorResponse($e, "Expense Controller -> Update Method");
        ResponseService::errorResponse();
    }
}


public function destroy($id)
{
    ResponseService::noFeatureThenRedirect('Expense Management');
    ResponseService::noPermissionThenSendJson('expense-delete');

    try {
        DB::beginTransaction();

        // 🔹 Step 1: Fetch expense record
        $expense = Expense::find($id);
        if (!$expense) {
            DB::rollBack();
            return ResponseService::errorResponse('Expense not found.');
        }

        $amount = $expense->amount;

        // 🔹 Step 2: Get latest balance
        $latestBalance = DB::table('manage_expenses')
            ->orderBy('id', 'desc')
            ->value('balance') ?? 0;

        // 🔹 Step 3: Calculate new balance after deleting (credit back)
        $newBalance = $latestBalance + $amount;

        // 🔹 Step 4: Delete expense record
        $this->expense->deleteById($id);

        // 🔹 Step 5: Delete related manage_expense record
        DB::table('manage_expenses')
            ->where('exp_id', $id)
            ->delete();

        // 🔹 Step 6: Add balance adjustment log (optional but safer)
        DB::table('manage_expenses')->insert([
            'title' => 'Expense Deleted: ' . ($expense->title ?? 'N/A'),
            'amount' => $amount,
            'description' => 'Expense deletion adjustment (credited back)',
            'type' => 'credit',
            'balance' => $newBalance,
            'transaction_date' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 🔹 Step 7: Track session year activity
        $sessionYear = $this->cache->getDefaultSessionYear();
        $this->sessionYearsTrackingsService->storeSessionYearsTracking(
            'App\Models\Expense',
            $id,
            Auth::user()->id,
            $sessionYear->id,
            Auth::user()->school_id,
            null
        );

        DB::commit();
        ResponseService::successResponse('Expense Deleted and Balance Adjusted Successfully');

    } catch (Throwable $e) {
        DB::rollBack();
        ResponseService::logErrorResponse($e, "Expense Controller -> Destroy Method");
        ResponseService::errorResponse();
    }
}
 public function filter_graph($session_year_id)
    {
        ResponseService::noFeatureThenRedirect('Expense Management');
        ResponseService::noAnyPermissionThenSendJson(['expense-create', 'expense-list']);

        try {
            $expense_months = [];
            $expense_amount = [];
            if ($session_year_id == 'undefined' || $session_year_id == '') {
                $session_year_id = $this->cache->getDefaultSessionYear()->id;
            }
            
            $expense = $this->expense->builder()->select(DB::raw('MONTH(date) as month'), DB::raw('SUM(amount) as total_amount'))->where('session_year_id', $session_year_id)
                ->groupBy(DB::raw('MONTH(date)'));
            $expense = $expense->get()->pluck('total_amount', 'month')->toArray();

            $months = sessionYearWiseMonth();
            foreach ($months as $key => $month) {
                if (isset($expense[$key])) {
                    // $expense_months[] = substr($months[$key], 0, 3);
                    $expense_months[] = $months[$key];
                    $expense_amount[] = $expense[$key];
                }
            }
            $data = [
                'expense_months' => $expense_months,
                'expense_amount' => $expense_amount
            ];

            ResponseService::successResponse('Data Fetched Successfully', $data);
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e, "Expense Controller -> Filter Method");
            ResponseService::errorResponse();
        }
    }
}
