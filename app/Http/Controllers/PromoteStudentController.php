<?php

namespace App\Http\Controllers;

use App\Models\PromoteStudent;
use App\Models\School;
use App\Models\ClassSection;
use App\Repositories\ClassSection\ClassSectionInterface;
use App\Repositories\PromoteStudent\PromoteStudentInterface;
use App\Repositories\SessionYear\SessionYearInterface;
use App\Repositories\Student\StudentInterface;
use App\Repositories\StudentSubject\StudentSubjectInterface;
use App\Repositories\User\UserInterface;
use App\Services\CachingService;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Throwable;

class PromoteStudentController extends Controller {

    private ClassSectionInterface $classSection;
    private SessionYearInterface $sessionYear;
    private StudentInterface $student;
    private UserInterface $user;
    private PromoteStudentInterface $promoteStudent;
    private CachingService $cache;
    private StudentSubjectInterface $studentSubject;

    public function __construct(ClassSectionInterface $classSection, SessionYearInterface $sessionYear, StudentInterface $student, UserInterface $user, PromoteStudentInterface $promoteStudent, CachingService $cachingService, StudentSubjectInterface $studentSubject) {
        $this->classSection = $classSection;
        $this->sessionYear = $sessionYear;
        $this->student = $student;
        $this->user = $user;
        $this->promoteStudent = $promoteStudent;
        $this->cache = $cachingService;
        $this->studentSubject = $studentSubject;
    }

    public function index() {
        ResponseService::noAnyPermissionThenRedirect(['promote-student-list','transfer-student-list']);
        $classSections = $this->classSection->all(['*'], ['class', 'section', 'medium','class.stream']);
        $sessionYears = $this->sessionYear->builder()->select(['id', 'name'])->where('default', 0)->get();
        $schools = School::on('mysql')->select('id', 'name')->get();
        return view('promote_student.index', compact('classSections', 'sessionYears', 'schools'));
    }

