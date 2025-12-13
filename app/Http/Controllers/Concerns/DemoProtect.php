<?php
// Declaración de lenguaje
namespace App\Http\Controllers\Concerns;
// Ruta conectada al controlador
trait DemoProtect
// Permite reusabilidad de código en múltiples controladores
{
    /*- Determina si un usuario es una cuenta de demostración según config('demo')
     - Acepta null y devuelve false en ese caso.*/
    protected function isDemoUser($user): bool
    {
        if (!$user) return false;
        try {
            $demoIds = config('demo.ids', []);
            $demoEmails = config('demo.emails', []);
            $uid = $user->getAuthIdentifier();
            if ($uid && in_array(intval($uid), $demoIds, true)) return true;
            $email = $user->email ?? ($user->correo ?? null);
            if ($email && in_array(strtolower($email), array_map('strtolower', $demoEmails), true)) return true;
        } catch (\Throwable $e) {
            // Silenciar y tratar como no-demo en caso de error de configuración
        }
        return false;
    }
}
