<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\SessionYear;
use App\Models\Staff;
use App\Models\StaffPayroll;
use App\Models\StaffPerformanceEvaluation;
use App\Models\StaffSalary;
use App\Models\User;
use App\Services\BootstrapTableService;
use App\Services\CachingService;
use App\Services\ResponseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use Throwable;

class StaffPerformanceController extends Controller
{
    public function index()
    {
        ResponseService::noAnyPermissionThenRedirect(['staff-performance-list', 'staff-performance-create', 'payroll-list', 'payroll-create', 'payroll-edit']);

        $sessionYears = SessionYear::Owner()->orderBy('start_date', 'DESC')->get();
        $defaultSessionYear = app(CachingService::class)->getDefaultSessionYear();

        $roles = Role::whereNotIn('name', ['Student', 'Parent', 'Guardian', 'School Admin', 'Super Admin', 'Principal'])
            ->where(function ($q) {
                $q->whereNull('school_id');
                if (Auth::user() && Auth::user()->school_id) {
                    $q->orWhere('school_id', Auth::user()->school_id);
                }
            })
            ->get()
            ->unique('name');

        $months = sessionYearWiseMonth();
        $currentMonth = Carbon::now()->month;
        $criteriaList = StaffPerformanceEvaluation::getDefaultCriteria();

        return view('staff_performance.index', compact('sessionYears', 'defaultSessionYear', 'roles', 'months', 'currentMonth', 'criteriaList'));
    }

