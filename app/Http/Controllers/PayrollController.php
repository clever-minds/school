<?php

namespace App\Http\Controllers;

use App\Repositories\Expense\ExpenseInterface;
use App\Repositories\Leave\LeaveInterface;
use App\Repositories\LeaveMaster\LeaveMasterInterface;
use App\Repositories\SchoolSetting\SchoolSettingInterface;
use App\Repositories\SessionYear\SessionYearInterface;
use App\Repositories\Staff\StaffInterface;
use App\Repositories\StaffPayroll\StaffPayrollInterface;
use App\Repositories\StaffSalary\StaffSalaryInterface;
use App\Services\BootstrapTableService;
use App\Services\CachingService;
use App\Services\ResponseService;
use App\Services\SessionYearsTrackingsService;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PDF;
use Throwable;

class PayrollController extends Controller {
    private SessionYearInterface $sessionYear;
    private StaffInterface $staff;
    private ExpenseInterface $expense;
    private LeaveMasterInterface $leaveMaster;
    private CachingService $cache;
    private SchoolSettingInterface $schoolSetting;
    private LeaveInterface $leave;
    private SessionYearInterface $sessionYearInterface;
    private StaffSalaryInterface $staffSalary;
    private StaffPayrollInterface $staffPayroll;
    private SessionYearsTrackingsService $sessionYearsTrackingsService;

    public function __construct(SessionYearInterface $sessionYear, StaffInterface $staff, ExpenseInterface $expense, LeaveMasterInterface $leaveMaster, CachingService $cache, SchoolSettingInterface $schoolSetting, LeaveInterface $leave, SessionYearInterface $sessionYearInterface, StaffSalaryInterface $staffSalary, StaffPayrollInterface $staffPayroll, SessionYearsTrackingsService $sessionYearsTrackingsService) {
        $this->sessionYear = $sessionYear;
        $this->staff = $staff;
        $this->expense = $expense;
        $this->leaveMaster = $leaveMaster;
        $this->cache = $cache;
        $this->schoolSetting = $schoolSetting;
        $this->leave = $leave;
        $this->sessionYearInterface = $sessionYearInterface;
        $this->staffSalary = $staffSalary;
        $this->staffPayroll = $staffPayroll;
        $this->sessionYearsTrackingsService = $sessionYearsTrackingsService;
    }

    public function index() {
        //
        ResponseService::noFeatureThenRedirect('Expense Management');
        ResponseService::noPermissionThenRedirect('payroll-list');

        $sessionYear = $this->sessionYear->builder()->orderBy('start_date', 'ASC')->first();
        $sessionYear = date('Y', strtotime($sessionYear->start_date));
        // Get months starting from session year
        $months = sessionYearWiseMonth();
        $schoolSetting = $this->cache->getSchoolSettings();

        return view('payroll.index', compact('sessionYear', 'months', 'schoolSetting'));
    }

    public function create() {
        //
        ResponseService::noFeatureThenRedirect('Expense Management');
        ResponseService::noPermissionThenRedirect('payroll-create');
    }

