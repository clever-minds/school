<?php

namespace App\Http\Controllers;

use App\Models\TeacherInterviewApplication;
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

        return view('teacher-interviews.index', compact('applications'));
    }

    public function show($id)
    {
        if (!Auth::user()->can('teacher-interview-list')) {
            abort(403);
        }

        $application = TeacherInterviewApplication::findOrFail($id);
        
        if (Auth::user()->school_id && $application->school_id != Auth::user()->school_id) {
            abort(403);
        }

        return view('teacher-interviews.show', compact('application'));
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
}
