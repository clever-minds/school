<?php

namespace App\Http\Controllers;

use App\Models\TeacherInterviewApplication;
use App\Models\TeacherInterview;
use App\Models\TeacherInterviewFeedback;
use App\Models\TeacherInterviewFeedbackQuestion;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TeacherInterviewController extends Controller
{
    public function index()
    {
        $request = request();
        if (!Auth::user()->can('teacher-interview-list')) {
            abort(403);
        }

        $query = TeacherInterviewApplication::query();

        if (Auth::user()->school_id) {
            $query->where('school_id', Auth::user()->school_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $applications = $query->latest()->paginate(15);
        $staffMembers = User::where('school_id', Auth::user()->school_id)
            ->whereHas('roles', function($q) {
                $q->whereNotIn('name', ['Student', 'Parent', 'Guardian']);
            })->get();

        return view('teacher-interviews.index', compact('applications', 'staffMembers'));
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
        if (!Auth::user()->can('teacher-interview-manage')) {
            abort(403);
        }

        $request->validate([
            'status' => 'required|string|in:Pending,Shortlisted,Interview Scheduled,Hired,Rejected',
            'remarks' => 'nullable|string'
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

        return redirect()->back()->with('success', 'Application status updated successfully.');
    }

    public function assignInterviewer(Request $request, $id)
    {
        if (!Auth::user()->can('teacher-interview-list')) {
            abort(403);
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

        if (!Auth::user()->can('teacher-interview-manage') && !($isAssigned && Auth::user()->can('assigned-teacher-interview'))) {
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