    public function store(Request $request) {
   
        ResponseService::noFeatureThenSendJson('Expense Management');
        ResponseService::noPermissionThenSendJson('payroll-create');

        $request->validate([
            'net_salary' => 'required',

            'user_id' => 'required'
        ], [
            'net_salary.required' => trans('no_records_found'),
            'user_id.required' => trans('Please select at least one record')
        ]);

        try {
            DB::beginTransaction();
            $user_ids = explode(",",$request->user_id);
            
            $selectedMonth = $request->month;
            $selectedYear = $request->year;
            // Define the start and end dates
            $startDate = Carbon::createFromFormat('Y-m', "$selectedYear-$selectedMonth")->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();

            $sessionYearInterface = $this->sessionYearInterface->builder()->where(function ($query) use ($startDate, $endDate) {
                $query->where(function ($query) use ($startDate, $endDate) {
                    $query->where('start_date', '<=', $endDate)
                        ->where('end_date', '>=', $startDate);
                });
            })->first();

            if (!$sessionYearInterface) {
                ResponseService::errorResponse('Session year not found');
            }

            $data = array();
            $staff_payroll_data  = array();
            foreach ($user_ids as $key => $user_id) {
                $data = [
                    'title'           => Carbon::create()->month($request->month)->format('F') . ' - ' . $request->year,
                    'description'     => 'Salary',
                    'month'           => $request->month,
                    'year'            => $request->year,
                    'staff_id'        => $user_id,
                    'basic_salary'    => $request->basic_salary[$user_id],
                    'paid_leaves'     => $request->paid_leave[$user_id],
                    'amount'          => $request->net_salary[$user_id],
                    'session_year_id' => $sessionYearInterface->id,
                    'date'            => $endDate->format('Y-m-d'),
                ];

                $expense = $this->expense->updateOrCreate(['staff_id' => $data['staff_id'], 'month' => $data['month'], 'year' => $data['year']], ['amount' => $data['amount'], 'session_year_id' => $data['session_year_id'],'basic_salary' => $data['basic_salary'],'date' => $data['date'],'title' => $data['title'],'paid_leaves' => $data['paid_leaves'],'description' => $data['description']]);

                $this->sessionYearsTrackingsService->storeSessionYearsTracking('App\Models\Expense', $expense->id, Auth::user()->id, $sessionYearInterface->id, Auth::user()->school_id, null);

                $staffSalary = $this->staffSalary->builder()->with('payrollSetting')->where('staff_id',$user_id)->get();
                if (count($staffSalary)) {
                    $evaluation = \App\Models\StaffPerformanceEvaluation::where('staff_id', $user_id)
                        ->where(function ($q) use ($request) {
                            $q->where('month', (int)$request->month)->orWhere('month', $request->month);
                        })
                        ->where(function ($q) use ($request) {
                            $q->where('year', (int)$request->year)->orWhere('year', $request->year);
                        })
                        ->first();

                    foreach ($staffSalary as $key => $payroll) {
                        $finalAmount = $payroll->amount;
                        $finalPercentage = $payroll->percentage;

                        $isPerf = $payroll->payrollSetting && (
                            $payroll->payrollSetting->type == 'performance' ||
                            ($payroll->payrollSetting->type == 'allowance' && stripos($payroll->payrollSetting->name, 'performance') !== false)
                        );

                        if ($isPerf) {
                            $baseAmount = $payroll->amount;
                            if (!$baseAmount && $payroll->percentage) {
                                $baseAmount = ($data['basic_salary'] * $payroll->percentage) / 100;
                            }
                            if ($evaluation) {
                                $earned = ($evaluation->positive_points / 10) * $baseAmount;
                                $deducted = $baseAmount - $earned;
                                $finalAmount = $earned;
                                $finalPercentage = null;
                            } else {
                                $finalAmount = $baseAmount;
                            }
                        }

                        $staff_payroll_data[] = [
                            'expense_id' => $expense->id,
                            'payroll_setting_id' => $payroll->payroll_setting_id,
                            'amount' => $finalAmount,
                            'percentage' => $finalPercentage,
                            'school_id' => Auth::user()->school_id,
                        ];   
                    }
                }
            }
            
            if (!empty($staff_payroll_data)) {
                $this->staffPayroll->upsert($staff_payroll_data, ['expense_id', 'payroll_setting_id'], ['amount', 'percentage', 'school_id']);
            }
            $user = $this->staff->builder()->whereIn('id', $user_ids)->pluck('user_id');
          
            $title = 'Payroll Update !!!' ;
            $body = "Your Payroll has been Updated.";
            $type = "assignment";

            DB::commit();
            send_notification($user, $title, $body, $type);

            ResponseService::successResponse('Data Stored Successfully');
        } catch (Throwable $e) {
            if (Str::contains($e->getMessage(), ['does not exist', 'file_get_contents'])) {
                DB::commit();
                ResponseService::warningResponse("Data Stored successfully. But App push notification not sent.");
            } else {
                DB::rollBack();
                ResponseService::logErrorResponse($e, 'Payroll Controller -> Store method');
                ResponseService::errorResponse();
            }
        }
    }

