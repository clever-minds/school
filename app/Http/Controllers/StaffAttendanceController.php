<?php

namespace App\Http\Controllers;

use App\Repositories\StaffAttendance\StaffAttendanceInterface;
use App\Services\ResponseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Throwable;

class StaffAttendanceController extends Controller
{
    private StaffAttendanceInterface $staffAttendance;

    public function __construct(StaffAttendanceInterface $staffAttendance)
    {
        $this->staffAttendance = $staffAttendance;
    }

    public function index()
    {
        ResponseService::noAnyPermissionThenSendJson(['staff-attendance-list']);
        return view('staff_attendance.index');
    }

    public function show(Request $request)
    {
        ResponseService::noAnyPermissionThenSendJson(['staff-attendance-list']);
        $offset = $request->input('offset', 0);
        $limit = $request->input('limit', 10);
        $sort = $request->input('sort', 'date');
        $order = $request->input('order', 'DESC');

        $sql = $this->staffAttendance->builder()->with('user:id,first_name,last_name,email,mobile')
            ->when($request->date, function ($q) use ($request) {
                $q->whereDate('date', $request->date);
            })
            ->when($request->staff_id, function ($q) use ($request) {
                $q->where('user_id', $request->staff_id);
            });

        $total = $sql->count();

        $sql->orderBy($sort, $order)->skip($offset)->take($limit);
        $res = $sql->get();

        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $tempRow = array();
        $no = 1;

        foreach ($res as $row) {
            $tempRow['id'] = $row->id;
            $tempRow['no'] = $no++;
            $tempRow['name'] = $row->user->full_name;
            $tempRow['date'] = $row->date;
            $tempRow['check_in'] = $row->check_in ? Carbon::parse($row->check_in)->format('H:i:s') : '-';
            $tempRow['check_out'] = $row->check_out ? Carbon::parse($row->check_out)->format('H:i:s') : '-';
            $tempRow['check_in_location'] = $row->check_in_location;
            $tempRow['check_out_location'] = $row->check_out_location;
            $tempRow['status'] = $row->status == 1 ? 'Present' : 'Absent';
            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }

    public function check(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:in,out',
            'latitude' => 'required',
            'longitude' => 'required',
        ]);

        if ($validator->fails()) {
            return ResponseService::errorResponse($validator->errors()->first());
        }

        try {
            $user = Auth::user();
            $today = Carbon::now()->toDateString();
            $currentTime = Carbon::now()->toDateTimeString();

            if ($request->type == 'in') {
                $attendance = $this->staffAttendance->builder()->where('user_id', $user->id)->where('date', $today)->first();
                if ($attendance) {
                    return ResponseService::errorResponse('Already checked in for today');
                }

                $data = [
                    'user_id' => $user->id,
                    'school_id' => $user->school_id,
                    'date' => $today,
                    'check_in' => $currentTime,
                    'latitude' => $request->latitude,
                    'longitude' => $request->longitude,
                    'check_in_location' => $request->address ?? null,
                    'check_in_ip' => $request->ip(),
                    'status' => 1
                ];
                $this->staffAttendance->create($data);
                return ResponseService::successResponse('Successfully Checked In');
            } else {
                $attendance = $this->staffAttendance->builder()->where('user_id', $user->id)->where('date', $today)->first();
                if (!$attendance) {
                    return ResponseService::errorResponse('Please check in first');
                }
                if ($attendance->check_out) {
                    return ResponseService::errorResponse('Already checked out for today');
                }

                $data = [
                    'check_out' => $currentTime,
                    'check_out_latitude' => $request->latitude,
                    'check_out_longitude' => $request->longitude,
                    'check_out_location' => $request->address ?? null,
                    'check_out_ip' => $request->ip(),
                ];
                $this->staffAttendance->update($attendance->id, $data);
                return ResponseService::successResponse('Successfully Checked Out');
            }
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th);
            return ResponseService::errorResponse();
        }
    }

    public function myAttendance()
    {
        return view('staff_attendance.my_attendance');
    }
}
