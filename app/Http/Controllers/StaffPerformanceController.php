<?php

namespace App\Http\Controllers;

use App\Models\Staff;
use App\Models\StaffPerformanceEvaluation;
use App\Models\User;
use App\Services\BootstrapTableService;
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

        $roles = Role::whereNotIn('name', ['Student', 'Parent', 'Guardian'])->get();
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;
        $criteriaList = StaffPerformanceEvaluation::getDefaultCriteria();

        return view('staff_performance.index', compact('roles', 'currentMonth', 'currentYear', 'criteriaList'));
    }

    public function list(Request $request)
    {
        if (!Auth::user()->can('staff-performance-list') && !Auth::user()->can('staff-performance-create') && !Auth::user()->can('payroll-list') && !Auth::user()->can('payroll-create')) {
            ResponseService::noAnyPermissionThenSendJson(['staff-performance-list', 'staff-performance-create', 'payroll-list', 'payroll-create']);
        }

        $month = $request->input('month', Carbon::now()->month);
        $year = $request->input('year', Carbon::now()->year);
        $roleId = $request->input('role_id');
        $search = $request->input('search');
        $schoolId = Auth::user()->school_id;

        $offset = $request->input('offset', 0);
        $limit = $request->input('limit', 10);
        $sort = $request->input('sort', 'first_name');
        $order = $request->input('order', 'ASC');

        $query = User::where('school_id', $schoolId)
            ->where('status', 1)
            ->has('staff')
            ->with(['roles', 'staff.staffSalary.payrollSetting'])
            ->whereHas('roles', function ($q) use ($roleId) {
                $q->whereNotIn('name', ['Student', 'Parent']);
                if ($roleId) {
                    $q->where('id', $roleId);
                }
            })
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            });

        $total = $query->count();
        $users = $query->orderBy($sort, $order)->skip($offset)->take($limit)->get();

        $staffIds = $users->pluck('staff.id')->filter()->toArray();

        $evaluations = StaffPerformanceEvaluation::whereIn('staff_id', $staffIds)
            ->where('month', $month)
            ->where('year', $year)
            ->get()
            ->keyBy('staff_id');

        $rows = [];
        $no = $offset + 1;

        foreach ($users as $user) {
            $staff = $user->staff;
            if (!$staff) {
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
                $statusBadge = "<span class='badge badge-success'>Evaluated</span>";
            } else {
                $positive = 10;
                $negative = 0;
                $earnedIncentive = $baseIncentive;
                $scoreText = "<span class='badge badge-secondary'>10 / 10 (Default)</span>";
                $statusBadge = "<span class='badge badge-warning'>Pending</span>";
            }

            $rolesText = $user->roles->pluck('name')->join(', ');

            $operate = '<button type="button" class="btn btn-sm btn-theme evaluate-staff-btn" '
                . 'data-staff-id="' . $staff->id . '" '
                . 'data-staff-name="' . htmlspecialchars($user->full_name) . '" '
                . 'data-month="' . $month . '" '
                . 'data-year="' . $year . '" '
                . 'data-base-incentive="' . $baseIncentive . '" '
                . 'data-has-incentive="' . ($hasIncentive ? '1' : '0') . '" '
                . 'title="Evaluate Performance">'
                . '<i class="fa fa-star mr-1"></i> ' . ($evaluation ? 'Edit Feedback' : 'Evaluate')
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

            StaffPerformanceEvaluation::updateOrCreate(
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

            DB::commit();
            return ResponseService::successResponse('Performance Evaluation Saved Successfully');
        } catch (Throwable $th) {
            DB::rollBack();
            ResponseService::logErrorResponse($th);
            return ResponseService::errorResponse();
        }
    }
}
