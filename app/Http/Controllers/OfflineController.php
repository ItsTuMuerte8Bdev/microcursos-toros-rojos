<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Progreso;
use App\Http\Controllers\Concerns\DemoProtect;

class OfflineController extends Controller
{
    use DemoProtect;
    // Sync progress sent from the client while offline
    public function syncProgress(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Usuario no autenticado'], 401);
        }
        if ($this->isDemoUser($user)) {
            return response()->json(['demo' => true, 'message' => 'Cuenta de demostración: el progreso no se guardó en la base de datos.'], 200);
        }

        $data = $request->validate([
            'progresses' => 'required|array'
        ]);

        $saved = 0;
        foreach ($data['progresses'] as $p) {
            // expect { user, courseId, lessonId, progress, updated_at }
            $id_leccion = $p['lessonId'] ?? ($p['id_leccion'] ?? null);
            $completado = null;
            if (is_array($p['progress'] ?? null)) {
                // if progress contains 'completado' boolean
                $completado = isset($p['progress']['completado']) ? (bool)$p['progress']['completado'] : null;
            } else {
                // allow boolean directly
                $completado = isset($p['progress']) && ($p['progress'] === true || $p['progress'] === false) ? (bool)$p['progress'] : null;
            }

            if (!$id_leccion || $completado === null) continue;

            try{
                // Use the existing ProgresoController logic's expectations: id_leccion and completado
                $attrs = ['id_usuario' => $user->getKey(), 'id_leccion' => intval($id_leccion)];
                $values = ['completado' => (bool)$completado];
                if ($values['completado']) $values['fecha_completado'] = now(); else $values['fecha_completado'] = null;
                Progreso::updateOrCreate($attrs, $values);
                $saved++;
            }catch(\Exception $e){
                // log and continue
                \Log::warning('offline.syncProgress: failed saving progreso', ['err' => $e->getMessage(), 'payload' => $p]);
            }
        }

        return response()->json(['saved' => $saved], 200);
    }
}
