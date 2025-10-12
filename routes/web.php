<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\AuthController;
use App\Models\User;
use App\Models\InstructorEmployeeAssignment;

Route::get('/', function () {
    return view('index');
});

Route::get('/microcursos', [App\Http\Controllers\CursoController::class, 'indexView'])->name('microcursos.index');



// Cursos - vista y API
Route::get('/cursos', [App\Http\Controllers\CursoController::class, 'indexView'])->name('cursos.index');
Route::get('/cursos/{id}/ver', [App\Http\Controllers\CursoController::class, 'showView'])->name('cursos.show');
// Redirect to the next lesson to continue (first incomplete) or to the course view
Route::get('/cursos/{id}/continuar', [App\Http\Controllers\CursoController::class, 'continue'])->name('cursos.continuar');

// Manage downloads UI
Route::get('/descargas/administrar', function(){
    return view('downloads.manage');
})->name('downloads.manage');

// Lección - vista y API
Route::get('/lecciones/{id}', [App\Http\Controllers\LeccionController::class, 'view'])->name('lecciones.view');

// API endpoints (served via web routes as requested)
Route::get('/api/cursos', [App\Http\Controllers\CursoController::class, 'index'])->name('api.cursos.index');
Route::get('/api/cursos/{id}', [App\Http\Controllers\CursoController::class, 'show'])->name('api.cursos.show');
Route::middleware('auth')->group(function(){
    Route::get('/api/cursos/{id}/progress', [App\Http\Controllers\CursoController::class, 'progress'])->name('api.cursos.progress');
    // Batch progress (POST with JSON { ids: [id1,id2], id_usuario: ... })
    Route::post('/api/cursos/progress-batch', [App\Http\Controllers\CursoController::class, 'progressBatch'])->name('api.cursos.progressBatch');
    // Record progreso (requires auth)
    Route::post('/api/progreso', [App\Http\Controllers\ProgresoController::class, 'store'])->name('api.progreso.store');
    // Record evaluation results (requires auth)
    Route::post('/api/resultados', [App\Http\Controllers\ResultadoController::class, 'store'])->name('api.resultados.store');
    // Endpoint para sincronización desde cliente offline
    Route::post('/offline/sync-progress', [App\Http\Controllers\OfflineController::class, 'syncProgress'])->name('offline.sync_progress');
});
Route::get('/api/lecciones/{id}', [App\Http\Controllers\LeccionController::class, 'show'])->name('api.lecciones.show');

Route::get('/directorio', function () {
    return view('directorio');
});

// Show login page (kept as GET) and named so links work
Route::get('/login', function () { return view('login'); })->name('login.form');
// Handle login POST
Route::post('/login', [AuthController::class, 'login'])->name('login');

// Logout
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// OAuth routes (google, github)
Route::get('/auth/{provider}', [AuthController::class, 'redirectToProvider'])->name('auth.redirect');
Route::get('/auth/{provider}/callback', [AuthController::class, 'handleProviderCallback'])->name('auth.callback');

// Basic register page (named)
Route::get('/register', function () { return view('register'); })->name('register');
// Handle registration POST
Route::post('/register', [AuthController::class, 'register'])->name('register.post');
// AJAX email check used by the registration form
Route::post('/register/check-email', [AuthController::class, 'checkEmail'])->name('register.check_email');
// Email verification link
Route::get('/register/verify/{token}', [AuthController::class, 'verifyEmail'])->name('register.verify');
// Verification pending UI, resend and change email
Route::get('/register/verify-pending', [AuthController::class, 'showVerifyPending'])->name('register.verify.pending');
Route::post('/register/resend-verification', [AuthController::class, 'resendVerification'])->name('register.verify.resend');
Route::post('/register/change-email', [AuthController::class, 'changeEmail'])->name('register.verify.change_email');

// Password reset request page: show the form at /password/email (named password.request)
Route::get('/password/email', function(){ return view('password.email'); })->name('password.request');

// Handle the POST that requests a password reset link. This is a simple placeholder
// that validates the email and returns a status message. Replace with proper
// logic (Password::sendResetLink) when ready.
Route::post('/password/email', function(Request $request){
    $request->validate(['email' => 'required|email']);

    // Placeholder: in a real app you would call the Password broker to send the reset link.
    // For now, just pretend we sent it and redirect back with a status message.
    return back()->with('status', 'Si existe una cuenta con ese correo, recibirás un enlace de restablecimiento.');
})->name('password.email');

