<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class BlockDemoWrites
{
    /**
     * Demo user identifiers (can be IDs or emails). Adjust as needed.
     * By default we block users with id 1 and 2 (seeded demo accounts).
     */
    protected array $demoIds = [1,2];
    protected array $demoEmails = ['admin@demo.com','juan@demo.com'];

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (!$user) return $next($request);

        $isDemo = false;
        try{
            // check by numeric id
            $uid = $user->getAuthIdentifier();
            if ($uid && in_array(intval($uid), $this->demoIds, true)) $isDemo = true;
            // fallback: check correo/email
            $email = $user->email ?? ($user->correo ?? null);
            if ($email && in_array(strtolower($email), array_map('strtolower', $this->demoEmails), true)) $isDemo = true;
        }catch(\Throwable $e){
            // if any issue reading user, don't block by default
            $isDemo = false;
        }

        // Only block mutating HTTP methods
        $writeMethods = ['POST','PUT','PATCH','DELETE'];
        if ($isDemo && in_array($request->method(), $writeMethods, true)) {
            // For AJAX / JSON requests return a friendly JSON result so frontend
            // can save locally or show informative message.
            if ($request->expectsJson() || $request->ajax() || $request->isJson() || str_contains($request->header('X-Requested-With',''), 'XMLHttpRequest')) {
                return response()->json([
                    'demo' => true,
                    'message' => 'Cuenta de demostración: los cambios no se guardaron en la base de datos. Crea una cuenta propia para una experiencia real.'
                ], 200);
            }

            // For regular form submissions redirect back with status message.
            // Preserve previous input where appropriate.
            return redirect()->back()->withInput($request->all())->with('status', 'Cuenta de demostración: los cambios no se guardaron en la base de datos. Crea una cuenta propia para una experiencia real.');
        }

        return $next($request);
    }
}