    public function store(Request $request) {
        ResponseService::noAnyPermissionThenSendJson(['promote-student-create', 'promote-student-edit']);
        $request->validate([
            'class_section_id' => 'required',
            'promote_data' => 'required',
            'new_school_id' => 'nullable'
        ], ['promote_data.required' => "No Student Data Found"]);
        try {
            DB::beginTransaction();

            $promoteStudentData = array();
            foreach ($request->promote_data as $key => $data) {
                $promoteStudentData[$key] = array(
                    'student_id'      => $data['student_id'],
                    'session_year_id' => $request->session_year_id,
                    'result'          => $data['result'],
                    'status'          => $data['status'],
                    'school_id'       => $request->new_school_id ?? Auth::user()->school_id
                );

                if ($data['result'] == 1) {
                    // IF Student Then Store New Class Section in Promote Data
                    $promoteStudentData[$key]['class_section_id'] = $request->new_class_section_id;

                    if ($data['status'] == 1) {
                        // IF Student Continues then get students IDs
                        $passStudentsIds[] = $data['student_id'];
                    }
                } else {
                    // IF Students Fails then store Current Class Section in Promote Data
                    $promoteStudentData[$key]['class_section_id'] = $request->class_section_id;

                    if ($data['status'] == 1) {
                        // IF Student Fails then get students IDs
                        $failStudentsIds[] = $data['student_id'];
                    }
                }

                // IF Student Leaves then get Student IDs
                if ($data['status'] == 0) {
                    $leftStudentSIds[] = $data['student_id'];
                }

                $promoteStudentData[$key]['current_class_section_id'] = $request->new_class_section_id;
                $promoteStudentData[$key]['current_session_year_id'] = $request->session_year_id;
            }
            if (!empty($passStudentsIds)) {

                // Get Sort Value and Order Value from Settings
                $sortBy = !empty($this->cache->getSchoolSettings('roll_number_sort_column')) ? $this->cache->getSchoolSettings('roll_number_sort_column') : 'first_name';
                $orderBy = !empty($this->cache->getSchoolSettings('roll_number_sort_order')) ? $this->cache->getSchoolSettings('roll_number_sort_order') : 'asc';

                // Get The Data of Users who is passed with Student Relation and make Array to Update Student Details
                $studentUsers = $this->user->builder()->role('Student')->whereIn('id',$passStudentsIds)->with('student')->orderBy('users.'.$sortBy, $orderBy)->get();
                $studentsData = array();
                foreach ($studentUsers as $key => $user) {
                    if ($request->new_school_id && $request->new_school_id != Auth::user()->school_id) {
                        // Cross-database promotion
                        $targetSchool = School::on('mysql')->find($request->new_school_id);
                        if ($targetSchool && $targetSchool->database_name) {
                            $sourceDb = Config::get('database.connections.school.database');
                            $targetDb = $targetSchool->database_name;

                            // 1. Prepare data
                            $userData = $user->toArray();
                            unset($userData['id']);
                            $userData['school_id'] = $request->new_school_id;

                            $sourceStudent = $user->student;
                            $studentData = $sourceStudent->toArray();
                            unset($studentData['id']);
                            $studentData['school_id'] = $request->new_school_id;
                            $studentData['class_section_id'] = $request->new_class_section_id;
                            $studentData['session_year_id'] = $request->session_year_id;
                            $studentData['roll_number'] = (int)$key + 1;

                            // Handle potential null constraints in target database
                            $studentData['uni_no'] = $studentData['uni_no'] ?? '';
                            $studentData['cast'] = $studentData['cast'] ?? 'GENERAL';

                            // 2. Switch connection
                            Config::set('database.connections.school.database', $targetDb);
                            DB::purge('school');
                            DB::connection('school')->reconnect();

                            // 3. Insert
                            $existingUser = \App\Models\User::on('school')->where('email', $userData['email'])->first();
                            if (!$existingUser) {
                                $newUser = \App\Models\User::on('school')->create($userData);
                                $newUser->assignRole('student');
                            } else {
                                $newUser = $existingUser;
                            }

                            // Handling Guardian (Need to fetch from source student)
                            $sourceGuardian = $sourceStudent->guardian;
                            if ($sourceGuardian) {
                                $guardianData = $sourceGuardian->toArray();
                                unset($guardianData['id']);
                                $guardianData['school_id'] = $request->new_school_id;

                                $existingGuardian = \App\Models\User::on('school')->where('email', $guardianData['email'])->first();
                                if (!$existingGuardian) {
                                    $newGuardian = \App\Models\User::on('school')->create($guardianData);
                                    $newGuardian->assignRole('guardian');
                                } else {
                                    $newGuardian = $existingGuardian;
                                }
                                $studentData['guardian_id'] = $newGuardian->id;
                            } else {
                                $studentData['guardian_id'] = $studentData['guardian_id'] ?? 0;
                            }

                            $studentData['user_id'] = $newUser->id;
                            \App\Models\Students::on('school')->create($studentData);

                            // 4. Switch back
                            Config::set('database.connections.school.database', $sourceDb);
                            DB::purge('school');
                            DB::connection('school')->reconnect();

                            // 5. Deactivate in source
                            $user->update(['status' => 0]);
                        }
                    } else {
                        $studentsData[] = array(
                            'id' => $user->student->id,
                            'roll_number' => (int)$key + 1,
                            'class_section_id' => $request->new_class_section_id,
                            'session_year_id'  => $request->session_year_id,
                            'school_id'        => $request->new_school_id ?? $user->student->school_id,
                        );
                    }
                }

                // Upsert Student Data
                $this->student->upsert($studentsData,['id'],['roll_number','class_section_id','session_year_id', 'school_id']);
            }

            if (!empty($failStudentsIds)) {
                $this->student->builder()->whereIn('user_id', $failStudentsIds)->update(array(
                    'session_year_id' => $request->session_year_id,
                ));
            }

            if (!empty($leftStudentSIds)) {
                $this->user->builder()->whereIn('id', $leftStudentSIds)->update(['status' => 0,'deleted_at' => now()]);
            }
            $this->promoteStudent->upsert($promoteStudentData, ['class_section', 'student_id', 'session_year_id'], ['status', 'result']);
            DB::commit();
            ResponseService::successResponse("Data Updated Successfully");

        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }

    public function getPromoteData(Request $request) {
        $response = PromoteStudent::where(['class_section_id' => $request->class_section_id])->get();
        return response()->json($response);
    }

    public function getClassSectionBySchool($school_id)
    {
        try {
            $school = School::on('mysql')->find($school_id);
            if (!$school) {
                return ResponseService::errorResponse("School not found");
            }

            // Get current database name to restore it later
            $current_db = Config::get('database.connections.school.database');

            // Switch to target school's database
            Config::set('database.connections.school.database', $school->database_name);
            DB::purge('school');
            DB::connection('school')->reconnect();
            
            // Fetch class sections from the other school's database
            $class_sections = ClassSection::on('school')->withoutGlobalScopes()
                ->where('school_id', $school_id)
                ->with('class', 'class.stream', 'section', 'medium')
                ->get();

            // Restore original database connection
            Config::set('database.connections.school.database', $current_db);
            DB::purge('school');
            DB::connection('school')->reconnect();

            ResponseService::successResponse('Data Fetched Successfully', $class_sections);
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "PromoteStudent Controller -> getClassSectionBySchool method");
            ResponseService::errorResponse();
        }
    }

