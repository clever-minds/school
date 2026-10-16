<?php

namespace App\Http\Controllers;

use App\Models\SessionYear;
use App\Models\Staff;
use App\Models\StaffPerformanceEvaluation;
use App\Models\User;
use App\Services\BootstrapTableService;
use App\Services\CachingService;
use App\Services\ResponseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use Throwable;

class StaffPerformanceController extends Controller
{
    public function index()
    {
        ResponseService::noAnyPermissionThenRedirect(['staff-performance-list', 'staff-performance-create', 'payroll-list', 'payroll-create', 'payroll-edit']);

        $schoolId = Auth::user()->school_id;

        $roles = Role::whereNotIn('name', ['Student', 'Parent', 'Guardian'])
            ->where(function ($q) use ($schoolId) {
                if ($schoolId) {
                    $q->whereNull('school_id')->orWhere('school_id', $schoolId);
                }
            })
            ->orderBy('name', 'ASC')
            ->get()
            ->unique('name');

        $sessionYears = SessionYear::owner()->orderBy('id', 'DESC')->get();
        $defaultSessionYear = app(CachingService::class)->getDefaultSessionYear();
        if (!$defaultSessionYear && $sessionYears->count()) {
            $defaultSessionYear = $sessionYears->first();
        }

        if ($sessionYears->isEmpty()) {
            $fallbackSy = new SessionYear([
                'id' => 0,
                'name' => date('Y') . '-' . (date('Y') + 1),
                'start_date' => date('Y') . '-04-01',
                'end_date' => (date('Y') + 1) . '-03-31',
                'default' => 1
            ]);
            $sessionYears = collect([$fallbackSy]);
            $defaultSessionYear = $fallbackSy;
        }

        $months = sessionYearWiseMonth();
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        if ($defaultSessionYear) {
            $startMonth = (int)date('n', strtotime($defaultSessionYear->start_date));
            $startYear = (int)date('Y', strtotime($defaultSessionYear->start_date));
            $endYear = (int)date('Y', strtotime($defaultSessionYear->end_date));
            if ($currentMonth >= $startMonth) {
                $currentYear = $startYear;
            } else {
                $currentYear = $endYear;
            }
        }

        $criteriaList = StaffPerformanceEvaluation::getDefaultCriteria();

        return view('staff_performance.index', compact('roles', 'sessionYears', 'defaultSessionYear', 'months', 'currentMonth', 'currentYear', 'criteriaList'));
    }

    public function list(Request $request)
    {
        if (!Auth::user()->can('staff-performance-list') && !Auth::user()->can('staff-performance-create') && !Auth::user()->can('payroll-list') && !Auth::user()->can('payroll-create')) {
            ResponseService::noAnyPermissionThenSendJson(['staff-performance-list', 'staff-performance-create', 'payroll-list', 'payroll-create']);
        }

        $month = $request->input('month', Carbon::now()->month);
        $year = $request->input('year', Carbon::now()->year);
        $sessionYearId = $request->input('session_year_id');
        $roleId = $request->input('role_id');
        $search = $request->input('search');
        $schoolId = Auth::user()->school_id;

        $offset = $request->input('offset', 0);
        $limit = $request->input('limit', 10);
        $sort = $request->input('sort', 'name');
        $order = $request->input('order', 'ASC');

        $query = Staff::with(['user.roles', 'staffSalary.payrollSetting'])
            ->join('users', 'staffs.user_id', '=', 'users.id')
            ->select('staffs.*')
            ->whereNull('users.deleted_at');

        if ($schoolId) {
            $query->where('users.school_id', $schoolId);
        }

        if ($roleId) {
            $selectedRole = is_numeric($roleId) ? Role::find($roleId) : Role::where('name', $roleId)->first();
            $roleName = $selectedRole ? $selectedRole->name : $roleId;
            $query->whereHas('user.roles', function ($q) use ($roleId, $roleName) {
                $q->where(function ($sub) use ($roleId, $roleName) {
                    if (is_numeric($roleId)) {
                        $sub->where('roles.id', $roleId);
                    }
                    if ($roleName) {
                        $sub->orWhere('roles.name', $roleName);
                    }
                });
            });
        } else {
            $query->whereHas('user.roles', function ($q) {
                $q->whereNotIn('roles.name', ['Student', 'Parent', 'Guardian']);
            });
        }

        if ($search) {
            $query->where(function ($sub) use ($search) {
                $sub->where('users.first_name', 'like', "%{$search}%")
                    ->orWhere('users.last_name', 'like', "%{$search}%")
                    ->orWhere('users.email', 'like', "%{$search}%")
                    ->orWhereRaw("concat(users.first_name, ' ', users.last_name) like ?", ["%{$search}%"]);
            });
        }

        $total = $query->count();

        if ($sort === 'name' || $sort === 'first_name') {
            $query->orderBy('users.first_name', $order)->orderBy('users.last_name', $order);
        } elseif ($sort === 'id') {
            $query->orderBy('staffs.id', $order);
        } else {
            $query->orderBy('staffs.id', $order);
        }

        $staffMembers = $query->skip($offset)->take($limit)->get();
        $staffIds = $staffMembers->pluck('id')->toArray();

        $evalQuery = StaffPerformanceEvaluation::whereIn('staff_id', $staffIds)
            ->where('month', $month)
            ->where('year', $year);

        if ($schoolId) {
            $evalQuery->where('school_id', $schoolId);
        }

        $evaluations = $evalQuery->get()->keyBy('staff_id');

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
                $scoreText = "<span class='badge badge-info'>{$positive} / 10</span>";
                $statusBadge = "<span class='badge badge-success'>" . __('Evaluated') . "</span>";
            } else {
                $positive = 10;
                $negative = 0;
                $earnedIncentive = $baseIncentive;
                $scoreText = "<span class='badge badge-secondary'>10 / 10 (" . __('Default') . ")</span>";
                $statusBadge = "<span class='badge badge-warning'>" . __('Pending') . "</span>";
            }

            $rolesText = $user->roles->pluck('name')->join(', ');

            $operate = '<button type="button" class="btn btn-sm btn-theme evaluate-staff-btn" '
                . 'data-staff-id="' . $staff->id . '" '
                . 'data-staff-name="' . htmlspecialchars($user->full_name) . '" '
                . 'data-session-year-id="' . $sessionYearId . '" '
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
            'session_year_id' => 'nullable|integer',
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

            $attributes = [
                'criteria' => $criteriaPayload,
                'positive_points' => $positivePoints,
                'negative_points' => $negativePoints,
                'total_points' => 10,
                'score_percentage' => $scorePercentage,
                'evaluated_by' => Auth::id(),
                'remarks' => $request->remarks ?? null,
            ];

            if ($request->filled('session_year_id') && Schema::hasColumn('staff_performance_evaluations', 'session_year_id')) {
                $attributes['session_year_id'] = $request->session_year_id;
            }

            StaffPerformanceEvaluation::updateOrCreate(
                [
                    'school_id' => Auth::user()->school_id,
                    'staff_id' => $request->staff_id,
                    'month' => $request->month,
                    'year' => $request->year,
                ],
                $attributes
            );

            DB::commit();
            return ResponseService::successResponse('Performance Evaluation Saved Successfully');
        } catch (Throwable $th) {
            DB::rollBack();
            ResponseService::logErrorResponse($th);
            return ResponseService::errorResponse();
        }
    }
}
