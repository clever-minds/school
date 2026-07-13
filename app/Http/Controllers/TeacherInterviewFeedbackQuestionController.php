<?php

namespace App\Http\Controllers;

use App\Models\TeacherInterviewFeedbackQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TeacherInterviewFeedbackQuestionController extends Controller
{
    public function index()
    {
        if (!Auth::user()->can('teacher-interview-feedback-question-list')) {
            abort(403, 'Unauthorized action.');
        }

        $questions = TeacherInterviewFeedbackQuestion::orderBy('id', 'desc')->get();
        return view('teacher-interview-feedback-questions.index', compact('questions'));
    }

    public function store(Request $request)
    {
        if (!Auth::user()->can('teacher-interview-feedback-question-create')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'feedback_question' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive'
        ]);

        TeacherInterviewFeedbackQuestion::create($request->all());

        return redirect()->route('teacher-interview-feedback-questions.index')->with('success', __('Question created successfully.'));
    }

    public function update(Request $request, $id)
    {
        if (!Auth::user()->can('teacher-interview-feedback-question-edit')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'feedback_question' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive'
        ]);

        $question = TeacherInterviewFeedbackQuestion::findOrFail($id);
        $question->update($request->all());

        return redirect()->route('teacher-interview-feedback-questions.index')->with('success', __('Question updated successfully.'));
    }

    public function destroy($id)
    {
        if (!Auth::user()->can('teacher-interview-feedback-question-delete')) {
            abort(403, 'Unauthorized action.');
        }

        $question = TeacherInterviewFeedbackQuestion::findOrFail($id);
        $question->delete();

        return redirect()->route('teacher-interview-feedback-questions.index')->with('success', __('Question deleted successfully.'));
    }
}
