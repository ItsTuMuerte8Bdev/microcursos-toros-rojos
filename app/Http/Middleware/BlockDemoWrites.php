<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class BlockDemoWrites
{
    // Recibe la solicitud entrante
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (!$user) return $next($request);
        
        $isDemo = false;
        // Determine si el usuario es de demostración
        try{
            $demoIds = config('demo.ids', []);
            $demoEmails = config('demo.emails', []);
            // check by numeric id
            $uid = $user->getAuthIdentifier();
            if ($uid && in_array(intval($uid), $demoIds, true)) $isDemo = true;
            // fallback: check correo/email
            $email = $user->email ?? ($user->correo ?? null);
            if ($email && in_array(strtolower($email), array_map('strtolower', $demoEmails), true)) $isDemo = true;
        }catch(\Throwable $e){
            $isDemo = false;
        }

        // Solo permite métodos de solo lectura para usuarios de demostración
        $writeMethods = ['POST','PUT','PATCH','DELETE'];
        if ($isDemo && in_array($request->method(), $writeMethods, true)) {
            // Para solicitudes AJAX/JSON, retorna un JSON con mensaje
            if ($request->expectsJson() || $request->ajax() || $request->isJson() || str_contains($request->header('X-Requested-With',''), 'XMLHttpRequest')) {
                return response()->json([
                    'demo' => true,
                    'message' => 'Cuenta de demostración: los cambios no se guardaron en la base de datos. Crea una cuenta propia para una experiencia real.'
                ], 200);
            }

            // Para solicitudes normales, redirige de vuelta con mensaje flash
            return redirect()->back()->withInput($request->all())->with('status', 'Cuenta de demostración: los cambios no se guardaron en la base de datos. Crea una cuenta propia para una experiencia real.');
        }

        return $next($request);
    }
}