    public function show() {
        ResponseService::noFeatureThenRedirect('Expense Management');
        ResponseService::noPermissionThenRedirect('payroll-list');

        $sort = request('sort', 'rank');
        $order = request('order', 'ASC');
        $search = request('search');
        $month = request('month');
        $year = request('year');

        

        $startDate = Carbon::create(null, $month, 1)->startOfMonth()->format('Y-m-d');
        $endDate = Carbon::create(null, $month, 1)->endOfMonth()->format('Y-m-d');

        $leaveMaster = $this->leaveMaster->builder()->whereHas('session_year', function ($q) use ($month, $year) {
            $q->where(function ($q) use ($month, $year) {
                $q->whereMonth('start_date', '<=', $month)->whereYear('start_date', $year);
            })->orWhere(function ($q) use ($month, $year) {
                $q->whereMonth('start_date', '>=', $month)->whereYear('end_date', '<=', $year);
            });
        })->first();

        $sql = $this->staff->builder()->with(['user.roles','staffSalary.payrollSetting', 'expense.staff_payroll.payroll_setting'])->whereHas('user', function ($q) {
            $q->whereNull('deleted_at')->Owner();
        })->when($search, function ($query) use ($search) {
            $query->where(function ($query) use ($search) {
                $query->orwhereHas('user', function ($q) use ($search) {
                    $q->where('first_name', 'LIKE', "%$search%")->orwhere('last_name', 'LIKE', "%$search%");
                });
            });
        });

        $total = $sql->count();

        $sql->orderBy($sort, $order);
        $res = $sql->get();

        $userIds = $res->pluck('user.id')->toArray();
        $staffIds = $res->pluck('id')->toArray();
        $allAttendances = \App\Models\StaffAttendance::whereIn('user_id', $userIds)
            ->whereBetween('date', [$startDate, $endDate])
            ->get()
            ->groupBy('user_id');

        $allLeaves = \App\Models\LeaveDetail::whereHas('leave', function($q) use ($userIds) {
                $q->whereIn('user_id', $userIds)->where('status', 1);
            })
            ->whereBetween('date', [$startDate, $endDate])
            ->with('leave:id,user_id')
            ->get()
            ->groupBy('leave.user_id');

        $allEvaluations = \App\Models\StaffPerformanceEvaluation::whereIn('staff_id', $staffIds)
            ->where('month', $month)
            ->where('year', $year)
            ->get()
            ->keyBy('staff_id');

        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $no = 1;

        // Calculate non-working days (Sundays + Holidays)
        $startOfMonthObj = Carbon::create($year, $month, 1);
        $endOfMonthObj = $startOfMonthObj->copy()->endOfMonth();
        $startOfMonthStr = $startOfMonthObj->format('Y-m-d');
        $endOfMonthStr = $endOfMonthObj->format('Y-m-d');
        $daysInMonth = $startOfMonthObj->daysInMonth;
        
        $holidays = \App\Models\Holiday::where(function($q) use ($startOfMonthStr, $endOfMonthStr) {
            $q->whereBetween('date', [$startOfMonthStr, $endOfMonthStr])
              ->orWhereBetween('end_date', [$startOfMonthStr, $endOfMonthStr])
              ->orWhere(function($q2) use ($startOfMonthStr, $endOfMonthStr) {
                  $q2->where('date', '<', $startOfMonthStr)->where('end_date', '>', $endOfMonthStr);
              });
        })->get();

        $holidayDates = [];
        foreach($holidays as $holiday) {
            $start = Carbon::parse($holiday->getRawOriginal('date'));
            $end = $holiday->getRawOriginal('end_date') ? Carbon::parse($holiday->getRawOriginal('end_date')) : $start->copy();
            for($d = $start->copy(); $d->lte($end); $d->addDay()) {
                if ($d->month == $month && $d->year == $year) {
                    $holidayDates[] = $d->format('Y-m-d');
                }
            }
        }

        $sundayDates = [];
        for($d = $startOfMonthObj->copy(); $d->lte($endOfMonthObj); $d->addDay()) {
            if ($d->isSunday()) {
                $sundayDates[] = $d->format('Y-m-d');
            }
        }
        $nonWorkingDates = array_unique(array_merge($holidayDates, $sundayDates));
        $total_working_days = $daysInMonth - count($nonWorkingDates);

        foreach ($res as $row) {
            $tempRow = $row->toArray();
            $tempRow['no'] = $no++;
            $salary = (float)$row->salary;
            
            $userAttendances = $allAttendances->has($row->user->id) ? $allAttendances[$row->user->id] : collect();
            $userLeaves = $allLeaves->has($row->user->id) ? $allLeaves[$row->user->id]->keyBy('date') : collect();
            
            $total_present = 0;
            $total_late = 0;

            $attendance_map = [];
            // Process Attendance records (Only P, W and H give present marks)
            foreach ($userAttendances as $att) {
                $dateStr = Carbon::parse($att->date)->format('Y-m-d');
                
                $effectiveStatus = $att->status;
                if ($att->type === 'Work From Home') {
                    $effectiveStatus = 1;
                } elseif ($att->type === 'Leave') {
                    $effectiveStatus = 4;
                } elseif ($att->status == 3) {
                    $effectiveStatus = 3; // Explicit Half Day (Preserved)
                } elseif ($att->status == 4) {
                    $effectiveStatus = 4; // Explicit Absent
                } elseif ($att->check_in && $att->check_out) {
                    $effectiveStatus = \App\Models\StaffAttendance::calculateStatusOnCheckOut($att->check_in, $att->check_out);
                } elseif ($att->check_in && empty($att->check_out)) {
                    $effectiveStatus = 3; // Missing punch out counted as Half Day
                }

                $attendance_map[$dateStr] = $effectiveStatus;

                if ($effectiveStatus == 1) { // 1 = Present / WFH
                    $total_present += 1;
                } elseif ($effectiveStatus == 3) { // 3 = Half Day (0.5 present, 0.5 absent)
                    $total_present += 0.5;
                } elseif ($effectiveStatus == 2) { // 2 = Late
                    $total_present += 1;
                    $total_late += 1;
                }
            }

            // Check approved leave records for days not explicitly in attendance map
            foreach ($userLeaves as $dateStr => $leaveDetail) {
                if (!isset($attendance_map[$dateStr])) {
                    if (stripos($leaveDetail->type ?? '', 'Half') !== false) {
                        $attendance_map[$dateStr] = 3;
                        $total_present += 0.5;
                    } else {
                        $attendance_map[$dateStr] = 4;
                    }
                }
            }

            $monthDates = [];
            for($d = $startOfMonthObj->copy(); $d->lte($endOfMonthObj); $d->addDay()) {
                $dateStr = $d->format('Y-m-d');
                $monthDates[] = [
                    'date' => $dateStr,
                    'is_working' => !in_array($dateStr, $nonWorkingDates),
                ];
            }

            $sandwiched_days = 0;
            $blocks = [];
            $current_block = [];
            foreach ($monthDates as $index => $dayInfo) {
                if (!$dayInfo['is_working']) {
                    $current_block[] = $index;
                } else {
                    if (!empty($current_block)) {
                        $blocks[] = $current_block;
                        $current_block = [];
                    }
                }
            }
            if (!empty($current_block)) {
                $blocks[] = $current_block;
            }

            foreach ($blocks as $block) {
                $first_index = $block[0];
                $last_index = end($block);
                
                $preceding_absent = false;
                if ($first_index > 0) {
                    $prev_index = $first_index - 1;
                    $prev_date = $monthDates[$prev_index]['date'];
                    $status = isset($attendance_map[$prev_date]) ? $attendance_map[$prev_date] : null;
                    if (!in_array($status, [1, 2, 3]) && !$userLeaves->has($prev_date)) {
                        $preceding_absent = true;
                    }
                }
                
                $following_absent = false;
                if ($last_index < count($monthDates) - 1) {
                    $next_index = $last_index + 1;
                    $next_date = $monthDates[$next_index]['date'];
                    $status = isset($attendance_map[$next_date]) ? $attendance_map[$next_date] : null;
                    if (!in_array($status, [1, 2, 3]) && !$userLeaves->has($next_date)) {
                        $following_absent = true;
                    }
                }
                
                if ($preceding_absent && $following_absent) {
                    $sandwiched_days += count($block);
                }
            }

            // Total absent days (each half day = 0.5)
            $total_leave = max(0, $total_working_days - $total_present) + $sandwiched_days;

            // Designation from User Roles
            $designation = '';
            if ($row->user && $row->user->roles) {
                $designation = $row->user->roles->pluck('name')->implode(', ');
            }
            if (empty($designation)) {
                $designation = $row->qualification ?? '-';
            }

            // Performance Incentive and other settings
            $performanceIncentiveEarned = 0;
            $performanceIncentiveDeducted = 0;
            $baseIncentiveTotal = 0;
            $hasPerformanceSetting = false;
            $otherAllowances = 0;
            $deductionSettingsAmount = 0;

            $evaluation = $allEvaluations->get($row->id);

            foreach ($row->staffSalary as $salaryItem) {
                $payrollSetting = $salaryItem->payrollSetting;
                if (!$payrollSetting) {
                    continue;
                }

                $isPerformance = ($payrollSetting->type === 'performance') || 
                                 ($payrollSetting->type === 'allowance' && stripos($payrollSetting->name, 'performance') !== false);

                if ($isPerformance) {
                    $hasPerformanceSetting = true;
                    $baseIncentive = 0;
                    if (isset($salaryItem->percentage) && $salaryItem->percentage > 0) {
                        $baseIncentive = ((float)$salaryItem->percentage / 100) * $salary;
                    } elseif (isset($salaryItem->amount)) {
                        $baseIncentive = (float)$salaryItem->amount;
                    }
                    $baseIncentiveTotal += $baseIncentive;

                    if ($evaluation) {
                        // Formula: positive_points / 10 * base
                        $earned = round(($evaluation->positive_points / 10) * $baseIncentive, 2);
                        $deducted = $baseIncentive - $earned;
                        $performanceIncentiveEarned += $earned;
                        $performanceIncentiveDeducted += $deducted;
                    } else {
                        $performanceIncentiveEarned += $baseIncentive;
                    }
                } elseif ($payrollSetting->type === 'allowance') {
                    if (isset($salaryItem->percentage) && $salaryItem->percentage > 0) {
                        $otherAllowances += ((float)$salaryItem->percentage / 100) * $salary;
                    } elseif (isset($salaryItem->amount)) {
                        $otherAllowances += (float)$salaryItem->amount;
                    }
                } elseif ($payrollSetting->type === 'deduction') {
                    if (isset($salaryItem->percentage) && $salaryItem->percentage > 0) {
                        $deductionSettingsAmount += ((float)$salaryItem->percentage / 100) * $salary;
                    } elseif (isset($salaryItem->amount)) {
                        $deductionSettingsAmount += (float)$salaryItem->amount;
                    }
                }
            }

            // Check if Payroll already generated (Expense record exists)
            $expense = null;
            if (isset($row->expense)) {
                $expense = $row->expense()->where('month', $month)->where('year', $year)->first();
            }

            if ($expense) {
                $status = 1;
                $operate = BootstrapTableService::button('fa fa-file-o', url('payroll/slip/'.$expense->id), ['btn-gradient-info'], ['title' => trans("slip"),'target' => '_blank']);
                $salary = (float)$expense->getRawOriginal('basic_salary');
                $paid_leaves = $expense->paid_leaves !== null ? (float)$expense->paid_leaves : 0;

                // Sync performance evaluation if present
                if ($evaluation && count($expense->staff_payroll)) {
                    foreach ($expense->staff_payroll as $staff_payroll) {
                        $ps = $staff_payroll->payroll_setting;
                        $isPerf = $ps && (
                            $ps->type == 'performance' ||
                            ($ps->type == 'allowance' && stripos($ps->name, 'performance') !== false)
                        );
                        if ($isPerf) {
                            $baseIncentive = 0;
                            $staffSalaryItem = $row->staffSalary->where('payroll_setting_id', $staff_payroll->payroll_setting_id)->first();
                            if ($staffSalaryItem) {
                                if ($staffSalaryItem->amount) {
                                    $baseIncentive = (float)$staffSalaryItem->amount;
                                } elseif ($staffSalaryItem->percentage) {
                                    $baseIncentive = ((float)$staffSalaryItem->percentage / 100) * (float)$expense->basic_salary;
                                }
                            }
                            if (!$baseIncentive) {
                                $baseIncentive = (float)$staff_payroll->amount;
                            }

                            $earned = round(($evaluation->positive_points / 10) * $baseIncentive, 2);
                            $deducted = $baseIncentive - $earned;

                            if (abs((float)$staff_payroll->amount - $baseIncentive) < 0.01 && $deducted > 0) {
                                $staff_payroll->amount = $earned;
                                $staff_payroll->save();

                                $expense->amount = (float)$expense->amount - $deducted;
                                $expense->save();
                            }
                            $performanceIncentiveEarned = $earned;
                        }
                    }
                }
            } else {
                $status = 0;
                $operate = '-';
                $salary = (float)$row->salary;
                $paid_leaves = $leaveMaster ? (float)($leaveMaster->leaves ?? 0) : 0;
            }

            // School Sheet Formulas
            // 1. Total Gross Salary = Basic Sa + Perfo + Other Allowances
            $total_gross = round($salary + $performanceIncentiveEarned + $otherAllowances, 2);

            // 2. Per Day Salary Rate = Basic Salary / Days in Month
            $per_day = round($salary / $daysInMonth, 2);

            // 3. Working Days = Days in Month
            $working_days = $daysInMonth;

            // 4. Absent Days (each half day = 0.5)
            $absent_days = round((float)$total_leave, 1);

            // 5. Unpaid Leaves = max(0, Absent - Paid)
            $unpaid_leave = max(0, round($absent_days - $paid_leaves, 1));

            // 6. Attended Days = Working Days - Unpaid Leaves
            $attended_days = round(max(0, $working_days - $unpaid_leave), 1);

            // 7. Leave / Salary Deduction = Unpaid Leaves * Per Day
            $salary_deduction = round($unpaid_leave * $per_day, 2);

            // 8. Other Deductions
            $other_deductions = round($deductionSettingsAmount, 2);
            if ($expense) {
                $expectedNet = round($total_gross - $salary_deduction - $deductionSettingsAmount, 2);
                if ((float)$expense->amount < $expectedNet) {
                    $other_deductions = round($deductionSettingsAmount + ($expectedNet - (float)$expense->amount), 2);
                }
                $net_salary = round((float)$expense->amount, 2);
            } else {
                $net_salary = round(max(0, $total_gross - $salary_deduction - $other_deductions), 2);
            }

            // Construct Row Data
            $tempRow['id'] = $row->id;
            $tempRow['user_full_name'] = $row->user->full_name;
            $tempRow['user'] = ['full_name' => $row->user->full_name];
            $tempRow['designation'] = $designation;
            $tempRow['salary'] = $salary;
            $tempRow['basic_salary_formatted'] = number_format($salary, 2);
            $tempRow['perfo'] = $performanceIncentiveEarned;
            $tempRow['perfo_formatted'] = number_format($performanceIncentiveEarned, 2);
            if ($hasPerformanceSetting) {
                if ($evaluation) {
                    $tempRow['perfo_html'] = '<span class="export-raw">' . number_format($performanceIncentiveEarned, 2) . '</span><br><small class="text-success font-weight-bold">(' . $evaluation->positive_points . '/10 pts)</small>';
                } else {
                    $tempRow['perfo_html'] = '<span class="export-raw">' . number_format($performanceIncentiveEarned, 2) . '</span><br><small class="text-warning font-weight-bold">(Pending)</small>';
                }
            } else {
                $tempRow['perfo_html'] = '<span class="export-raw">0.00</span>';
            }
            $tempRow['other_allowances'] = number_format($otherAllowances, 2);
            $tempRow['allowances'] = number_format($performanceIncentiveEarned + $otherAllowances, 2);
            $tempRow['per_day'] = number_format($per_day, 2);
            $tempRow['total_gross'] = number_format($total_gross, 2);
            $tempRow['working_days'] = $working_days;
            $tempRow['absent_days'] = (float)$absent_days;
            $tempRow['total_leaves'] = (float)$absent_days;
            $tempRow['paid_leaves'] = (float)$paid_leaves;
            $tempRow['pen'] = $total_late > 0 ? $total_late : '-';
            $tempRow['attended_days'] = (float)$attended_days;
            $tempRow['salary_deduction'] = number_format($salary_deduction, 2);
            $tempRow['other_deductions'] = number_format($other_deductions, 2);
            $tempRow['deductions'] = number_format($other_deductions, 2);
            $tempRow['net_salary'] = $net_salary;
            $tempRow['net_salary_formatted'] = number_format($net_salary, 2);
            $tempRow['signature'] = '';
            $tempRow['status'] = $status;
            $tempRow['operate'] = $operate;

            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }

    public function slip_index()
    {
        ResponseService::noFeatureThenRedirect('Expense Management');
        try {
            $sessionYear = $this->sessionYear->builder()->pluck('name','id');
            $currentSessionYear = $this->cache->getDefaultSessionYear();

            $sessionYears = $this->sessionYear->builder()->orderBy('start_date', 'ASC')->get();

            return view('payroll.list',compact('sessionYear','currentSessionYear','sessionYears'));
        } catch (\Throwable $th) {
            ResponseService::logErrorResponse($th, 'Payroll Controller -> Slip Index method');
            ResponseService::errorResponse();
        }
    }

    public function slip_list()
    {
        ResponseService::noFeatureThenRedirect('Expense Management');

        $offset = request('offset', 0);
        $limit = request('limit', 10);
        $sort = request('sort', 'rank');
        $order = request('order', 'ASC');
        $search = request('search');
        $sessionYearId = request('session_year_id');

        $sql = $this->expense->builder()->where('staff_id',Auth::user()->staff->id)
        ->where(function($q) use($search){
            $q->when($search, function($q) use($search) {
                $q->where('title','LIKE',"%$search%")
                ->orWhere('basic_salary','LIKE',"%$search%")
                ->orWhere('amount','LIKE',"%$search%")
                ->where('staff_id',Auth::user()->staff->id);
            });
        })
        
        ->when($sessionYearId, function($q) use($sessionYearId) {
            $q->where('session_year_id',$sessionYearId);
        })
        ->where('staff_id',Auth::user()->staff->id);

        $total = $sql->count();

        $sql->orderBy($sort, $order)->skip($offset)->take($limit);
        $res = $sql->get();

        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $no = 1;

        foreach ($res as $row) {
            $operate = BootstrapTableService::button('fa fa-file-o', url('payroll/slip/'.$row->id), ['btn-gradient-info'], ['title' => trans("slip"),'target' => '_blank']);
            $tempRow = $row->toArray();
            $tempRow['no'] = $no++;
            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }

    public function slip($id = null)
    {
        ResponseService::noFeatureThenRedirect('Expense Management');
        try {
            $schoolSetting = $this->cache->getSchoolSettings();
            $data = explode("storage/", $schoolSetting['horizontal_logo'] ?? '');
            $schoolSetting['horizontal_logo'] = end($data);

            if ($schoolSetting['horizontal_logo'] == null) {
                $systemSettings = $this->cache->getSystemSettings();
                $data = explode("storage/", $systemSettings['horizontal_logo'] ?? '');
                $schoolSetting['horizontal_logo'] = end($data);
            }

            // Salary
            $salary = $this->expense->builder()->with('staff.user:id,first_name,last_name','staff_payroll.payroll_setting')->where('id',$id)->first();
            if (!$salary) {
                return redirect()->back()->with('error',trans('no_data_found'));
            }
            // Get total leaves from StaffAttendance and LeaveDetail
            $startDate = Carbon::create($salary->year, $salary->month, 1)->startOfMonth()->format('Y-m-d');
            $endDate = Carbon::create($salary->year, $salary->month, 1)->endOfMonth()->format('Y-m-d');
            
            $userAttendances = \App\Models\StaffAttendance::where('user_id', $salary->staff->user_id)
                ->whereBetween('date', [$startDate, $endDate])
                ->get();

            $userLeaves = \App\Models\LeaveDetail::whereHas('leave', function($q) use ($salary) {
                    $q->where('user_id', $salary->staff->user_id)->where('status', 1);
                })
                ->whereBetween('date', [$startDate, $endDate])
                ->get()
                ->keyBy('date');

            $daysInMonth = Carbon::create($salary->year, $salary->month, 1)->daysInMonth;
            
            // Calculate non-working days
            $startOfMonthObj = Carbon::create($salary->year, $salary->month, 1);
            $endOfMonthObj = $startOfMonthObj->copy()->endOfMonth();
            
            $holidays = \App\Models\Holiday::where(function($q) use ($startDate, $endDate) {
                $q->whereBetween('date', [$startDate, $endDate])
                  ->orWhereBetween('end_date', [$startDate, $endDate])
                  ->orWhere(function($q2) use ($startDate, $endDate) {
                      $q2->where('date', '<', $startDate)->where('end_date', '>', $endDate);
                  });
            })->get();

            $holidayDates = [];
            foreach($holidays as $holiday) {
                $start = Carbon::parse($holiday->getRawOriginal('date'));
                $end = $holiday->getRawOriginal('end_date') ? Carbon::parse($holiday->getRawOriginal('end_date')) : $start->copy();
                for($d = $start->copy(); $d->lte($end); $d->addDay()) {
                    if ($d->month == $salary->month && $d->year == $salary->year) {
                        $holidayDates[] = $d->format('Y-m-d');
                    }
                }
            }

            $sundayDates = [];
            for($d = $startOfMonthObj->copy(); $d->lte($endOfMonthObj); $d->addDay()) {
                if ($d->isSunday()) {
                    $sundayDates[] = $d->format('Y-m-d');
                }
            }
            $nonWorkingDates = array_unique(array_merge($holidayDates, $sundayDates));
            $total_working_days = $daysInMonth - count($nonWorkingDates);

            $total_present = 0;

            $attendance_map = [];
            foreach ($userAttendances as $att) {
                $dateStr = Carbon::parse($att->date)->format('Y-m-d');
                
                $effectiveStatus = $att->status;
                $effectiveStatus = $att->status;
                if ($att->type === 'Work From Home') {
                    $effectiveStatus = 1;
                } elseif ($att->type === 'Leave') {
                    $effectiveStatus = 4;
                } elseif ($att->status == 3) {
                    $effectiveStatus = 3; // Explicit Half Day (Preserved)
                } elseif ($att->status == 4) {
                    $effectiveStatus = 4; // Explicit Absent
                } elseif ($att->check_in && $att->check_out) {
                    $effectiveStatus = \App\Models\StaffAttendance::calculateStatusOnCheckOut($att->check_in, $att->check_out);
                } elseif ($att->check_in && empty($att->check_out)) {
                    $effectiveStatus = 3; // Missing punch out -> Half Day
                }

                $attendance_map[$dateStr] = $effectiveStatus;

                if ($effectiveStatus == 1) {
                    $total_present += 1;
                } elseif ($effectiveStatus == 3) {
                    $total_present += 0.5;
                }
            }

            // Check approved leave records for days not explicitly in attendance map
            foreach ($userLeaves as $dateStr => $leaveDetail) {
                if (!isset($attendance_map[$dateStr])) {
                    if (stripos($leaveDetail->type ?? '', 'Half') !== false) {
                        $attendance_map[$dateStr] = 3;
                        $total_present += 0.5;
                    } else {
                        $attendance_map[$dateStr] = 4;
                    }
                }
            }

            $monthDates = [];
            for($d = $startOfMonthObj->copy(); $d->lte($endOfMonthObj); $d->addDay()) {
                $dateStr = $d->format('Y-m-d');
                $monthDates[] = [
                    'date' => $dateStr,
                    'is_working' => !in_array($dateStr, $nonWorkingDates),
                ];
            }

            $sandwiched_days = 0;
            $blocks = [];
            $current_block = [];
            foreach ($monthDates as $index => $dayInfo) {
                if (!$dayInfo['is_working']) {
                    $current_block[] = $index;
                } else {
                    if (!empty($current_block)) {
                        $blocks[] = $current_block;
                        $current_block = [];
                    }
                }
            }
            if (!empty($current_block)) {
                $blocks[] = $current_block;
            }

            foreach ($blocks as $block) {
                $first_index = $block[0];
                $last_index = end($block);
                
                $preceding_absent = false;
                if ($first_index > 0) {
                    $prev_index = $first_index - 1;
                    $prev_date = $monthDates[$prev_index]['date'];
                    $status = isset($attendance_map[$prev_date]) ? $attendance_map[$prev_date] : null;
                    if (!in_array($status, [1, 2, 3]) && !$userLeaves->has($prev_date)) {
                        $preceding_absent = true;
                    }
                }
                
                $following_absent = false;
                if ($last_index < count($monthDates) - 1) {
                    $next_index = $last_index + 1;
                    $next_date = $monthDates[$next_index]['date'];
                    $status = isset($attendance_map[$next_date]) ? $attendance_map[$next_date] : null;
                    if (!in_array($status, [1, 2, 3]) && !$userLeaves->has($next_date)) {
                        $following_absent = true;
                    }
                }
                
                if ($preceding_absent && $following_absent) {
                    $sandwiched_days += count($block);
                }
            }

            $total_leave = max(0, $total_working_days - $total_present) + $sandwiched_days;

            $allow_leaves = 0;
            if ($salary) {
                $allow_leaves = $salary->paid_leaves;
            }

            $total_leaves = $total_leave;
            // Total days
            $days = Carbon::now()->year($salary->year)->month($salary->month)->daysInMonth;

            $performanceEvaluation = \App\Models\StaffPerformanceEvaluation::where(function($q) use ($salary) {
                    $q->where('staff_id', $salary->staff_id)
                      ->orWhere('staff_id', $salary->staff->user_id ?? 0);
                })
                ->where(function($q) use ($salary) {
                    $q->where('month', (int)$salary->month)->orWhere('month', $salary->month);
                })
                ->where(function($q) use ($salary) {
                    $q->where('year', (int)$salary->year)->orWhere('year', $salary->year);
                })
                ->first();

            if ($performanceEvaluation && count($salary->staff_payroll)) {
                foreach ($salary->staff_payroll as $payroll) {
                    $setting = $payroll->payroll_setting;
                    $isPerf = $setting && (
                        $setting->type === 'performance' ||
                        ($setting->type === 'allowance' && stripos($setting->name, 'performance') !== false)
                    );
                    if ($isPerf) {
                        $baseIncentive = 0;
                        $staffSalarySetting = \App\Models\StaffSalary::where('staff_id', $salary->staff_id)
                            ->where('payroll_setting_id', $payroll->payroll_setting_id)
                            ->first();
                        if ($staffSalarySetting) {
                            if ($staffSalarySetting->amount) {
                                $baseIncentive = (float)$staffSalarySetting->amount;
                            } elseif ($staffSalarySetting->percentage) {
                                $baseIncentive = ((float)$staffSalarySetting->percentage / 100) * (float)$salary->basic_salary;
                            }
                        }
                        if (!$baseIncentive) {
                            $baseIncentive = (float)$payroll->amount;
                        }

                        $earned = round(($performanceEvaluation->positive_points / 10) * $baseIncentive, 2);
                        $deducted = $baseIncentive - $earned;

                        if (abs((float)$payroll->amount - $earned) > 0.01) {
                            $payroll->amount = $earned;
                            $payroll->save();
                        }
                    }
                }
            }

            // Sync salary total amount with earned allowances, leave deductions and deductions
            $unpaidLeaves = max(0, (float)$total_leaves - (float)($salary->paid_leaves ?? 0));
            $lwpAmount = round(((float)$salary->basic_salary / ($days ?: 30)) * $unpaidLeaves, 2);

            $totalAllowance = 0;
            $totalDeduction = 0;
            foreach ($salary->staff_payroll as $sp) {
                if (!$sp->payroll_setting) continue;
                if ($sp->payroll_setting->type === 'allowance' || $sp->payroll_setting->type === 'performance') {
                    $totalAllowance += (float)$sp->amount;
                } elseif ($sp->payroll_setting->type === 'deduction') {
                    $totalDeduction += (float)$sp->amount;
                }
            }
            $calculatedNet = round((float)$salary->basic_salary + $totalAllowance - $lwpAmount - $totalDeduction, 2);
            if (abs((float)$salary->amount - $calculatedNet) > 0.01 && $calculatedNet > 0) {
                $salary->amount = $calculatedNet;
                $salary->save();
            }

            $pdf = PDF::loadView('payroll.slip',compact('schoolSetting','salary','total_leaves','days','allow_leaves', 'performanceEvaluation'))->setPaper('a5');
            return $pdf->stream($salary->title.'-'.$salary->staff->user->full_name.'.pdf');
        } catch (\Throwable $th) {
            ResponseService::logErrorResponse($th);
            ResponseService::errorResponse();
        }
    }

}