    public function show(Request $request) {
        ResponseService::noPermissionThenRedirect('promote-student-list');
        $offset = request('offset', 0);
        $limit = request('limit', 10);
        $sort = request('sort', 'id');
        $order = request('order', 'ASC');
        $search = request('search');

        $class_section_id = $request->class_section_id;
        $sessionYear = $this->cache->getDefaultSessionYear(); // Get Current Session Year
        $sql = $this->student->builder()->where(['class_section_id' => $class_section_id, 'session_year_id' => $sessionYear->id])->whereHas('user', function ($query) {
            $query->where('status', 1);
        })->with('user')
            ->where(function ($query) use ($search) {
                $query->when($search, function ($query) use ($search) {
                $query->where('id', 'LIKE', "%$search%")
                ->orWhereHas('user',function($q) use($search){
                    $q->whereRaw("concat(users.first_name,' ',users.last_name) LIKE '%" . $search . "%'");
                });
            });
            });
        $total = $sql->count();
        // $sql->orderBy($sort, $order)->skip($offset)->take($limit);
        $sql->orderBy($sort, $order);
        $res = $sql->get();
        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $no = 1;
        foreach ($res as $row) {
            $tempRow = $row->toArray();
            $tempRow['no'] = $offset + $no++;
            $rows[] = $tempRow;
        }
        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }

    public function showTransferStudent(Request $request) {
        // ResponseService::noFeatureThenRedirect('Academics Management');
        ResponseService::noPermissionThenRedirect('transfer-student-list');
        $offset = request('offset', 0);
        $limit = request('limit', 10);
        $sort = request('sort', 'id');
        $order = request('order', 'ASC');
        $search = request('search');

        $class_section_id = $request->current_class_section;
        $sessionYear = $this->cache->getDefaultSessionYear(); // Get Current Session Year
        $sql = $this->student->builder()->where(['class_section_id' => $class_section_id, 'session_year_id' => $sessionYear->id])->whereHas('user', function ($query) {
            $query->where('status', 1);
        })->with('user')
        ->where(function($q) use($search) {
            $q->when($search, function ($query) use ($search) {
                $query->where('id', 'LIKE', "%$search%")
                ->orWhereHas('user',function($q) use($search){
                    $q->whereRaw("concat(users.first_name,' ',users.last_name) LIKE '%" . $search . "%'");
                });
            });
        });
            
        $total = $sql->count();
        $sql->orderBy($sort, $order)->skip($offset)->take($limit);
        $res = $sql->get();
        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $no = 1;
        foreach ($res as $row) {
            $tempRow['no'] = $offset + $no++;
            $tempRow['student_id'] = $row->id;
            $tempRow['user_id'] = $row->user_id;
            $tempRow['name'] = $row->full_name;
            $rows[] = $tempRow;
        }
        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }

