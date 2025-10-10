<?php
namespace App\Http\Controllers;

use App\Models\Leccion;
use App\Models\Progreso;
use Illuminate\Http\Request;

class LeccionController extends Controller
{
    // API: return leccion with its modulo and curso info
    public function show($id)
    {
        return Leccion::with(['modulo.curso'])->findOrFail($id);
    }

    // Render blade view for a leccion
    public function view($id)
    {
        $leccion = Leccion::with(['modulo.curso'])->findOrFail($id);
        // Intentar calcular lección anterior/siguiente a nivel de CURSO
        $cursoId = $leccion->modulo->id_curso;

        $leccionesEnCurso = Leccion::select('lecciones.*')
            ->join('modulos', 'lecciones.id_modulo', '=', 'modulos.id_modulo')
            ->where('modulos.id_curso', $cursoId)
            ->orderBy('modulos.orden', 'asc')
            ->orderBy('lecciones.id_leccion', 'asc')
            ->get();

        $prev = null;
        $next = null;

        // Encontrar la posición de la lección actual en la lista ordenada
        $index = $leccionesEnCurso->search(function($l) use ($leccion){
            return (int)$l->id_leccion === (int)$leccion->id_leccion;
        });

        if ($index !== false) {
            if ($index > 0) {
                $prev = $leccionesEnCurso->get($index - 1);
            }
            if ($index < $leccionesEnCurso->count() - 1) {
                $next = $leccionesEnCurso->get($index + 1);
            }
        } else {
            // Fallback: buscar sólo en el mismo módulo si no se encontró en el conjunto del curso
            $prev = Leccion::where('id_modulo', $leccion->id_modulo)
                ->where('id_leccion', '<', $leccion->id_leccion)
                ->orderBy('id_leccion', 'desc')
                ->first();

            $next = Leccion::where('id_modulo', $leccion->id_modulo)
                ->where('id_leccion', '>', $leccion->id_leccion)
                ->orderBy('id_leccion', 'asc')
                ->first();
        }

        return view('lecciones.view', compact('leccion', 'prev', 'next'));
    }

    // Optionally record local progress (not used directly here)
    public function storeProgress(Request $request)
    {
        $data = $request->validate([
            'id_usuario' => 'required|integer',
            'id_leccion' => 'required|integer',
            'completado' => 'required|boolean',
        ]);
        $progreso = Progreso::create($data);
        return response()->json($progreso, 201);
    }
}