// Admin small area
Route::get('/admin/cursos', [App\Http\Controllers\CursoController::class, 'adminIndex'])->name('admin.cursos.index');

// Admin: página para limpieza opt-in de PWA (solo para administradores autenticados)
Route::middleware('auth')->get('/admin/cleanup-pwa', function(){
    $user = auth()->user();
    if (!$user || ($user->rol ?? null) !== 'admin') {
        abort(403);
    }
    // Prevent demo admins from accessing this tool
    $demoIds = [1,2];
    $demoEmails = ['admin@demo.com','juan@demo.com'];
    $isDemo = false;
    try{ $uid = $user->getAuthIdentifier(); if ($uid && in_array(intval($uid), $demoIds, true)) $isDemo = true; $email = $user->email ?? ($user->correo ?? null); if ($email && in_array(strtolower($email), array_map('strtolower',$demoEmails), true)) $isDemo = true; }catch(\Throwable $e){}
    if ($isDemo) {
        return redirect('/admin/cursos')->with('status','Cuenta de demostración: no tiene permiso para usar esta herramienta.');
    }
    return view('admin.cleanup-pwa');
})->name('admin.cleanup_pwa');

// Admin: asignar empleados a instructores (GET + POST) -- usa la tabla instructor_employee_assignments
Route::middleware('auth')->match(['get','post'],'/admin/assign-instructors', function(Request $request){
    $user = auth()->user();
    if (!$user || ($user->rol ?? null) !== 'admin') abort(403);

    // Prevent demo-admins from performing mutating actions
    $demoIds = [1,2];
    $demoEmails = ['admin@demo.com','juan@demo.com'];
    $isDemoAdmin = false;
    try{ $uid = $user->getAuthIdentifier(); if ($uid && in_array(intval($uid), $demoIds, true)) $isDemoAdmin = true; $email = $user->email ?? ($user->correo ?? null); if ($email && in_array(strtolower($email), array_map('strtolower',$demoEmails), true)) $isDemoAdmin = true; }catch(\Throwable $e){}

    if ($request->isMethod('post')){
        \Log::info('assign-instructors POST payload', $request->all());
        $data = $request->validate([ 'instructor_id' => 'required|integer', 'employee_ids' => 'array' ]);
        if ($isDemoAdmin){
            return back()->with('status', 'Cuenta de demostración: no está permitido modificar asignaciones desde este perfil.');
        }
        $instructor = intval($data['instructor_id']);
        $emps = isset($data['employee_ids']) ? array_map('intval', $data['employee_ids']) : [];

        // eliminar asignaciones previas para este instructor y guardar nuevas
        $created = 0;
        if (!$isDemoAdmin) {
            InstructorEmployeeAssignment::where('instructor_id', $instructor)->delete();
            foreach ($emps as $eid){
                $row = InstructorEmployeeAssignment::create(['instructor_id' => $instructor, 'employee_id' => $eid]);
                if ($row) $created++;
            }
        } else {
            // Do not persist changes for demo-admins
            \Log::info('assign-instructors prevented for demo admin', ['user_id' => $user->getAuthIdentifier(), 'payload' => $request->all()]);
        }

        return back()->with('status',"Asignaciones guardadas. Registros creados: {$created}");
    }

    // cargar usuarios para selector (instructors y empleados)
    $instructors = User::where('rol','instructor')->get();
    $employees = User::where('rol','employee')->orWhere('rol','empleado')->get();

    // cargar asignaciones actuales en forma de array [ instructor_id => [employee_id, ...] ]
    $assignments = InstructorEmployeeAssignment::all()->groupBy('instructor_id')->map(function($group){
        return $group->pluck('employee_id')->toArray();
    })->toArray();

    // ordenar instructores según query param ?sort=name|count (por defecto name)
    $sort = $request->query('sort','name');
    if ($sort === 'count'){
        // calcular conteos y ordenar desc
        $counts = array_map(function($a){ return count($a); }, $assignments);
        $instructors = $instructors->sortByDesc(function($ins) use ($counts){
            $id = $ins->getKey();
            return $counts[$id] ?? 0;
        })->values();
    } else {
        // ordenar por nombre asc
        $instructors = $instructors->sortBy(function($ins){ return mb_strtolower($ins->name); })->values();
    }

    return view('admin.assign-instructors', compact('instructors','employees','assignments','sort'));
})->name('admin.assign_instructors');

