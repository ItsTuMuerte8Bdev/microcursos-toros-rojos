<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Usuario;
use App\Mail\VerifyEmail;

class AuthController extends Controller
{
    // Handle email/password login
    public function login(Request $request)
    {
        // The app uses 'correo' as username column in Usuarios table
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        // Map form field 'email' to DB column 'correo'
        $credentials = ['correo' => $request->input('email'), 'password' => $request->input('password')];

        // end debug

        $redirectTo = $request->input('redirect_to');

        if(Auth::attempt($credentials, $request->filled('remember'))){
            $user = Auth::user();
            // If you'd like completed courses to be auto-saved, handle that here.
            // e.g. sync local session progress into DB.

            // If redirect_to provided, only redirect to internal paths for safety
            if ($redirectTo && str_starts_with($redirectTo, '/') && !str_contains($redirectTo, '://')) {
                return redirect()->to($redirectTo);
            }

            return redirect()->intended('/microcursos');
        }

        return back()->withErrors(['email' => 'Credenciales inválidas'])->withInput();
    }

    // Logout
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }

    // Handle registration
    public function register(Request $request)
    {

        $request->validate([
            'name' => 'required|string|max:100',
            'apellido' => 'required|string|max:100',
            'email' => 'required|email|max:150|unique:usuarios,correo',
            'password' => 'required|confirmed|min:6',
            'sexo' => 'nullable|in:masculino,femenino,no binario',
        ]);

        // Create the user in the usuarios table with a verification token
        $token = Str::random(64);
        $usuario = Usuario::create([
            'nombre' => $request->input('name'),
            'apellido' => $request->input('apellido'),
            'correo' => $request->input('email'),
            'password' => Hash::make($request->input('password')),
            // default role for self-registered users
            'rol' => 'empleado',
            'proveedor_oauth' => null,
            'proveedor_id' => null,
            'sexo' => $request->input('sexo') ?? 'no binario',
            'estado' => 'inactivo', // until verified
            'fecha_registro' => now(),
            'verification_token' => $token,
            // store when the verification email was sent so the UI / audits can show it
            'verification_sent_at' => now(),
            // explicitly null until verified
            'email_verified_at' => null,
        ]);

        // Send verification email (best-effort)
        try {
            Mail::to($usuario->correo)->send(new VerifyEmail($usuario, $token));
        } catch (\Exception $e) {
            // Log or ignore: in local env mail may not be configured
            logger()->warning('Failed to send verification email: '.$e->getMessage());
        }

        // Redirect user to login with a status message to check their email
        return redirect('/login')->with('status', 'Hemos enviado un correo para verificar tu cuenta. Revisa tu bandeja.');
    }

    // AJAX endpoint to validate an email quickly (format, uniqueness, MX records)
    public function checkEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $email = $request->input('email');

        // Check uniqueness in usuarios table
        if (Usuario::where('correo', $email)->exists()) {
            return response()->json(['ok' => false, 'message' => 'El correo ya está en uso']);
        }

        // Check domain MX records to heuristically verify that the email domain can receive mail
        $domain = substr(strrchr($email, "@"), 1);
        $hasMx = false;
        if ($domain) {
            if (function_exists('checkdnsrr')) {
                $hasMx = checkdnsrr($domain, 'MX');
            } elseif (function_exists('getmxrr')) {
                $hasMx = getmxrr($domain, $mxhosts);
            }
        }

        if (!$hasMx) {
            return response()->json(['ok' => false, 'message' => 'No se encontraron registros MX para el dominio; verifica que el correo exista']);
        }

        return response()->json(['ok' => true, 'message' => 'El correo parece válido']);
    }

    // Verify email token
    public function verifyEmail($token)
    {
        $usuario = Usuario::where('verification_token', $token)->first();
        if (!$usuario) {
            return redirect('/login')->withErrors(['verification' => 'Token inválido o expirado']);
        }

        // token expiry: 24 hours
        if ($usuario->verification_sent_at && now()->diffInHours($usuario->verification_sent_at) > 24) {
            return redirect()->route('register.verify.pending')->withErrors(['verification' => 'El enlace ha expirado. Por favor solicita un reenvío.']);
        }

        $usuario->verification_token = null;
        $usuario->email_verified_at = now();
        $usuario->estado = 'activo';
        $usuario->verification_sent_at = null;
        $usuario->save();

        // Log in the user
        Auth::login($usuario);

        return redirect('/microcursos')->with('status', 'Correo verificado. Bienvenido!');
    }

    // Show a page explaining the user must verify their email and allow re-send/change
    public function showVerifyPending()
    {
        return view('auth.verify_pending');
    }

    // Resend verification email
    public function resendVerification(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $usuario = Usuario::where('correo', $request->input('email'))->first();
        if (!$usuario) {
            return back()->withErrors(['email' => 'No existe una cuenta con ese correo']);
        }

        if ($usuario->email_verified_at) {
            return back()->with('status', 'El correo ya fue verificado');
        }

        // generate new token and set sent_at
        $token = Str::random(64);
        $usuario->verification_token = $token;
        $usuario->verification_sent_at = now();
        $usuario->save();

        try{
            Mail::to($usuario->correo)->send(new VerifyEmail($usuario, $token));
        } catch (\Exception $e) {
            logger()->warning('Failed to send verification email: '.$e->getMessage());
            return back()->withErrors(['email' => 'No se pudo enviar el correo de verificación en este momento']);
        }

        return back()->with('status', 'Correo de verificación reenviado. Revisa tu bandeja.');
    }

    // Change the email associated to the account (before verification)
    public function changeEmail(Request $request)
    {
        $request->validate([
            'old_email' => 'required|email',
            'new_email' => 'required|email|unique:usuarios,correo',
        ]);

        $usuario = Usuario::where('correo', $request->input('old_email'))->first();
        if (!$usuario) {
            return back()->withErrors(['old_email' => 'La cuenta original no fue encontrada']);
        }

        if ($usuario->email_verified_at) {
            return back()->withErrors(['old_email' => 'La cuenta ya está verificada']);
        }

        // update email, generate new token
        $token = Str::random(64);
        $usuario->correo = $request->input('new_email');
        $usuario->verification_token = $token;
        $usuario->verification_sent_at = now();
        $usuario->save();

        try{
            Mail::to($usuario->correo)->send(new VerifyEmail($usuario, $token));
        } catch (\Exception $e) {
            logger()->warning('Failed to send verification email after change: '.$e->getMessage());
            return back()->withErrors(['new_email' => 'No se pudo enviar el correo de verificación al nuevo email']);
        }

        return back()->with('status', 'Se actualizó el correo y se reenvi&oacute; el email de verificación.');
    }

    // Redirect to provider (google, github)
    public function redirectToProvider($provider)
    {
        // Will use Socialite if available. If Socialite isn't installed yet, instruct the developer.
        if(!in_array($provider, ['google','github'])){
            abort(404);
        }

        // Use Laravel Socialite if available
        if(class_exists('\Laravel\Socialite\Facades\Socialite')){
            $driver = \Laravel\Socialite\Facades\Socialite::driver($provider);
            // Use stateless for Google to avoid session/state issues behind proxies
            // or when SameSite cookies block the state cookie. Stateful flow is
            // still used for other providers by default.
            if ($provider === 'google') {
                return $driver->stateless()->redirect();
            }

            return $driver->redirect();
        }

        // Fallback: show message
        return response('Socialite is not installed. Run: composer require laravel/socialite', 501);
    }

    // Handle provider callback
    public function handleProviderCallback(\Illuminate\Http\Request $request, $provider)
    {
        if(!in_array($provider, ['google','github'])){
            abort(404);
        }

        if(!class_exists('\Laravel\Socialite\Facades\Socialite')){
            return response('Socialite is not installed. Run: composer require laravel/socialite', 501);
        }

        // Log incoming request to debug missing `code` issues (Google returns ?code=... on success)
        logger()->info('OAuth callback request', [
            'provider' => $provider,
            'query' => $request->query(),
            'input' => $request->all(),
            'method' => $request->method(),
        ]);

        // Log provider config for debugging (redirect uri, client id presence)
        try{
            $svc = config('services.' . $provider);
        } catch(\Throwable $t){
            $svc = null;
        }
        logger()->info('Socialite provider config', ['provider' => $provider, 'config' => $svc]);

        try{
            // Prefer stateless for Google to bypass state mismatch issues seen in
            // environments with proxying or SameSite cookie restrictions. For
            // other providers, keep the default (stateful) behavior.
            $driver = \Laravel\Socialite\Facades\Socialite::driver($provider);
            if ($provider === 'google') {
                $socialUser = $driver->stateless()->user();
            } else {
                $socialUser = $driver->user();
            }
        } catch(\Exception $e){
            // Log full exception for diagnostics (message, code and stack)
            logger()->error('Socialite callback exception (initial)', [
                'provider' => $provider,
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Try stateless fallback: this bypasses state check and sometimes helps when the
            // session/state was lost by the time the callback arrives (proxies, SWs, SameSite).
            try{
                logger()->info('Attempting stateless fallback for Socialite provider', ['provider' => $provider]);
                $socialUser = \Laravel\Socialite\Facades\Socialite::driver($provider)->stateless()->user();
                logger()->info('Stateless Socialite succeeded', ['provider' => $provider, 'id' => $socialUser->getId(), 'email' => $socialUser->getEmail()]);
            } catch(\Exception $e2){
                logger()->error('Socialite callback exception (stateless fallback)', [
                    'provider' => $provider,
                    'message' => $e2->getMessage(),
                    'code' => $e2->getCode(),
                    'trace' => $e2->getTraceAsString(),
                ]);

                return redirect('/login')->withErrors(['oauth' => 'Error al autenticar con '.$provider.'. Revisa los registros para más detalles.']);
            }
        }


        // Ensure we have an email (GitHub may not provide one if it's private)
        $email = $socialUser->getEmail();
        if (empty($email)) {
            return redirect('/login')->withErrors(['oauth' => 'No se obtuvo el correo del proveedor. Por favor usa registro tradicional o asegúrate que tu proveedor comparte el email.']);
        }

        // Find or create local Usuario (app uses `usuarios` table)
        $usuario = Usuario::where('correo', $email)->first();
        if (!$usuario) {
            // try to split name into first/last
            $fullName = $socialUser->getName() ?? $socialUser->getNickname() ?? 'Usuario';
            $parts = preg_split('/\s+/', trim($fullName), 2);
            $first = $parts[0] ?? 'Usuario';
            $last = $parts[1] ?? '';

            $usuario = Usuario::create([
                'nombre' => $first,
                'apellido' => $last,
                'correo' => $email,
                // the mutator will hash this
                'password' => Str::random(24),
                'rol' => 'empleado',
                'proveedor_oauth' => $provider,
                'proveedor_id' => $socialUser->getId(),
                'fecha_registro' => now(),
                'estado' => 'activo',
                'sexo' => 'no binario',
                'verification_token' => null,
                'verification_sent_at' => null,
                'email_verified_at' => now(),
            ]);
        } else {
            // Update provider info if missing
            $changed = false;
            if (empty($usuario->proveedor_oauth)) { $usuario->proveedor_oauth = $provider; $changed = true; }
            if (empty($usuario->proveedor_id)) { $usuario->proveedor_id = $socialUser->getId(); $changed = true; }
            if (empty($usuario->email_verified_at)) { $usuario->email_verified_at = now(); $usuario->estado = 'activo'; $changed = true; }
            if ($changed) $usuario->save();
        }

        // Log in the usuario
        Auth::login($usuario, true);

        // Here: migrate any session-stored completed courses into user's profile (DB).
        // Example placeholder: \App\Services\CourseProgress::syncSessionToUser($user, session('course_progress'));

        return redirect('/microcursos');
    }

    // Show edit profile form
    public function editProfile()
    {
        $user = auth()->user();
        return view('perfil.edit', compact('user'));
    }

    // Update profile (name, apellido, sexo)
    public function updateProfile(Request $request)
    {
        $user = auth()->user();
        $request->validate([
            'nombre' => 'required|string|max:100',
            'apellido' => 'required|string|max:100',
            'sexo' => 'nullable|in:masculino,femenino,no binario',
        ]);

        $user->nombre = $request->input('nombre');
        $user->apellido = $request->input('apellido');
        $user->sexo = $request->input('sexo') ?? $user->sexo;
        $user->save();

        return redirect()->route('perfil.edit')->with('status', 'Información actualizada correctamente');
    }

    // Show change password form
    public function showChangePassword()
    {
        return view('perfil.password');
    }

    // Handle password change
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|confirmed|min:6',
        ]);

        $user = auth()->user();

        // Verify current password
        if (!\Illuminate\Support\Facades\Hash::check($request->input('current_password'), $user->password)) {
            return back()->withErrors(['current_password' => 'La contraseña actual es incorrecta']);
        }

        $user->password = $request->input('password'); // mutator will hash
        $user->save();

        // Regenerate session to avoid fixation
        $request->session()->regenerate();

        return redirect()->route('perfil.edit')->with('status', 'Contraseña cambiada correctamente');
    }

    // Admin endpoint: change a user's role (only minimal info required)
    public function changeUserRole(Request $request)
    {
        $request->validate([
            'id_usuario' => 'required|integer|exists:usuarios,id_usuario',
            'rol' => 'required|in:admin,instructor,empleado',
        ]);

        $auth = auth()->user();
        if (!$auth || $auth->rol !== 'admin') {
            return response()->json(['error' => 'no autorizado'], 403);
        }

        $u = \App\Models\Usuario::find($request->input('id_usuario'));
        if (!$u) return response()->json(['error' => 'usuario no encontrado'], 404);

        $u->rol = $request->input('rol');
        $u->save();

        return response()->json(['ok' => true, 'id_usuario' => $u->id_usuario, 'rol' => $u->rol]);
    }
}