    public function storeTransferStudent(Request $request){
        // ResponseService::noFeatureThenRedirect('Academics Management');
        ResponseService::noAnyPermissionThenSendJson(['transfer-student-list', 'transfer-student-edit']);
        $request->validate([
            'current_class_section_id' => 'required',
            'new_class_section_id' => 'required',
            'student_ids' => 'required',
            'new_school_id' => 'nullable'
        ]);
        try {
            DB::beginTransaction();
            // $studentIds = json_decode($request->student_ids);
            $studentIds = explode(",",$request->student_ids);
            $roll_number_db = $this->student->builder()->select(DB::raw('max(roll_number)'))->where('class_section_id', $request->new_class_section_id)->first();
            $roll_number_db = $roll_number_db['max(roll_number)'];

            $updateStudent = array();
            foreach ($studentIds as $id) {
                $sourceStudent = $this->student->builder()->where('id', $id)->with('user', 'guardian')->first();
                
                if ($request->new_school_id && $request->new_school_id != Auth::user()->school_id) {
                    // Cross-database transfer
                    $targetSchool = School::on('mysql')->find($request->new_school_id);
                    if ($targetSchool && $targetSchool->database_name) {
                        $sourceDb = Config::get('database.connections.school.database');
                        $targetDb = $targetSchool->database_name;

                        // 1. Prepare data from source
                        $userData = $sourceStudent->user->toArray();
                        unset($userData['id']);
                        $userData['school_id'] = $request->new_school_id;

                        $guardianData = null;
                        if ($sourceStudent->guardian) {
                            $guardianData = $sourceStudent->guardian->toArray();
                            unset($guardianData['id']);
                            $guardianData['school_id'] = $request->new_school_id;
                        }

                        $studentData = $sourceStudent->toArray();
                        unset($studentData['id']);
                        $studentData['school_id'] = $request->new_school_id;
                        $studentData['class_section_id'] = $request->new_class_section_id;
                        $studentData['roll_number'] = (int)$roll_number_db + 1;
                        $roll_number_db++;

                        // Handle potential null constraints in target database
                        $studentData['uni_no'] = $studentData['uni_no'] ?? '';
                        $studentData['cast'] = $studentData['cast'] ?? 'GENERAL';

                        // 2. Switch to target database
                        Config::set('database.connections.school.database', $targetDb);
                        DB::purge('school');
                        DB::connection('school')->reconnect();

                        // 3. Insert into target database
                        // Check if user already exists by email/mobile
                        $existingUser = \App\Models\User::on('school')->where('email', $userData['email'])->first();
                        if (!$existingUser) {
                            $newUser = \App\Models\User::on('school')->create($userData);
                            // Assign role
                            $newUser->assignRole('student');
                        } else {
                            $newUser = $existingUser;
                        }

                        if ($guardianData) {
                            $existingGuardian = \App\Models\User::on('school')->where('email', $guardianData['email'])->first();
                            if (!$existingGuardian) {
                                $newGuardian = \App\Models\User::on('school')->create($guardianData);
                                $newGuardian->assignRole('guardian');
                            } else {
                                $newGuardian = $existingGuardian;
                            }
                            $studentData['guardian_id'] = $newGuardian->id;
                        } else {
                            // If guardian is required but missing
                            $studentData['guardian_id'] = $studentData['guardian_id'] ?? 0;
                        }

                        $studentData['user_id'] = $newUser->id;
                        \App\Models\Students::on('school')->create($studentData);

                        // 4. Switch back to source database
                        Config::set('database.connections.school.database', $sourceDb);
                        DB::purge('school');
                        DB::connection('school')->reconnect();

                        // 5. Deactivate in source (Optional, but good for data integrity)
                        $sourceStudent->user->update(['status' => 0]);
                    }
                } else {
                    // Same database transfer
                    $updateStudent[] = array(
                        'id' => $id,
                        'class_section_id' => $request->new_class_section_id,
                        'roll_number' => (int)$roll_number_db + 1,
                        'school_id' => $request->new_school_id ?? Auth::user()->school_id
                    );
                    $roll_number_db++;
                }
            }

            if (!empty($updateStudent)) {
                foreach($updateStudent as $student){
                    $user = $this->student->builder()->where('id', $student['id'])->with('user')->first();
                    $studentSubject = $this->studentSubject->builder()->where('student_id', $user->user_id)->get();
                    if($studentSubject->count() > 0){
                        foreach($studentSubject as $subject){
                            $subject->delete();
                        }
                    }
                }
                $this->student->upsert($updateStudent,['id'],['class_section_id','roll_number', 'school_id']);
            }

            DB::commit();
            ResponseService::successResponse("Data Updated Successfully");
        } catch (Throwable $e) {
            DB::rollback();
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse($e->getMessage());
        }
    }
}
