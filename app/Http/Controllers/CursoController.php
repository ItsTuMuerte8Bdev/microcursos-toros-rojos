<?php
namespace App\Http\Controllers;

use App\Models\Curso;
use Illuminate\Http\Request;
use App\Models\Progreso;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Auth;

class CursoController extends Controller
{
    public function index(Request $request)
    {
        // Indexación de cursos con paginación y filtros
        $q = $request->query('q');
        $category = $request->query('category');
        $status = $request->query('status'); // no tiene que ver con el estado del curso en sí, sino con el progreso del usuario
        $perPage = (int) $request->query('per_page', 12);

        $query = Curso::with(['categoria'])->orderBy('titulo');

        if ($q) {
            $query->where(function($sub) use ($q){
                $sub->where('titulo', 'like', "%{$q}%")
                    ->orWhere('descripcion', 'like', "%{$q}%");
            });
        }

        if ($category) {
            $query->where('id_categoria', $category);
        }

        // Filtro por estado del curso para el usuario autenticado

        $paginated = $query->paginate($perPage)->appends($request->query());

        // Return paginated JSON
        return response()->json($paginated);
    }

    public function show($id)
    {
        return Curso::with(['categoria', 'creador', 'modulos.lecciones', 'modulos.evaluaciones'])->findOrFail($id);
    }

    public function store(Request $request)
    {
        $curso = Curso::create($request->all());
        return response()->json($curso, 201);
    }

    // Muestra una vista con todos los cursos
    public function indexView()
    {
        // Pasar los cursos para que Blade pueda renderizarlos del lado del servidor
        $cursos = Curso::with(['categoria','creador','modulos.lecciones'])->get();
        return view('microcursos', compact('cursos'));
    }

    // Vista detallada de un curso específico
    public function showView($id)
    {
        $curso = Curso::with(['categoria', 'creador', 'modulos.lecciones'])->findOrFail($id);

        // Calcular el progreso por módulo para el usuario actual (evitar N+1 utilizando consultas agrupadas)
        $userId = Auth::id();
        $moduleProgress = [];
        if ($userId) {
            $moduleIds = $curso->modulos->pluck('id_modulo')->toArray();

            // Opciones por modulo
            $totals = DB::table('lecciones')
                ->whereIn('id_modulo', $moduleIds)
                ->groupBy('id_modulo')
                ->select('id_modulo', DB::raw('count(id_leccion) as total'))
                ->pluck('total', 'id_modulo')
                ->toArray();

            // Lo que esta hecho por modulo por el usuario
            $completed = DB::table('progreso')
                ->join('lecciones', 'progreso.id_leccion', '=', 'lecciones.id_leccion')
                ->whereIn('lecciones.id_modulo', $moduleIds)
                ->where('progreso.id_usuario', $userId)
                ->where('progreso.completado', true)
                ->groupBy('lecciones.id_modulo')
                ->select('lecciones.id_modulo', DB::raw('count(DISTINCT progreso.id_leccion) as completed'))
                ->pluck('completed', 'id_modulo')
                ->toArray();
            // Calcular el progreso por módulo
            foreach ($curso->modulos as $m) {
                $mid = $m->id_modulo;
                $total = isset($totals[$mid]) ? intval($totals[$mid]) : 0;
                $comp = isset($completed[$mid]) ? intval($completed[$mid]) : 0;
                $percent = $total === 0 ? 0 : intval(($comp / $total) * 100);
                $moduleProgress[$mid] = ['percent' => $percent, 'completed' => $comp, 'total' => $total];
            }
        } else {
            // Si no está autenticado, todo está en 0's
            foreach ($curso->modulos as $m) {
                $moduleProgress[$m->id_modulo] = ['percent' => 0, 'completed' => 0, 'total' => $m->lecciones->count()];
            }
        }

        // Pasar también la lista completa de cursos para navegación
        $cursosList = Curso::orderBy('titulo')->get();
        return view('cursos.show', compact('curso', 'moduleProgress', 'cursosList'));
    }

