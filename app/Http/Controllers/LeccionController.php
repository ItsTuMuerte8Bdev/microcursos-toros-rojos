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
        return view('lecciones.view', compact('leccion'));
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