// Instructor: vista con opciones útiles (solo para rol instructor)
Route::middleware('auth')->get('/instructor/dashboard', function(){
    $user = auth()->user();
    if (!$user || ($user->rol ?? null) !== 'instructor') abort(403);

    // Cargar asignaciones desde la tabla
    $assigned = InstructorEmployeeAssignment::where('instructor_id', $user->id)->pluck('employee_id')->toArray();
    // debug logging to help track why instructors may not see assignments
    \Log::info('instructor.dashboard debug: found assignment ids', ['user_id' => $user->id, 'assigned' => $assigned]);
    $myEmployees = [];
    if (!empty($assigned)){
        // The User model uses a custom primary key (id_usuario). Use the model's
        // key name instead of hard-coded 'id' so Eloquent queries the correct column.
        $pk = (new User)->getKeyName();
        $myEmployees = User::whereIn($pk, $assigned)->get();
        \Log::info('instructor.dashboard debug: loaded employees', ['user_id' => $user->id, 'pk' => $pk, 'loaded' => $myEmployees->pluck($pk)->toArray()]);
    }

    return view('instructor.dashboard', compact('myEmployees'));
})->name('instructor.dashboard');

// Instructor: ver progreso de un empleado asignado
Route::middleware('auth')->get('/instructor/employee/{id}/progress', function($id){
    $user = auth()->user();
    if (!$user || ($user->rol ?? null) !== 'instructor') abort(403);

    // check assignment exists
    $assigned = InstructorEmployeeAssignment::where('instructor_id', $user->id)->pluck('employee_id')->toArray();
    if (!in_array((int)$id, array_map('intval', $assigned))) abort(403);

    // load employee and courses for progress display
    $employee = \App\Models\Usuario::where('id_usuario', (int)$id)->firstOrFail();
    $cursos = App\Models\Curso::with('modulos.lecciones')->get();

    return view('instructor.employee_progress', compact('employee','cursos'));
})->name('instructor.employee.progress');

// Instructor: exportar empleados asignados (CSV)
Route::middleware('auth')->get('/instructor/export-assigned', function(){
    $user = auth()->user();
    if (!$user || ($user->rol ?? null) !== 'instructor') abort(403);
    // detect demo instructors to mask sensitive fields
    $demoIds = [1,2];
    $demoEmails = ['admin@demo.com','juan@demo.com'];
    $isDemo = false;
    try{ $uid = $user->getAuthIdentifier(); if ($uid && in_array(intval($uid), $demoIds, true)) $isDemo = true; $email = $user->email ?? ($user->correo ?? null); if ($email && in_array(strtolower($email), array_map('strtolower',$demoEmails), true)) $isDemo = true; }catch(\Throwable $e){}

    $assigned = InstructorEmployeeAssignment::where('instructor_id', $user->id)->pluck('employee_id')->toArray();
    $rows = [];
    if (!empty($assigned)){
        $rows = \App\Models\Usuario::whereIn('id_usuario', $assigned)
            ->select('id_usuario','nombre','apellido','correo','rol')
            ->orderBy('nombre')
            ->get()
            ->map(function($u) use ($isDemo){
                return [
                    'id_usuario' => $u->id_usuario,
                    'nombre' => $u->nombre,
                    'apellido' => $u->apellido,
                    'correo' => $isDemo ? 'correo_oculto@demo.local' : $u->correo,
                    'rol' => $u->rol,
                ];
            })->toArray();
    }

    $filename = 'empleados_asignados_' . ($user->id ?? 'unknown') . '_' . date('Ymd_His') . '.csv';

    $callback = function() use ($rows) {
        $out = fopen('php://output', 'w');
        // headers
        fputcsv($out, ['id_usuario','nombre','apellido','correo','rol']);
        foreach($rows as $r) fputcsv($out, [$r['id_usuario'],$r['nombre'],$r['apellido'],$r['correo'],$r['rol']]);
        fclose($out);
    };

    return response()->streamDownload($callback, $filename, ['Content-Type' => 'text/csv']);
})->name('instructor.export_assigned');

// Profile and password change (protected)
Route::middleware('auth')->group(function(){
    Route::get('/perfil', [AuthController::class, 'editProfile'])->name('perfil.edit');
    Route::post('/perfil', [AuthController::class, 'updateProfile'])->name('perfil.update');

    Route::get('/password/change', [AuthController::class, 'showChangePassword'])->name('password.change');
    Route::post('/password/change', [AuthController::class, 'changePassword'])->name('password.change.post');
});

// Admin: change user role (AJAX)
Route::middleware('auth')->post('/admin/user/change-role', [AuthController::class, 'changeUserRole'])->name('admin.user.change_role');