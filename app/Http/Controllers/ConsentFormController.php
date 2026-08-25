<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Students;
use App\Services\ResponseService;
use Illuminate\Support\Facades\Auth;

class ConsentFormController extends Controller
{
    /**
     * Display the main view.
     */
    public function index()
    {
        if (!Auth::user()->can('consent-form-list')) {
            return ResponseService::noPermissionThenRedirect('consent-form-list');
        }

        $classSections = \App\Models\ClassSection::with('class', 'section')->get();
        return view('consent_forms.index', compact('classSections'));
    }

    /**
     * Return list for the datatable.
     */
    public function list(Request $request)
    {
        if (!Auth::user()->can('consent-form-list')) {
            return ResponseService::noPermissionThenSendJson('consent-form-list');
        }

        $offset = $request->offset ?? 0;
        $limit = $request->limit ?? 10;
        $sort = $request->sort ?? 'id';
        $order = $request->order ?? 'DESC';

        $sql = Students::with(['user', 'class_section.class', 'class_section.section']);

        if (!empty($request->search)) {
            $search = $request->search;
            $sql->where(function ($q) use ($search) {
                $q->whereHas('user', function ($q2) use ($search) {
                    $q2->where('first_name', 'LIKE', "%$search%")
                        ->orWhere('last_name', 'LIKE', "%$search%");
                });
            });
        }

        if ($request->has('class_section_id') && !empty($request->class_section_id)) {
            $sql->where('class_section_id', $request->class_section_id);
        }

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
            $tempRow['student_name'] = $row->user ? $row->user->first_name . ' ' . $row->user->last_name : '-';
            
            $class_name = '-';
            if ($row->class_section && $row->class_section->class) {
                $class_name = $row->class_section->class->name . ' - ' . ($row->class_section->section->name ?? '');
            }
            $tempRow['class_section'] = $class_name;
            
            if ($row->consent_form_date) {
                $consentDate = \Carbon\Carbon::parse($row->consent_form_date);
                if ($consentDate->format('H:i:s') === '00:00:00') {
                    $createdTime = \Carbon\Carbon::parse($row->created_at)->format('H:i:s');
                    $consentDate = \Carbon\Carbon::parse($consentDate->format('Y-m-d') . ' ' . $createdTime);
                }
                $tempRow['consent_form_date'] = $consentDate->format('Y-m-d h:i A');
            } else {
                $tempRow['consent_form_date'] = '-';
            }
            
            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }
}