    // Se usa para pasar a la lección que este en progreso o la primera no empezada
    public function continue(Request $request, $id)
    {
        $curso = Curso::with(['modulos.lecciones'])->findOrFail($id);
        $userId = Auth::id();

        // Contruye la lista de progreso por lecciones
        $lessonList = [];
        foreach ($curso->modulos->sortBy('orden') as $mod) {
            $lecs = $mod->lecciones->sortBy('id_leccion');
            foreach ($lecs as $l) $lessonList[] = $l->id_leccion;
        }

        if (!$userId) {
            // Si no está autenticado, redirigir a la vista del curso
            return redirect()->route('cursos.show', ['id' => $id]);
        }

        // Encuentra la siguiente lección no completada
        $completed = \App\Models\Progreso::where('id_usuario', $userId)
            ->where('completado', true)
            ->whereIn('id_leccion', $lessonList)
            ->pluck('id_leccion')
            ->toArray();

        $next = null;
        foreach ($lessonList as $lid) {
            if (!in_array($lid, $completed)) { $next = $lid; break; }
        }

        if ($next) {
            return redirect()->route('lecciones.view', ['id' => $next]);
        }

        // Si todo está completado, redirigir a la vista del curso
        return redirect()->route('cursos.show', ['id' => $id]);
    }

    // Retorna el progreso de un curso específico para el usuario autenticado
    public function progress(Request $request, $id)
    {
        // Determina qué usuario verificar: usuario autenticado por defecto; el admin puede pasar id_usuario
        $requestedUserId = $request->query('id_usuario');
        $authUser = Auth::user();
        if ($authUser && $authUser->rol === 'admin' && $requestedUserId) {
            $userId = (int) $requestedUserId;
        } else {
            $userId = Auth::id();
        }
        if (!$userId) return response()->json(['error' => 'no authenticated user'], 401);

        $curso = Curso::with('modulos.lecciones')->findOrFail($id);
        $totalLecciones = 0;
        foreach($curso->modulos as $m) $totalLecciones += $m->lecciones->count();
        if ($totalLecciones === 0) return response()->json(['percent' => 0, 'moduleProgress' => []]);

        // Calcular lecciones completadas
        $completed = DB::table('progreso')
            ->where('id_usuario', $userId)
            ->where('completado', true)
            ->whereIn('id_leccion', function($q) use ($curso){
                $q->select('id_leccion')->from('lecciones')->whereIn('id_modulo', function($q2) use ($curso){
                    $ids = $curso->modulos->pluck('id_modulo')->toArray();
                    $q2->select('id_modulo')->from('modulos')->whereIn('id_modulo', $ids);
                });
            })
            ->distinct()
            ->count('id_leccion');

        $percent = $totalLecciones === 0 ? 0 : intval(($completed / $totalLecciones) * 100);
        // Previene inconsistencias
        if ($percent > 100) $percent = 100;

        // Calcular progreso por módulo
        $moduleIds = $curso->modulos->pluck('id_modulo')->toArray();
        // Opciones por modulo
        $totals = DB::table('lecciones')
            ->whereIn('id_modulo', $moduleIds)
            ->groupBy('id_modulo')
            ->select('id_modulo', DB::raw('count(id_leccion) as total'))
            ->pluck('total', 'id_modulo')
            ->toArray();
        // Lo que esta hecho por modulo por el usuario
        $completedPerModule = DB::table('progreso')
            ->join('lecciones', 'progreso.id_leccion', '=', 'lecciones.id_leccion')
            ->whereIn('lecciones.id_modulo', $moduleIds)
            ->where('progreso.id_usuario', $userId)
            ->where('progreso.completado', true)
            ->groupBy('lecciones.id_modulo')
            ->select('lecciones.id_modulo', DB::raw('count(DISTINCT progreso.id_leccion) as completed'))
            ->pluck('completed', 'id_modulo')
            ->toArray();
        // Calcular el progreso por módulo
        $moduleProgress = [];
        foreach ($curso->modulos as $m) {
            $mid = $m->id_modulo;
            $total = isset($totals[$mid]) ? intval($totals[$mid]) : 0;
            $comp = isset($completedPerModule[$mid]) ? intval($completedPerModule[$mid]) : 0;
            $pct = $total === 0 ? 0 : intval(($comp / $total) * 100);
            $moduleProgress[$mid] = [
                'id_modulo' => $mid,
                'titulo' => $m->titulo,
                'percent' => $pct,
                'completed' => $comp,
                'total' => $total,
            ];
        }

        return response()->json(['percent' => $percent, 'completed' => $completed, 'total' => $totalLecciones, 'moduleProgress' => $moduleProgress]);
    }

