<?php
namespace App\Http\Controllers;

use App\Models\Progreso;
use App\Models\Leccion;
use App\Models\Resultado;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\Concerns\DemoProtect;

class ProgresoController extends Controller
{
    use DemoProtect;
    public function index(Request $request)
    {
        $query = Progreso::query();
        if ($request->has('id_usuario')) {
            $query->where('id_usuario', $request->id_usuario);
        }
        return $query->with(['usuario', 'leccion'])->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'id_usuario' => 'sometimes|nullable|integer',
            'id_leccion' => 'required|integer',
            'completado' => 'required|boolean',
        ]);

        // Prefer authenticated user when available
        $userId = Auth::id() ?: ($data['id_usuario'] ?? null);
        if ($this->isDemoUser(Auth::user())) {
            return response()->json(['demo' => true, 'message' => 'Cuenta de demostración: el progreso se mantiene localmente pero no se guarda en la base de datos.'], 200);
        }
        if (!$userId) {
            return response()->json(['error' => 'Usuario no autenticado'], 401);
        }

        // Ensure leccion exists and check module evaluations (if any)
        $leccion = Leccion::with(['modulo.evaluaciones.preguntas'])->find($data['id_leccion']);
        if (!$leccion) return response()->json(['error' => 'Lección no encontrada'], 404);

        // If the module contains evaluations, require that the user has full score results
        $evaluaciones = $leccion->modulo->evaluaciones ?? [];
        if (count($evaluaciones) > 0 && $data['completado']){
            foreach($evaluaciones as $ev){
                // total possible score for this evaluation
                $totalPossible = $ev->preguntas->sum('puntaje');
                // if there are no preguntas, skip this evaluation
                if ($totalPossible <= 0) continue;
                // get the user's best/latest resultado for this evaluation
                $resultado = Resultado::where('id_usuario', $userId)
                    ->where('id_evaluacion', $ev->id_evaluacion)
                    ->orderBy('fecha', 'desc')
                    ->first();
                // If no resultado or score is less than totalPossible, deny marking complete
                if (!$resultado || intval($resultado->puntaje_obtenido) < intval($totalPossible)){
                    return response()->json(['error' => 'No has completado correctamente la evaluación asociada a esta lección'], 403);
                }
            }
        }

        // Upsert progreso record to avoid duplicates
        $attrs = [ 'id_usuario' => $userId, 'id_leccion' => $data['id_leccion'] ];
        $values = [ 'completado' => (bool)$data['completado'] ];
        if ($data['completado']) $values['fecha_completado'] = now();
        else $values['fecha_completado'] = null;

        $progreso = Progreso::updateOrCreate($attrs, $values);
        return response()->json($progreso, 200);
    }
}
