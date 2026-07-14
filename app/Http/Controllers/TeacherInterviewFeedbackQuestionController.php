<?php

namespace App\Http\Controllers;

use App\Models\TeacherInterviewFeedbackQuestion;
use App\Models\AuditOptionGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TeacherInterviewFeedbackQuestionController extends Controller
{
    public function index()
    {
        if (!Auth::user()->can('teacher-interview-question-list')) {
            abort(403, 'Unauthorized action.');
        }

        $questions = TeacherInterviewFeedbackQuestion::with('optionGroup')->orderBy('id', 'desc')->get();
        $optionGroups = AuditOptionGroup::all();
        return view('teacher-interview-feedback-questions.index', compact('questions', 'optionGroups'));
    }

    public function store(Request $request)
    {
        if (!Auth::user()->can('teacher-interview-question-create')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'feedback_question' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive',
            'type' => 'nullable|string',
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
            'category' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive',
            'type' => 'nullable|string',
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
