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
use Throwable;
use App\Models\Expense;
class ExpenseController extends Controller {

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

    public function index() {
        ResponseService::noFeatureThenRedirect('Expense Management');
        ResponseService::noAnyPermissionThenRedirect(['expense-create', 'expense-list']);

        $expenseCategory = $this->expenseCategory->builder()->pluck('name', 'id')->toArray();
        $sessionYear = $this->sessionYear->builder()->pluck('name', 'id');
        $current_session_year = app(CachingService::class)->getDefaultSessionYear();

        $months = sessionYearWiseMonth();

        return view('expense.index', compact('expenseCategory', 'sessionYear', 'current_session_year','months'));
    }


    public function create() {
        ResponseService::noFeatureThenRedirect('Expense Management');
        ResponseService::noPermissionThenRedirect('expense-create');
    }


    // public function store(Request $request) {
    //     ResponseService::noFeatureThenSendJson('Expense Management');
    //     ResponseService::noPermissionThenSendJson('expense-create');
    //     try {
    //         DB::beginTransaction();
    //         $data = ['category_id' => $request->category_id, 'title' => $request->title, 'ref_no' => $request->ref_no, 'amount' => $request->amount, 'date' => date('Y-m-d', strtotime($request->date)), 'description' => $request->description, 'session_year_id' => $request->session_year_id];
    //         $expense = $this->expense->create($data);

    //         $sessionYear = $this->cache->getDefaultSessionYear();
    //         $this->sessionYearsTrackingsService->storeSessionYearsTracking('App\Models\Expense', $expense->id, Auth::user()->id, $sessionYear->id, Auth::user()->school_id, null);

    //         DB::commit();
    //         ResponseService::successResponse('Data Stored Successfully');
    //     } catch (Throwable $e) {
    //         DB::rollBack();
    //         ResponseService::logErrorResponse($e, "Expense Controller -> Store Method");
    //         ResponseService::errorResponse();
    //     }
    // }
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


