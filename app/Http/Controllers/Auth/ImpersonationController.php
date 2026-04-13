<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Http\Request;

class ImpersonationController extends Controller
{
    public function loginAsStaff($staffId)
    {
        $admin = Auth::user();
        // Spatie permission check
        abort_unless($admin->can('login-as-staff'), 403);

        // 🔒 SAME SCHOOL / TENANT DATABASE
        $schoolDb = Session::get('school_database_name');

        if (!$schoolDb) {
            abort(403, 'School database not selected');
        }

        // Switch to school DB
        Config::set('database.connections.school.database', $schoolDb);
        DB::purge('school');
        DB::connection('school')->reconnect();
        DB::setDefaultConnection('school');

        // Staff fetch
        $staff = User::findOrFail($staffId);
         $staff->update([
        'impersonated_by' => $admin->id,
    ]);
        // Save admin id only for EXIT
        session(['impersonated_by' => $admin->id,'impersonation_return_url' => url()->previous(),"impersonation_admin"=>true]);

        // Login as staff (NO PASSWORD, NO 2FA)
        Auth::loginUsingId($staff->id);

        return redirect('/dashboard');
    }

    public function exit(Request $request)
{
    $adminId = session('impersonated_by');

    abort_if(!$adminId, 403);

    $returnUrl = session('impersonation_return_url', '/dashboard');

    // logout staff
    Auth::logout();

    // login back admin
    Auth::loginUsingId($adminId);

    // clear impersonation session
    session()->forget([
        'impersonated_by',
        'impersonation_return_url'
        ,"impersonation_admin"
    ]);

    // 🔐 safety fallback
    if (!str_starts_with($returnUrl, url('/'))) {
        $returnUrl = '/dashboard';
    }

    return redirect($returnUrl);
}
}