    public function list(Request $request)
    {
        if (!Auth::user()->can('staff-performance-list') && !Auth::user()->can('staff-performance-create') && !Auth::user()->can('payroll-list') && !Auth::user()->can('payroll-create')) {
            ResponseService::noAnyPermissionThenSendJson(['staff-performance-list', 'staff-performance-create', 'payroll-list', 'payroll-create']);
        }

        $month = (int) $request->input('month', Carbon::now()->month);
        $sessionYearId = $request->input('session_year_id');
        $roleId = $request->input('role_id');
        $search = $request->input('search');

        // Automatically determine year from session year & selected month
        $year = $request->input('year');
        if (!$year) {
            $selectedSessionYear = $sessionYearId ? SessionYear::find($sessionYearId) : app(CachingService::class)->getDefaultSessionYear();
            if ($selectedSessionYear && $selectedSessionYear->start_date && $selectedSessionYear->end_date) {
                $startYear = (int) date('Y', strtotime($selectedSessionYear->start_date));
                $endYear = (int) date('Y', strtotime($selectedSessionYear->end_date));
                $startMonth = (int) date('m', strtotime($selectedSessionYear->start_date));
                $year = ($month >= $startMonth) ? $startYear : $endYear;
            } else {
                $year = (int) Carbon::now()->year;
            }
        }

        $offset = $request->input('offset', 0);
        $limit = $request->input('limit', 10);
        $sort = $request->input('sort', 'name');
        $order = $request->input('order', 'ASC');

        $query = Staff::select('staffs.*')
            ->join('users', 'staffs.user_id', '=', 'users.id')
            ->with(['user.roles', 'staffSalary.payrollSetting'])
            ->whereNull('users.deleted_at');

        if (Auth::user() && Auth::user()->school_id) {
            $query->where('users.school_id', Auth::user()->school_id);
        }

        // Exclude Principal, Admins, Students, Parents, Guardians from performance list
        $query->whereDoesntHave('user.roles', function ($q) {
            $q->whereIn('roles.name', ['Principal', 'School Admin', 'Super Admin', 'Student', 'Parent', 'Guardian']);
        });

        if ($roleId) {
            $selectedRole = Role::find($roleId);
            $query->whereHas('user.roles', function ($q) use ($roleId, $selectedRole) {
                $q->where(function ($sub) use ($roleId, $selectedRole) {
                    $sub->where('roles.id', $roleId);
                    if ($selectedRole) {
                        $sub->orWhere('roles.name', $selectedRole->name);
                    }
                });
            });
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('users.first_name', 'like', "%{$search}%")
                    ->orWhere('users.last_name', 'like', "%{$search}%")
                    ->orWhere('users.email', 'like', "%{$search}%")
                    ->orWhereRaw("concat(users.first_name, ' ', users.last_name) LIKE '%" . $search . "%'");
            });
        }

        if ($sessionYearId) {
            $selectedSessionYear = SessionYear::find($sessionYearId);
            if ($selectedSessionYear && $selectedSessionYear->end_date) {
                $query->where(function ($q) use ($selectedSessionYear, $sessionYearId) {
                    $q->where(function ($sub) use ($selectedSessionYear) {
                        $sub->whereNull('staffs.joining_date')
                            ->orWhereDate('staffs.joining_date', '<=', $selectedSessionYear->end_date);
                    })->where(function ($sub) use ($sessionYearId) {
                        $sub->whereNull('staffs.leave_session_year_id')
                            ->orWhere('staffs.leave_session_year_id', '>=', $sessionYearId);
                    });
                });
            }
        }

        $total = (clone $query)->count('staffs.id');

        if ($sort === 'name' || $sort === 'first_name') {
            $query->orderBy('users.first_name', $order)->orderBy('users.last_name', $order);
        } else {
            $query->orderBy('staffs.id', $order);
        }

        $staffMembers = $query->skip($offset)->take($limit)->get();
        $staffIds = $staffMembers->pluck('id')->filter()->toArray();

        $evaluations = StaffPerformanceEvaluation::whereIn('staff_id', $staffIds)
            ->where('month', $month)
            ->where('year', $year)
            ->get()
            ->keyBy('staff_id');

        $rows = [];
        $no = $offset + 1;

        foreach ($staffMembers as $staff) {
            $user = $staff->user;
            if (!$user) {
                continue;
            }

            // Calculate base performance incentive from salary structure
            $baseIncentive = 0;
            $hasIncentive = false;
            foreach ($staff->staffSalary as $item) {
                $isPerf = $item->payrollSetting && (
                    $item->payrollSetting->type === 'performance' ||
                    ($item->payrollSetting->type === 'allowance' && stripos($item->payrollSetting->name, 'performance') !== false)
                );
                if ($isPerf) {
                    $hasIncentive = true;
                    if (isset($item->amount)) {
                        $baseIncentive += (float) $item->amount;
                    } elseif (isset($item->percentage)) {
                        $baseIncentive += ((float) $item->percentage / 100) * (float) $staff->salary;
                    }
                }
            }

            $evaluation = $evaluations->get($staff->id);

            if ($evaluation) {
                $positive = $evaluation->positive_points;
                $negative = $evaluation->negative_points;
                $earnedIncentive = ($positive / 10) * $baseIncentive;
                $scoreBadge = $positive == 10 ? 'badge-success' : ($positive >= 7 ? 'badge-info' : 'badge-danger');
                $scoreText = "<span class='badge {$scoreBadge}' style='font-size: 11px; padding: 5px 10px;'><i class='fa fa-star text-warning mr-1'></i>{$positive} / 10</span>";
                $statusBadge = "<span class='badge badge-success' style='font-size: 11px; padding: 5px 10px;'><i class='fa fa-check mr-1'></i>" . __('Evaluated') . "</span>";
            } else {
                $positive = 10;
                $negative = 0;
                $earnedIncentive = $baseIncentive;
                $scoreText = "<span class='badge badge-secondary' style='font-size: 11px; padding: 5px 10px;'>10 / 10 (" . __('Default') . ")</span>";
                $statusBadge = "<span class='badge badge-warning' style='font-size: 11px; padding: 5px 10px;'><i class='fa fa-clock-o mr-1'></i>" . __('Pending') . "</span>";
            }

            $rolesText = $user->roles->pluck('name')->join(', ');

            $operate = '<button type="button" class="btn btn-sm btn-theme evaluate-staff-btn" '
                . 'data-staff-id="' . $staff->id . '" '
                . 'data-staff-name="' . htmlspecialchars($user->full_name, ENT_QUOTES, 'UTF-8') . '" '
                . 'data-month="' . $month . '" '
                . 'data-year="' . $year . '" '
                . 'data-base-incentive="' . $baseIncentive . '" '
                . 'data-has-incentive="' . ($hasIncentive ? '1' : '0') . '" '
                . 'title="' . __('Evaluate Performance') . '">'
                . '<i class="fa fa-star mr-1"></i> ' . ($evaluation ? __('Edit Feedback') : __('Evaluate'))
                . '</button>';

            $rows[] = [
                'id' => $staff->id,
                'no' => $no++,
                'name' => $user->full_name,
                'role' => $rolesText,
                'base_incentive' => number_format($baseIncentive, 2),
                'score' => $scoreText,
                'earned_incentive' => number_format($earnedIncentive, 2),
                'status' => $statusBadge,
                'operate' => $operate,
            ];
        }

        return response()->json([
            'total' => $total,
            'rows' => $rows
        ]);
    }

    public function getEvaluation(Request $request)
    {
        if (!Auth::user()->can('staff-performance-list') && !Auth::user()->can('staff-performance-create') && !Auth::user()->can('payroll-list') && !Auth::user()->can('payroll-create')) {
            return ResponseService::errorResponse('Permission denied', null, 403);
        }

        $validator = Validator::make($request->all(), [
            'staff_id' => 'required|exists:staffs,id',
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return ResponseService::errorResponse($validator->errors()->first());
        }

        $evaluation = StaffPerformanceEvaluation::where('school_id', Auth::user()->school_id)
            ->where('staff_id', $request->staff_id)
            ->where('month', $request->month)
            ->where('year', $request->year)
            ->first();

        $defaultCriteria = StaffPerformanceEvaluation::getDefaultCriteria();
        $criteriaData = [];

        foreach ($defaultCriteria as $key => $title) {
            $status = 1; // default positive
            $remark = '';
            if ($evaluation && isset($evaluation->criteria[$key])) {
                $status = (int) ($evaluation->criteria[$key]['status'] ?? 1);
                $remark = $evaluation->criteria[$key]['remark'] ?? '';
            }
            $criteriaData[$key] = [
                'title' => $title,
                'status' => $status,
                'remark' => $remark,
            ];
        }

        return response()->json([
            'error' => false,
            'data' => [
                'is_evaluated' => (bool) $evaluation,
                'positive_points' => $evaluation ? $evaluation->positive_points : 10,
                'negative_points' => $evaluation ? $evaluation->negative_points : 0,
                'remarks' => $evaluation ? $evaluation->remarks : '',
                'criteria' => $criteriaData,
            ]
        ]);
    }

    public function store(Request $request)
    {
        if (!Auth::user()->can('staff-performance-create') && !Auth::user()->can('staff-performance-edit') && !Auth::user()->can('payroll-create') && !Auth::user()->can('payroll-edit')) {
            return ResponseService::errorResponse('Permission denied', null, 403);
        }

        $validator = Validator::make($request->all(), [
            'staff_id' => 'required|exists:staffs,id',
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer',
            'criteria' => 'required|array',
            'remarks' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return ResponseService::errorResponse($validator->errors()->first());
        }

        try {
            DB::beginTransaction();

            $positivePoints = 0;
            $negativePoints = 0;
            $criteriaPayload = [];
            $defaultCriteria = StaffPerformanceEvaluation::getDefaultCriteria();

            foreach ($defaultCriteria as $key => $title) {
                $status = (isset($request->criteria[$key]['status']) && (string)$request->criteria[$key]['status'] === '1') ? 1 : 0;
                $remark = $request->criteria[$key]['remark'] ?? '';

                if ($status === 1) {
                    $positivePoints++;
                } else {
                    $negativePoints++;
                }

                $criteriaPayload[$key] = [
                    'title' => $title,
                    'status' => $status,
                    'remark' => $remark,
                ];
            }

            $scorePercentage = ($positivePoints / 10) * 100;

            $evaluation = StaffPerformanceEvaluation::updateOrCreate(
                [
                    'school_id' => Auth::user()->school_id,
                    'staff_id' => $request->staff_id,
                    'month' => $request->month,
                    'year' => $request->year,
                ],
                [
                    'criteria' => $criteriaPayload,
                    'positive_points' => $positivePoints,
                    'negative_points' => $negativePoints,
                    'total_points' => 10,
                    'score_percentage' => $scorePercentage,
                    'evaluated_by' => Auth::id(),
                    'remarks' => $request->remarks ?? null,
                ]
            );

            // Sync with existing Expense (payroll) and StaffPayroll if already generated
            $expense = Expense::where('staff_id', $request->staff_id)
                ->where(function ($q) use ($request) {
                    $q->where('month', (int)$request->month)->orWhere('month', $request->month);
                })
                ->where(function ($q) use ($request) {
                    $q->where('year', (int)$request->year)->orWhere('year', $request->year);
                })
                ->first();

            if ($expense && count($expense->staff_payroll)) {
                foreach ($expense->staff_payroll as $sp) {
                    $setting = $sp->payroll_setting;
                    $isPerf = $setting && (
                        $setting->type === 'performance' ||
                        ($setting->type === 'allowance' && stripos($setting->name, 'performance') !== false)
                    );

                    if ($isPerf) {
                        $baseIncentive = 0;
                        $staffSalaryItem = StaffSalary::where('staff_id', $expense->staff_id)
                            ->where('payroll_setting_id', $sp->payroll_setting_id)
                            ->first();

                        if ($staffSalaryItem) {
                            if ($staffSalaryItem->amount) {
                                $baseIncentive = (float)$staffSalaryItem->amount;
                            } elseif ($staffSalaryItem->percentage) {
                                $baseIncentive = ((float)$staffSalaryItem->percentage / 100) * (float)$expense->basic_salary;
                            }
                        }

                        if (!$baseIncentive) {
                            $baseIncentive = (float)$sp->amount;
                        }

                        $earnedIncentive = ($positivePoints / 10) * $baseIncentive;
                        $oldAmount = (float)$sp->amount;

                        $sp->amount = $earnedIncentive;
                        $sp->save();

                        $diff = $oldAmount - $earnedIncentive;
                        if ($diff != 0) {
                            $expense->amount = (float)$expense->amount - $diff;
                            $expense->save();
                        }
                    }
                }
            }

            DB::commit();
            return ResponseService::successResponse('Performance Evaluation Saved Successfully');
        } catch (Throwable $th) {
            DB::rollBack();
            ResponseService::logErrorResponse($th);
            return ResponseService::errorResponse();
        }
    }
}
