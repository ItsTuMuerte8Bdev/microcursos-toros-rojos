<?php
namespace App\Http\Controllers;

use App\Models\Resultado;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Http\Controllers\Concerns\DemoProtect;

class ResultadoController extends Controller
{
    use DemoProtect;
    public function index(Request $request)
    {
        $query = Resultado::query();
        if ($request->has('id_usuario')) {
            $query->where('id_usuario', $request->id_usuario);
        }
        return $query->with(['usuario', 'evaluacion'])->get();
    }

    public function store(Request $request)
    {
        // Validate required fields (id_usuario is taken from the authenticated user)
        $validated = $request->validate([
            'id_evaluacion' => 'required|integer|exists:evaluaciones,id_evaluacion',
            'puntaje_obtenido' => 'required|integer',
            'fecha' => 'nullable|string',
        ]);

        // Use authenticated user id to avoid spoofing
        $validated['id_usuario'] = auth()->id();
        if ($this->isDemoUser(auth()->user())) {
            return response()->json(['demo' => true, 'message' => 'Cuenta de demostración: el resultado no se guardó en la base de datos.'], 200);
        }

        // Convert incoming ISO datetime (e.g. 2025-10-05T02:58:19.304Z) to MySQL datetime
        if (!empty($validated['fecha'])) {
            try {
                $validated['fecha'] = Carbon::parse($validated['fecha'])->toDateTimeString();
            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Fecha inválida',
                    'errors' => ['fecha' => $e->getMessage()],
                ], 422);
            }
        } else {
            $validated['fecha'] = Carbon::now()->toDateTimeString();
        }

        try {
            $resultado = Resultado::create($validated);
            return response()->json($resultado, 201);
        } catch (\Exception $e) {
            \Log::error('Resultado store error: '.$e->getMessage(), ['userId' => auth()->id()]);
            return response()->json(['message' => 'Error guardando resultado'], 500);
        }
    }
}
