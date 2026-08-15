<?php

namespace App\Http\Controllers;

use App\Models\TeacherInterviewCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TeacherInterviewCategoryController extends Controller
{
    public function index(Request $request)
    {
        if (!Auth::user()->can('teacher-interview-question-list')) {
            abort(403, 'Unauthorized action.');
        }

        $query = TeacherInterviewCategory::query();
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
        }
        $categories = $query->orderBy('id', 'desc')->paginate(15);

        return view('teacher-interview-categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        if (!Auth::user()->can('teacher-interview-question-create')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:1,0',
        ]);

        TeacherInterviewCategory::create($request->all());

        return redirect()->route('teacher-interview-categories.index')->with('success', __('Category created successfully.'));
    }

    public function update(Request $request, $id)
    {
        if (!Auth::user()->can('teacher-interview-question-edit')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:1,0',
        ]);

        $category = TeacherInterviewCategory::findOrFail($id);
        $category->update($request->all());

        return redirect()->route('teacher-interview-categories.index')->with('success', __('Category updated successfully.'));
    }

    public function destroy($id)
    {
        if (!Auth::user()->can('teacher-interview-question-delete')) {
            abort(403, 'Unauthorized action.');
        }

        $category = TeacherInterviewCategory::findOrFail($id);
        $category->delete();

        return redirect()->route('teacher-interview-categories.index')->with('success', __('Category deleted successfully.'));
    }
}