    public function show($id) {
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

        // $sql = $this->expense->builder()->with('category')->select('*', DB::raw('SUM(amount) as total_salary'))->groupBy('month', 'date')->where(function ($query) use ($search) {
        //         $query->when($search, function ($query) use ($search) {
        //             $query->where(function ($query) use ($search) {
        //                 $query->where('title', 'LIKE', "%$search%")->orWhere('ref_no', 'LIKE', "%$search%")->orWhere('amount', 'LIKE', "%$search%")->orWhere('date', 'LIKE', "%$search%")->orWhere('description', 'LIKE', "%$search%")->orWhereHas('category', function ($q) use ($search) {
        //                         $q->Where('name', 'LIKE', "%$search%");
        //                     });
        //             });
        //         });
        //     });

        $sql = $this->expense->builder()->with('category')->where(function ($query) use ($search) {
            $query->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('title', 'LIKE', "%$search%")->orWhere('ref_no', 'LIKE', "%$search%")->orWhere('amount', 'LIKE', "%$search%")->orWhere('date', 'LIKE', "%$search%")->orWhere('description', 'LIKE', "%$search%")->orWhereHas('category', function ($q) use ($search) {
                            $q->Where('name', 'LIKE', "%$search%");
                        });
                });
            });
        });

        if ($category_id) {
            if ($category_id != 'salary') {
                $sql->where('category_id', $category_id)->whereNull('staff_id');
            } else {
                $sql->whereNotNull('staff_id');

            }
        }

        if ($session_year_id) {
            $sql->where('session_year_id', $session_year_id);
        }

        if ($month) {
            $sql->whereMonth('date', $month);
        }

        $total = $sql->get()->count();

        $sql->orderBy($sort, $order)->skip($offset)->take($limit);
        $res = $sql->get();
        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
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
            if ($row->staff_id) {
                $tempRow['category.name'] = 'Salary';
            }
            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }


    // public function update(Request $request, $id) {
    //     ResponseService::noFeatureThenRedirect('Expense Management');
    //     ResponseService::noPermissionThenSendJson('expense-edit');
    //     try {
    //         DB::beginTransaction();
    //         $data = ['category_id' => $request->category_id, 'title' => $request->title, 'ref_no' => $request->ref_no, 'amount' => $request->amount, 'date' => date('Y-m-d', strtotime($request->date)), 'description' => $request->description, 'session_year_id' => $request->session_year_id,];
    //         $this->expense->update($id, $data);
    //         DB::commit();
    //         ResponseService::successResponse('Data Updated Successfully');
    //     } catch (Throwable $e) {
    //         DB::rollBack();
    //         ResponseService::logErrorResponse($e, "Expense Controller -> Update Method");
    //         ResponseService::errorResponse();
    //     }
    // }

  public function update(Request $request, $id)
{
    ResponseService::noFeatureThenRedirect('Expense Management');
    ResponseService::noPermissionThenSendJson('expense-edit');

    $request->validate([
        'title' => 'required|string|max:255',
        'amount' => 'required|numeric|min:0',
        'category_id' => 'required|exists:expense_categories,id',
        'date' => 'required|date',
        'session_year_id' => 'required|exists:session_years,id',
    ]);

    try {
        DB::beginTransaction();

        // 1️⃣ Old expense record fetch karo
        $oldExpense = Expense::find($id);
        $oldAmount = $oldExpense->amount;

        // 2️⃣ New amount aur difference nikalo
        $newAmount = $request->amount;
        $difference = $newAmount - $oldAmount;

        if ($difference == 0) {
            DB::rollBack();
            return ResponseService::successResponse('No changes in amount.');
        }

        // 3️⃣ Latest balance lo
        $latestBalance = DB::table('manage_expenses')
            ->orderBy('id', 'desc')
            ->value('balance') ?? 0;

        // 4️⃣ Negative limit check karo
        $negativeLimit = DB::table('school_settings')
            ->where('name', 'expense_negative_limit')
            ->value('data') ?? 0;

        // 5️⃣ New balance calculate karo
        $newBalance = $latestBalance - $difference;

        if ($newBalance < -$negativeLimit) {
            DB::rollBack();
            return ResponseService::errorResponse('Insufficient Balance');
        }

        // 6️⃣ Expense record update karo
        $data = [
            'category_id' => $request->category_id,
            'title' => $request->title,
            'ref_no' => $request->ref_no,
            'amount' => $newAmount,
            'date' => date('Y-m-d', strtotime($request->date)),
            'description' => $request->description,
            'session_year_id' => $request->session_year_id,
            'updated_at' => now(),
        ];
        $this->expense->update($id, $data);

        // 7️⃣ manage_expenses me naya row insert karo (sirf difference ke liye)
        $type = $difference > 0 ? 'debit' : 'credit'; // agar zyada hua to debit, kam hua to credit

        // DB::table('manage_expenses')->insert([
        //     'title' => 'Exp  Adj.: ' . $request->title." ₹ ". $oldExpense->amount ." -> ".$newAmount,
        //     'amount' => abs($difference),
        //     'description' => 'Edited expense adjustment',
        //     'type' => $type,
        //     'balance' => $newBalance,
        //     'transaction_date' => now(),
        //     'created_at' => now(),
        //     'updated_at' => now(),
        // ]);
          $expense = DB::table('manage_expenses')
            ->select('*')
            ->where('exp_id', $id)
            ->first();
        if (!$expense) {
            throw new \Exception('Expense not found.');
        }

          DB::table('manage_expenses')
            ->where('exp_id', $id)
            ->update([
                'amount' => $request->amount,
                'updated_at' => now(),
            ]);

        // 🔹 Step 2: expance_trans_log में नया record insert करो
        DB::table('expance_trans_log')->insert([
            'expnace_trans_id' =>  $expense->id,
            'user_id' => Auth::id(),
            'amount' => $expense->amount,
            'description' => "Privious Amount ".$expense->amount,
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

        // 1️⃣ Expense record fetch karo
        $expense = Expense::find($id);
        if (!$expense) {
            DB::rollBack();
            return ResponseService::errorResponse('Expense not found.');
        }

        $amount = $expense->amount;

        // 2️⃣ Latest balance fetch karo
        $latestBalance = DB::table('manage_expenses')
            ->orderBy('id', 'desc')
            ->value('balance') ?? 0;

        // 3️⃣ New balance calculate karo (delete -> credit)
        $newBalance = $latestBalance + $amount; // delete karne par credit

        // 4️⃣ Expense delete karo
        $this->expense->deleteById($id);

        // 5️⃣ Manage_expenses me adjustment entry add karo
        DB::table('manage_expenses')->where('exp_id', $expense->id)->delete();

        // 6️⃣ Session year tracking
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
        ResponseService::noAnyPermissionThenSendJson(['expense-create','expense-list']);

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
