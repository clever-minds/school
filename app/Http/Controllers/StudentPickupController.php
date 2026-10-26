<?php

namespace App\Http\Controllers;

use App\Repositories\StudentPickup\StudentPickupInterface;
use App\Services\ResponseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

class StudentPickupController extends Controller
{
    private StudentPickupInterface $studentPickup;

    public function __construct(StudentPickupInterface $studentPickup)
    {
        $this->studentPickup = $studentPickup;
    }

    public function index()
    {
        ResponseService::noAnyPermissionThenRedirect(['student-pickup-list', 'student-pickup-verify', 'student-pickup-verify-otp']);
        return view('student_pickup.index');
    }

    public function show(Request $request)
    {
        ResponseService::noAnyPermissionThenSendJson(['student-pickup-list', 'student-pickup-verify', 'student-pickup-verify-otp']);
        try {
            $offset = $request->input('offset', 0);
            $limit = $request->input('limit', 10);
            $sort = $request->input('sort', 'id');
            $order = $request->input('order', 'DESC');
            $search = $request->input('search');

            $query = $this->studentPickup->builder()->with([
                'student.user',
                'student.class_section.class',
                'student.class_section.section',
                'parent',
                'verifier',
                'school'
            ]);

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('pickup_person_name', 'LIKE', "%$search%")
                      ->orWhere('otp', 'LIKE', "%$search%")
                      ->orWhereHas('student.user', function($q) use ($search) {
                          $q->where('first_name', 'LIKE', "%$search%")->orWhere('last_name', 'LIKE', "%$search%");
                      });
                });
            }

            $total = $query->count();
            $rows = $query->orderBy($sort, $order)->offset($offset)->limit($limit)->get();

            $canVerify = Auth::user()->canany(['student-pickup-verify', 'student-pickup-verify-otp', 'student-pickup-edit']);

            $bulkData = [
                'total' => $total,
                'rows'  => $rows->map(function ($row) use ($canVerify) {
                    $studentName = $row->student->full_name ?? ($row->student->user->full_name ?? '-');
                    $className = $row->student->class_section->class->name ?? '';
                    $sectionName = $row->student->class_section->section->name ?? '';
                    $classFull = trim($className . ' ' . $sectionName);

                    $operate = '';
                    if ($canVerify && $row->status == 0) {
                        $operate = '<button type="button" class="btn btn-sm btn-theme text-white verify-pickup-btn" ' .
                            'data-id="' . $row->id . '" ' .
                            'data-student="' . htmlspecialchars($studentName, ENT_QUOTES) . '" ' .
                            'data-class="' . htmlspecialchars($classFull, ENT_QUOTES) . '" ' .
                            'data-person="' . htmlspecialchars($row->pickup_person_name ?? '', ENT_QUOTES) . '" ' .
                            'data-parent="' . htmlspecialchars($row->parent->full_name ?? '', ENT_QUOTES) . '" ' .
                            'title="' . __('Click here to Verify OTP') . '">' .
                            '<i class="fa fa-shield mr-1"></i> ' . __('Verify OTP') .
                            '</button>';
                    } else if ($row->status == 1) {
                        $operate = '<span class="badge badge-success px-2 py-1"><i class="fa fa-check"></i> ' . __('Verified') . '</span>';
                    }

                    return [
                        'id' => $row->id,
                        'student_name' => $studentName,
                        'class_name' => $classFull ?: '-',
                        'parent_name' => $row->parent->full_name ?? '-',
                        'pickup_person_name' => $row->pickup_person_name,
                        'otp' => $row->otp,
                        'status' => $row->status,
                        'verified_by' => $row->verifier ? $row->verifier->full_name : '-',
                        'verified_at' => $row->verified_at ? date('Y-m-d H:i:s', strtotime($row->verified_at)) : '-',
                        'created_at' => $row->created_at ? date('Y-m-d H:i:s', strtotime($row->created_at)) : '-',
                        'operate' => $operate,
                    ];
                }),
            ];

            return response()->json($bulkData);
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e);
            return response()->json(['error' => true, 'message' => 'Something went wrong']);
        }
    }

    public function pendingList()
    {
        ResponseService::noAnyPermissionThenSendJson(['student-pickup-list', 'student-pickup-verify', 'student-pickup-verify-otp']);

        $requests = $this->studentPickup->builder()
            ->with(['student.user', 'student.class_section.class', 'student.class_section.section', 'parent'])
            ->where('status', 0)
            ->orderBy('id', 'DESC')
            ->get();

        $data = $requests->map(function ($row) {
            $studentName = $row->student->full_name ?? ($row->student->user->full_name ?? '-');
            $className = $row->student->class_section->class->name ?? '';
            $sectionName = $row->student->class_section->section->name ?? '';
            $classFull = trim($className . ' ' . $sectionName);

            return [
                'id' => $row->id,
                'otp' => $row->otp,
                'student_name' => $studentName,
                'class_name' => $classFull ?: '-',
                'pickup_person_name' => $row->pickup_person_name,
                'parent_name' => $row->parent->full_name ?? '-',
                'created_at' => $row->created_at ? date('d M Y, h:i A', strtotime($row->created_at)) : '-',
            ];
        });

        return response()->json(['error' => false, 'data' => $data]);
    }

    public function checkOtp(Request $request)
    {
        ResponseService::noAnyPermissionThenSendJson(['student-pickup-verify', 'student-pickup-verify-otp', 'student-pickup-edit']);

        $request->validate([
            'otp' => 'required|string|size:6',
        ]);

        $pickupRequest = $this->studentPickup->builder()
            ->with(['student.user', 'student.class_section.class', 'student.class_section.section', 'parent'])
            ->where('otp', $request->otp)
            ->where('status', 0)
            ->first();

        if (!$pickupRequest) {
            return response()->json([
                'error' => true,
                'message' => 'No pending pickup request found for this OTP'
            ]);
        }

        $studentName = $pickupRequest->student->full_name ?? ($pickupRequest->student->user->full_name ?? '-');
        $className = $pickupRequest->student->class_section->class->name ?? '';
        $sectionName = $pickupRequest->student->class_section->section->name ?? '';
        $classFull = trim($className . ' ' . $sectionName);

        return response()->json([
            'error' => false,
            'data' => [
                'id' => $pickupRequest->id,
                'otp' => $pickupRequest->otp,
                'student_name' => $studentName,
                'class_name' => $classFull ?: '-',
                'pickup_person_name' => $pickupRequest->pickup_person_name,
                'parent_name' => $pickupRequest->parent->full_name ?? '-',
                'created_at' => $pickupRequest->created_at ? date('d M Y, h:i A', strtotime($pickupRequest->created_at)) : '-',
            ]
        ]);
    }

    public function verifyOtp(Request $request)
    {
        ResponseService::noAnyPermissionThenSendJson(['student-pickup-verify', 'student-pickup-verify-otp', 'student-pickup-edit']);

        $request->validate([
            'otp' => 'required|string|size:6',
        ]);

        try {
            $query = $this->studentPickup->builder()
                ->where('otp', $request->otp)
                ->where('status', 0); // Only pending

            if ($request->filled('id')) {
                $query->where('id', $request->id);
            }

            $pickupRequest = $query->first();

            if (!$pickupRequest) {
                ResponseService::errorResponse('Invalid or expired OTP');
            }

            $pickupRequest->update([
                'status' => 1, // Verified
                'verified_by' => Auth::id(),
                'verified_at' => Carbon::now(),
            ]);

            $pickupRequest->load(['student.user', 'student.class_section.class', 'student.class_section.section', 'parent']);

            $studentName = $pickupRequest->student->full_name ?? ($pickupRequest->student->user->full_name ?? 'Student');
            $className = $pickupRequest->student->class_section->class->name ?? '';
            $sectionName = $pickupRequest->student->class_section->section->name ?? '';
            $classFull = trim($className . ' ' . $sectionName);

            ResponseService::successResponse(
                'Pickup OTP verified successfully! Student: ' . $studentName . ($classFull ? ' (' . $classFull . ')' : '') . ' can be picked up by ' . $pickupRequest->pickup_person_name . '.',
                [
                    'id' => $pickupRequest->id,
                    'student_name' => $studentName,
                    'class_name' => $classFull,
                    'pickup_person_name' => $pickupRequest->pickup_person_name,
                ]
            );
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse('Failed to verify OTP');
        }
    }
}
