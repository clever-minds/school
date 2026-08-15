<?php

namespace App\Http\Controllers;

use App\Models\TeacherInterviewFeedbackQuestion;
use App\Models\TeacherInterviewCategory;
use App\Models\AuditOptionGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TeacherInterviewFeedbackQuestionController extends Controller
{
    public function index(Request $request)
    {
        if (!Auth::user()->can('teacher-interview-question-list')) {
            abort(403, 'Unauthorized action.');
        }

        $query = TeacherInterviewFeedbackQuestion::with('category');
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where('feedback_question', 'like', "%{$search}%")
                  ->orWhereHas('category', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
        }
        $questions = $query->orderBy('id', 'desc')->paginate(15);
        $categories = TeacherInterviewCategory::where('status', 1)->get();
        return view('teacher-interview-feedback-questions.index', compact('questions', 'categories'));
    }

    public function store(Request $request)
    {
        if (!Auth::user()->can('teacher-interview-question-create')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'feedback_question' => 'required|string|max:255',
            'teacher_interview_category_id' => 'nullable|exists:teacher_interview_categories,id',
            'status' => 'required|in:active,inactive',
            'type' => 'nullable|string',
            'custom_options' => 'nullable|string',
            'audit_option_group_id' => 'nullable|exists:audit_option_groups,id'
        ]);

        TeacherInterviewFeedbackQuestion::create($request->all());

        return redirect()->route('teacher-interview-feedback-questions.index')->with('success', __('Question created successfully.'));
    }

    public function update(Request $request, $id)
    {
        if (!Auth::user()->can('teacher-interview-question-edit')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'feedback_question' => 'required|string|max:255',
            'teacher_interview_category_id' => 'nullable|exists:teacher_interview_categories,id',
            'status' => 'required|in:active,inactive',
            'type' => 'nullable|string',
            'custom_options' => 'nullable|string',
            'audit_option_group_id' => 'nullable|exists:audit_option_groups,id'
        ]);

        $question = TeacherInterviewFeedbackQuestion::findOrFail($id);
        $question->update($request->all());

        return redirect()->route('teacher-interview-feedback-questions.index')->with('success', __('Question updated successfully.'));
    }

    public function destroy($id)
    {
        if (!Auth::user()->can('teacher-interview-question-delete')) {
            abort(403, 'Unauthorized action.');
        }

        $question = TeacherInterviewFeedbackQuestion::findOrFail($id);
        $question->delete();

        return redirect()->route('teacher-interview-feedback-questions.index')->with('success', __('Question deleted successfully.'));
    }
}
