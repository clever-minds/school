<?php

namespace App\Http\Controllers;

use App\Models\AuditQuestion;
use App\Models\School;
use App\Models\SchoolAudit;
use App\Models\SchoolAuditAnswer;
use App\Services\BootstrapTableService;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Throwable;

class SchoolAuditController extends Controller
{
    public function index(Request $request)
    {
        ResponseService::noPermissionThenRedirect('school-audit-list');

        if ($request->wantsJson()) {
            $offset = request('offset', 0);
            $limit = request('limit', 10);
            $sort = request('sort', 'id');
            $order = request('order', 'DESC');
            $search = request('search');

            $sql = SchoolAudit::with('school', 'auditor');

            if (!empty($search)) {
                $sql->whereHas('school', function ($q) use ($search) {
                    $q->where('name', 'like', "%$search%");
                })->orWhereHas('auditor', function ($q) use ($search) {
                    $q->where('first_name', 'like', "%$search%")
                      ->orWhere('last_name', 'like', "%$search%");
                });
            }

            $total = $sql->count();

            $sql->orderBy($sort, $order)->skip($offset)->take($limit);
            $res = $sql->get();

            $bulkData = [];
            $bulkData['total'] = $total;
            $rows = [];
            $no = 1;
            foreach ($res as $row) {
                $operate = '';
                if (Auth::user()->can('school-audit-list')) {
                    $operate .= '<a href="' . route('school-audits.show', $row->id) . '" class="btn btn-xs btn-gradient-info btn-rounded btn-icon" title="View"><i class="fa fa-eye"></i></a>&nbsp;&nbsp;';
                }
                if (Auth::user()->can('school-audit-delete')) {
                    $operate .= BootstrapTableService::deleteButton(route('school-audits.destroy', $row->id));
                }

                $tempRow = $row->toArray();
                $tempRow['no'] = $no++;
                $tempRow['school_name'] = $row->school ? $row->school->name : '-';
                $tempRow['auditor_name'] = $row->auditor ? $row->auditor->first_name . ' ' . $row->auditor->last_name : '-';
                $tempRow['operate'] = $operate;
                $rows[] = $tempRow;
            }

            $bulkData['rows'] = $rows;
            return response()->json($bulkData);
        }

        return view('school_audits.index');
    }

    public function create()
    {
        ResponseService::noPermissionThenRedirect('school-audit-create');
        
        $schools = School::active()->get();
        $questions = AuditQuestion::active()->get();

        return view('school_audits.create', compact('schools', 'questions'));
    }

    public function store(Request $request)
    {
        ResponseService::noPermissionThenRedirect('school-audit-create');

        $request->validate([
            'school_id' => 'required|exists:schools,id',
            'audit_date' => 'required|date',
            'remarks' => 'nullable|string',
            'answers' => 'required|array',
            'answers.*.question_id' => 'required|exists:audit_questions,id',
            'answers.*.answer' => 'required|in:Yes,No,N/A',
            'answers.*.remarks' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $audit = SchoolAudit::create([
                'school_id' => $request->school_id,
                'auditor_id' => Auth::user()->id,
                'audit_date' => date('Y-m-d', strtotime($request->audit_date)),
                'remarks' => $request->remarks,
            ]);

            foreach ($request->answers as $answerData) {
                SchoolAuditAnswer::create([
                    'school_audit_id' => $audit->id,
                    'audit_question_id' => $answerData['question_id'],
                    'answer' => $answerData['answer'],
                    'remarks' => $answerData['remarks'] ?? '',
                ]);
            }

            DB::commit();
            return redirect()->route('school-audits.index')->with('success', trans('data_store_successfully'));
        } catch (Throwable $e) {
            DB::rollback();
            return redirect()->back()->with('error', trans('error_occurred'));
        }
    }

    public function show($id)
    {
        ResponseService::noPermissionThenRedirect('school-audit-list');

        $audit = SchoolAudit::with(['school', 'auditor', 'answers.question'])->findOrFail($id);

        return view('school_audits.show', compact('audit'));
    }

    public function destroy($id)
    {
        ResponseService::noPermissionThenSendJson('school-audit-delete');

        try {
            $audit = SchoolAudit::findOrFail($id);
            // Delete related answers
            $audit->answers()->delete();
            $audit->delete();
            return ResponseService::successResponse('Data Deleted Successfully');
        } catch (Throwable $e) {
            return ResponseService::logErrorResponse($e, 'SchoolAuditController -> destroy');
        }
    }
}