    // Retorna el progreso en batch para múltiples cursos para el usuario autenticado
    public function progressBatch(Request $request)
    {
        try {
            // EL id de usuario se maneja más abajo
            $data = $request->validate([
                'ids' => 'required|array',
                'ids.*' => 'integer',
                'id_usuario' => 'nullable|integer'
            ]);

            // El logueo de depuración si está habilitado
            if (config('app.debug')) {
                \Log::debug('progressBatch payload', ['payload' => $request->all()]);
            }

            // Determine which user to check: authenticated user by default; admin may pass id_usuario
            $requestedUserId = isset($data['id_usuario']) && $data['id_usuario'] !== null ? (int) $data['id_usuario'] : null;
            $authUser = Auth::user();
            if ($authUser && $authUser->rol === 'admin' && $requestedUserId) {
                $userId = $requestedUserId;
            } else {
                $userId = Auth::id();
            }
            if (!$userId) return response()->json(['error' => 'no authenticated user'], 401);
            $ids = $data['ids'];

            $onlyActive = $request->input('only_active_modules', false);
            // Defensive: ensure the modulos table exists before checking for column
            $hasModulosTable = Schema::hasTable('modulos');
            $hasActivo = $hasModulosTable && Schema::hasColumn('modulos', 'activo');

            // Build totals query
            $totalsQuery = DB::table('modulos')
                ->join('lecciones', 'lecciones.id_modulo', '=', 'modulos.id_modulo')
                ->whereIn('modulos.id_curso', $ids);
            if ($onlyActive && $hasActivo) {
                $totalsQuery->where('modulos.activo', 1);
            }
            $totals = $totalsQuery->groupBy('modulos.id_curso')
                ->select('modulos.id_curso as curso_id', DB::raw('count(lecciones.id_leccion) as total'))
                ->pluck('total', 'curso_id')
                ->toArray();

            // Build completed query
            $completedQuery = DB::table('progreso')
                ->join('lecciones', 'progreso.id_leccion', '=', 'lecciones.id_leccion')
                ->join('modulos', 'lecciones.id_modulo', '=', 'modulos.id_modulo')
                ->whereIn('modulos.id_curso', $ids)
                ->where('progreso.id_usuario', $userId)
                ->where('progreso.completado', true);
            if ($onlyActive && $hasActivo) {
                $completedQuery->where('modulos.activo', 1);
            }
            $completed = $completedQuery->groupBy('modulos.id_curso')
                ->select('modulos.id_curso as curso_id', DB::raw('count(DISTINCT progreso.id_leccion) as completed'))
                ->pluck('completed', 'curso_id')
                ->toArray();

            $results = [];
            foreach($ids as $cursoId){
                $total = isset($totals[$cursoId]) ? intval($totals[$cursoId]) : 0;
                $comp = isset($completed[$cursoId]) ? intval($completed[$cursoId]) : 0;
                $percent = $total === 0 ? 0 : intval(($comp / $total) * 100);
                $results[$cursoId] = ['percent' => $percent, 'completed' => $comp, 'total' => $total];
            }

            return response()->json($results);
        } catch (\Exception $e) {
            // Log the exception with full trace to help debugging
            \Log::error('progressBatch failed', ['message' => $e->getMessage(), 'exception' => $e]);
            // Return a controlled JSON error so client can parse it
            return response()->json(['error' => 'server_error', 'message' => $e->getMessage()], 500);
        }
    }

    // Basic admin list (very small admin area)
    public function adminIndex()
    {
        $cursos = Curso::with('creador')->get();
        // Load minimal user info for admin (only fields we will display/manage)
        $usuarios = \App\Models\Usuario::select('id_usuario','nombre','apellido','rol')->get();
        return view('admin.cursos.index', compact('cursos','usuarios'));
    }
}
