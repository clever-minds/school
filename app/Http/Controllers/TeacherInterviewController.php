<?php

namespace App\Http\Controllers;

use App\Models\TeacherInterviewApplication;
use App\Models\TeacherInterview;
use App\Models\TeacherInterviewFeedback;
use App\Models\TeacherInterviewFeedbackQuestion;
use App\Models\StaffSupportSchool;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TeacherInterviewController extends Controller
{
    public function index()
    {
        if (!Auth::user()->can('teacher-interview-list')) {
            abort(403);
        }

        if (request()->wantsJson()) {
            $offset = request()->offset ?? 0;
            $limit = request()->limit ?? 10;
            $sort = request()->sort ?? 'id';
            $order = request()->order ?? 'DESC';
            $search = request()->search;

            $query = TeacherInterviewApplication::with('school');

            if (Auth::user()->hasRole('Super Admin')) {
                // Super Admin can see all
            } elseif (Auth::user()->hasRole('School Admin')) {
                $query->where('school_id', Auth::user()->school_id);
            } else {
                // Other users (interviewers) see only their assigned applications
                $query->whereHas('interview', function($q) {
                    $q->where('interviewer_id', Auth::id());
                });
            }

            if (!empty($search)) {
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
                });
            }

            $total = $query->count();
            $query->orderBy($sort, $order)->skip($offset)->take($limit);
            $applications = $query->get();

            $bulkData = [];
            $bulkData['total'] = $total;
            $rows = [];
            $no = 1;

            foreach ($applications as $application) {
                $operate = '<a href="' . route('teacher-interviews.show', $application->id) . '" class="btn btn-sm btn-info btn-rounded btn-icon" title="' . __('View Details') . '"><i class="fa fa-eye"></i></a>&nbsp;';
                
                if (Auth::user()->can('teacher-interview-edit') || Auth::user()->hasRole('Super Admin') || Auth::user()->hasRole('School Admin')) {
                    $operate .= '<button type="button" class="btn btn-sm btn-warning btn-rounded btn-icon assign-btn" data-id="' . $application->id . '" data-school="' . $application->school_id . '" title="' . __('Assign Interviewer') . '"><i class="fa fa-user-plus"></i></button>&nbsp;';
                }

                if ($application->resume_path) {
                    $operate .= '<a href="' . asset('storage/' . $application->resume_path) . '" target="_blank" class="btn btn-sm btn-primary btn-rounded btn-icon" title="' . __('Download Resume') . '"><i class="fa fa-download"></i></a>';
                }

                if ($application->status == 'Pending') {
                    $statusBadge = '<span class="badge badge-warning">' . $application->status . '</span>';
                } elseif ($application->status == 'Rejected') {
                    $statusBadge = '<span class="badge badge-danger">' . $application->status . '</span>';
                } elseif ($application->status == 'Hired') {
                    $statusBadge = '<span class="badge badge-success">' . $application->status . '</span>';
                } else {
                    $statusBadge = '<span class="badge badge-info">' . $application->status . '</span>';
                }

                $tempRow = $application->toArray();
                $tempRow['no'] = $no++;
                $tempRow['school_name'] = $application->school->name ?? '-';
                $tempRow['applied_on'] = $application->created_at->format('d M, Y');
                $tempRow['status_badge'] = $statusBadge;
                $tempRow['operate'] = $operate;
                $rows[] = $tempRow;
            }

            $bulkData['rows'] = $rows;
            return response()->json($bulkData);
        }

        $staffMembers = User::where('school_id', Auth::user()->school_id)
            ->whereHas('roles', function ($q) {
                $q->whereNotIn('name', ['Student', 'Parent', 'Guardian']);
            })->get();

        return view('teacher-interviews.index', compact('staffMembers'));
    }

    public function myInterviews()
    {
        if (!Auth::user()->can('assigned-teacher-interview')) {
            abort(403);
        }

        $interviews = TeacherInterview::with('application')
            ->where('interviewer_id', Auth::id())
            ->latest()
            ->paginate(15);

        return view('teacher-interviews.my-assigned', compact('interviews'));
    }

    public function show($id)
    {
        $application = TeacherInterviewApplication::findOrFail($id);
        $isAssigned = TeacherInterview::where('application_id', $id)->where('interviewer_id', Auth::id())->exists();

        if (!Auth::user()->can('teacher-interview-list') && !($isAssigned && Auth::user()->can('assigned-teacher-interview'))) {
            abort(403);
        }


        if (Auth::user()->school_id && $application->school_id != Auth::user()->school_id) {
            abort(403);
        }

        $interview = TeacherInterview::where('application_id', $id)->first();
        if (!$interview) {
            $interview = TeacherInterview::create([
                'application_id' => $id,
                'status' => 'Pending',
                'interviewer_id' => Auth::id() // Default to the viewer if not assigned yet
            ]);
        }

        $feedbackQuestions = TeacherInterviewFeedbackQuestion::with('optionGroup')->where('status', 'active')->get();
        $feedbacks = TeacherInterviewFeedback::where('interview_id', $interview->id)->get()->keyBy('question_id');

        return view('teacher-interviews.show', compact('application', 'interview', 'feedbackQuestions', 'feedbacks'));
    }

    public function updateStatus(Request $request, $id)
    {
        // Only Super Admin or users with 'teacher-interview-update-status' permission can update status
        $isSuperAdmin = Auth::user()->hasRole('Super Admin');
        $hasPermission = Auth::user()->can('teacher-interview-update-status');

        if (!$isSuperAdmin && !$hasPermission) {
            abort(403, 'You are not authorized to update the interview status.');
        }

        $request->validate([
            'status' => 'required|string|in:Pending,Shortlisted,Interview Scheduled,Demo Scheduled,Demo Completed,Document Verification,Hired,Rejected',
            'remarks' => 'nullable|string',
            'interview_date' => 'nullable|required_if:status,Interview Scheduled|date',
            'time' => 'nullable|required_if:status,Interview Scheduled',
            'location' => 'nullable|required_if:status,Interview Scheduled|string',
            'instructions' => 'nullable|string',
            'demo_subject' => 'nullable|required_if:status,Demo Scheduled|string',
            'demo_class_name' => 'nullable|required_if:status,Demo Scheduled|string',
            'demo_date' => 'nullable|required_if:status,Demo Scheduled|date',
            'demo_time' => 'nullable|required_if:status,Demo Scheduled',
            'demo_location' => 'nullable|required_if:status,Demo Scheduled|string',
            'demo_instructions' => 'nullable|string'
        ]);

        $application = TeacherInterviewApplication::findOrFail($id);

        if (Auth::user()->school_id && $application->school_id != Auth::user()->school_id) {
            abort(403);
        }

        $application->status = $request->status;
        if ($request->has('remarks')) {
            $application->remarks = $request->remarks;
        }
        $application->save();

        if ($request->status == 'Interview Scheduled') {
            $interview = TeacherInterview::updateOrCreate(
                ['application_id' => $id],
                [
                    'interviewer_id' => Auth::id(),
                    'interview_date' => $request->interview_date,
                    'time' => $request->time,
                    'location' => $request->location,
                    'instructions' => $request->instructions,
                    'status' => 'pending'
                ]
            );

            try {
                \Illuminate\Support\Facades\Mail::to($application->email)->send(new \App\Mail\InterviewScheduledMail($application, $interview));
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Interview Email failed: " . $e->getMessage());
            }
        } elseif ($request->status == 'Demo Scheduled') {
            $demoClass = \App\Models\TeacherDemoClass::updateOrCreate(
                ['application_id' => $id],
                [
                    'school_id' => $application->school_id,
                    'subject' => $request->demo_subject,
                    'class_name' => $request->demo_class_name,
                    'date' => $request->demo_date,
                    'time' => $request->demo_time,
                    'location' => $request->demo_location,
                    'instructions' => $request->demo_instructions,
                    'status' => 'Scheduled'
                ]
            );

            try {
                \Illuminate\Support\Facades\Mail::to($application->email)->send(new \App\Mail\DemoScheduledMail($application, $demoClass));
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Demo Email failed: " . $e->getMessage());
            }
        } elseif ($request->status == 'Demo Completed') {
            $demoClass = \App\Models\TeacherDemoClass::updateOrCreate(
                ['application_id' => $id],
                [
                    'school_id' => $application->school_id,
                    'status' => 'Completed',
                    'overall_rating' => $request->demo_overall_rating,
                    'remarks' => $request->demo_remarks
                ]
            );
        } elseif ($request->status == 'Document Verification') {
            try {
                \Illuminate\Support\Facades\Mail::to($application->email)->send(new \App\Mail\DocumentVerificationMail($application));
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Document Verification Email failed: " . $e->getMessage());
            }
        }

        return redirect()->back()->with('success', 'Application status updated successfully.');
    }

    public function assignInterviewer(Request $request, $id)
    {
        if (!Auth::user()->can('teacher-interview-edit') && !Auth::user()->hasRole('Super Admin') && !Auth::user()->hasRole('School Admin')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'interviewer_id' => 'required|exists:users,id'
        ]);

        $application = TeacherInterviewApplication::findOrFail($id);
        if (Auth::user()->school_id && $application->school_id != Auth::user()->school_id) {
            abort(403);
        }

        $interview = TeacherInterview::where('application_id', $id)->first();
        if (!$interview) {
            $interview = TeacherInterview::create([
                'application_id' => $id,
                'status' => 'Pending',
                'interviewer_id' => $request->interviewer_id
            ]);
        } else {
            $interview->interviewer_id = $request->interviewer_id;
            $interview->save();
        }

        return redirect()->back()->with('success', __('Interviewer assigned successfully.'));
    }

    public function saveFeedback(Request $request, $id)
    {
        $application = TeacherInterviewApplication::findOrFail($id);
        $isAssigned = TeacherInterview::where('application_id', $id)->where('interviewer_id', Auth::id())->exists();

        if (!Auth::user()->can('teacher-interview-manage') && !Auth::user()->can('teacher-interview-edit') && !Auth::user()->hasRole('HR Admin') && !($isAssigned && Auth::user()->can('assigned-teacher-interview'))) {
            abort(403);
        }

        if (Auth::user()->school_id && $application->school_id != Auth::user()->school_id) {
            abort(403);
        }

        $interview = TeacherInterview::firstOrCreate(
            ['application_id' => $id],
            ['interviewer_id' => Auth::id(), 'status' => 'Pending']
        );

        if ($request->has('feedbacks')) {
            foreach ($request->feedbacks as $question_id => $feedback_text) {
                if (!empty($feedback_text)) {
                    TeacherInterviewFeedback::updateOrCreate(
                        ['interview_id' => $interview->id, 'question_id' => $question_id],
                        ['interviewer_feedback' => $feedback_text]
                    );
                }
            }
        }

        return redirect()->back()->with('success', 'Interview feedback saved successfully.');
    }

    public function downloadPdf($id)
    {
        $application = TeacherInterviewApplication::findOrFail($id);
        $isAssigned = TeacherInterview::where('application_id', $id)->where('interviewer_id', Auth::id())->exists();

        if (!Auth::user()->can('teacher-interview-list') && !($isAssigned && Auth::user()->can('assigned-teacher-interview'))) {
            abort(403);
        }

        if (Auth::user()->school_id && $application->school_id != Auth::user()->school_id) {
            abort(403);
        }

        $interview = TeacherInterview::where('application_id', $id)->first();
        if (!$interview) {
            abort(404, 'Interview not found');
        }

        $feedbackQuestions = TeacherInterviewFeedbackQuestion::with('optionGroup')->where('status', 'active')->get();
        $feedbacks = TeacherInterviewFeedback::where('interview_id', $interview->id)->get()->keyBy('question_id');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('teacher-interviews.pdf', compact('application', 'interview', 'feedbackQuestions', 'feedbacks'));

        return $pdf->download('interview_feedback_' . str_replace(' ', '_', strtolower($application->name)) . '.pdf');
    }
}
