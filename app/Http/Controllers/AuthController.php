<?php

namespace App\Http\Controllers;

// "Librerías" de Laravel utilizadas
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
// Modelos utilizados, se tienen que especificar aqui para usar el use de trait posteriormente
use App\Models\User;
use App\Models\Usuario;
use App\Mail\VerifyEmail;
use App\Http\Controllers\Concerns\DemoProtect;

class AuthController extends Controller
// Herencia de clase del controlador main
{
    // Declaración de uso del trait DemoProtect
    use DemoProtect;
    // ------  Autenticación: login / logout  ------
    public function login(Request $request)
    {
        // Verificación de credenciales
        $request->validate(['email' => 'required|email', 'password' => 'required|string']);
        $credentials = ['correo' => $request->input('email'), 'password' => $request->input('password')];
        // Intento de login
        $redirectTo = $request->input('redirect_to');
        if (Auth::attempt($credentials, $request->filled('remember'))) {
            if ($redirectTo && str_starts_with($redirectTo, '/') && !str_contains($redirectTo, '://')) {
                return redirect()->to($redirectTo);
            }
            return redirect()->intended('/microcursos');
            // Redirección a la página deseada o al dashboard | Fin condicional de la función
        }
        return back()->withErrors(['email' => 'Credenciales inválidas'])->withInput();
        // Fin en caso de no cumplir la condición
    }

    public function logout(Request $request)
    // Función de cierre de sesión
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }

    // ------  Registro y verificación por email  ------
    public function register(Request $request)
    {
        $request->validate([
            // Se establecen los criterios de validación para el registro (Conforme a la BD, y algunos extras)
            'name' => 'required|string|max:100',
            'apellido' => 'required|string|max:100',
            'email' => 'required|email|max:150|unique:usuarios,correo',
            'password' => 'required|confirmed|min:6',
            'sexo' => 'nullable|in:masculino,femenino,no binario',
        ]);

        $token = Str::random(64);
        $usuario = Usuario::create([
            // Creación del usuario en la base de datos
            'nombre' => $request->input('name'),
            'apellido' => $request->input('apellido'),
            'correo' => $request->input('email'),
            'password' => Hash::make($request->input('password')),
            'rol' => 'empleado',
            'sexo' => $request->input('sexo') ?? 'no binario',
            'estado' => 'inactivo',
            'fecha_registro' => now(),
            'verification_token' => $token,
            'verification_sent_at' => now(),
            'email_verified_at' => null,
        ]);
        // Envío del correo de verificación
        $this->sendVerificationEmail($usuario, $token);
        return redirect('/login')->with('status', 'Hemos enviado un correo para verificar tu cuenta. Revisa tu bandeja.');
    }

    public function verifyEmail($token)
    {
        // Verificación del token y activación de la cuenta
        $usuario = Usuario::where('verification_token', $token)->first();
        if (!$usuario) return redirect('/login')->withErrors(['verification' => 'Token inválido o expirado']);
        // Verificar si el token ha expirado (24 horas)
        if ($usuario->verification_sent_at && now()->diffInHours($usuario->verification_sent_at) > 24) {
            return redirect()->route('register.verify.pending')->withErrors(['verification' => 'El enlace ha expirado. Por favor solicita un reenvío.']);
        }
        // Activar cuenta
        $usuario->update(['verification_token' => null, 'email_verified_at' => now(), 'estado' => 'activo', 'verification_sent_at' => null]);
        Auth::login($usuario);
        return redirect('/microcursos')->with('status', 'Correo verificado. Bienvenido!');
    }

    public function showVerifyPending()
    {
        return view('auth.verify_pending');
    }

    public function resendVerification(Request $request)
    {
        // Si se dejo el primer token expirar, permite reenviar el correo de verificación
        $request->validate(['email' => 'required|email']);
        $usuario = Usuario::where('correo', $request->input('email'))->first();
        if (!$usuario) return back()->withErrors(['email' => 'No existe una cuenta con ese correo']);
        if ($usuario->email_verified_at) return back()->with('status', 'El correo ya fue verificado');

        $token = Str::random(64);
        $usuario->update(['verification_token' => $token, 'verification_sent_at' => now()]);
        if (!$this->sendVerificationEmail($usuario, $token)) {
            return back()->withErrors(['email' => 'No se pudo enviar el correo de verificación en este momento']);
        }
        return back()->with('status', 'Correo de verificación reenviado. Revisa tu bandeja.');
    }

    public function changeEmail(Request $request)
    {
        // Permite cambiar el correo antes de verificar la cuenta
        $request->validate(['old_email' => 'required|email', 'new_email' => 'required|email|unique:usuarios,correo']);
        $usuario = Usuario::where('correo', $request->input('old_email'))->first();
        if (!$usuario) return back()->withErrors(['old_email' => 'La cuenta original no fue encontrada']);
        if ($usuario->email_verified_at) return back()->withErrors(['old_email' => 'La cuenta ya está verificada']);

        $token = Str::random(64);
        $usuario->update(['correo' => $request->input('new_email'), 'verification_token' => $token, 'verification_sent_at' => now()]);
        if (!$this->sendVerificationEmail($usuario, $token)) {
            return back()->withErrors(['new_email' => 'No se pudo enviar el correo de verificación al nuevo email']);
        }
        return back()->with('status', 'Se actualizó el correo y se reenvi&oacute; el email de verificación.');
    }

    // ------  Validaciones y utilitarios públicos  ------
    public function checkEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $email = $request->input('email');
        if (Usuario::where('correo', $email)->exists()) return response()->json(['ok' => false, 'message' => 'El correo ya está en uso']);
        // Verificación básica de registros MX | Verifica que el correo sea real (Conforme a los criterios de MX)
        $domain = substr(strrchr($email, "@"), 1);
        $hasMx = $domain && (function_exists('checkdnsrr') ? checkdnsrr($domain, 'MX') : (function_exists('getmxrr') ? getmxrr($domain, $mx) : false));
        if (!$hasMx) return response()->json(['ok' => false, 'message' => 'No se encontraron registros MX para el dominio; verifica que el correo exista']);
        return response()->json(['ok' => true, 'message' => 'El correo parece válido']);
    }

    // ------  OAuth (Socialite)  ------
    public function redirectToProvider($provider)
    {
        // Redirección a la página de inicio de sesión del proveedor OAuth
        if (!in_array($provider, ['google', 'github'])) abort(404);
        if (!class_exists('\Laravel\Socialite\Facades\Socialite')) return response('Socialite is not installed. Run: composer require laravel/socialite', 501);
        $driver = \Laravel\Socialite\Facades\Socialite::driver($provider);
        return $provider === 'google' ? $driver->stateless()->redirect() : $driver->redirect();
    }

    public function handleProviderCallback(\Illuminate\Http\Request $request, $provider)
    {
        // Manejo del callback del proveedor OAuth
        if (!in_array($provider, ['google', 'github'])) abort(404);
        if (!class_exists('\Laravel\Socialite\Facades\Socialite')) return response('Socialite is not installed. Run: composer require laravel/socialite', 501);

        logger()->info('OAuth callback', ['provider' => $provider, 'query' => $request->query()]);
        // Intento de obtención del usuario OAuth
        try {
            $driver = \Laravel\Socialite\Facades\Socialite::driver($provider);
            $socialUser = $provider === 'google' ? $driver->stateless()->user() : $driver->user();
        } catch (\Exception $e) {
            logger()->error('Socialite callback failed', ['provider' => $provider, 'e' => $e->getMessage()]);
            try {
                $socialUser = \Laravel\Socialite\Facades\Socialite::driver($provider)->stateless()->user();
            } catch (\Exception $e2) {
                logger()->error('Socialite stateless fallback failed', ['provider' => $provider, 'e' => $e2->getMessage()]);
                return redirect('/login')->withErrors(['oauth' => 'Error al autenticar con '.$provider.'. Revisa los registros para más detalles.']);
            }
        }
        // Buscar o crear el usuario local en base al usuario OAuth para la BD e inicio de sesion
        $email = $socialUser->getEmail();
        if (empty($email)) return redirect('/login')->withErrors(['oauth' => 'No se obtuvo el correo del proveedor. Por favor usa registro tradicional o asegúrate que tu proveedor comparte el email.']);

        $usuario = Usuario::where('correo', $email)->first();
        if (!$usuario) {
            $fullName = $socialUser->getName() ?? $socialUser->getNickname() ?? 'Usuario';
            $parts = preg_split('/\s+/', trim($fullName), 2);
            $usuario = Usuario::create([
                'nombre' => $parts[0] ?? 'Usuario',
                'apellido' => $parts[1] ?? '',
                'correo' => $email,
                'password' => Str::random(24),
                'rol' => 'empleado',
                'proveedor_oauth' => $provider,
                'proveedor_id' => $socialUser->getId(),
                'fecha_registro' => now(),
                'estado' => 'activo',
                'sexo' => 'no binario',
                'email_verified_at' => now(),
            ]);
        } else {
            $changed = false;
            if (empty($usuario->proveedor_oauth)) { $usuario->proveedor_oauth = $provider; $changed = true; }
            if (empty($usuario->proveedor_id)) { $usuario->proveedor_id = $socialUser->getId(); $changed = true; }
            if (empty($usuario->email_verified_at)) { $usuario->email_verified_at = now(); $usuario->estado = 'activo'; $changed = true; }
            if ($changed) $usuario->save();
        }

        Auth::login($usuario, true);
        return redirect('/microcursos');
    }

    // ------  Perfil: ver y editar  ------
    public function editProfile()
    {
        $user = auth()->user();
        return view('perfil.edit', compact('user'));
    }

    // Actualización de información del perfil conforme a validaciones coordinadas con la BD
    public function updateProfile(Request $request)
    {
        $user = auth()->user();
        $request->validate(['nombre' => 'required|string|max:100', 'apellido' => 'required|string|max:100', 'sexo' => 'nullable|in:masculino,femenino,no binario']);
        if ($this->isDemoUser($user)) return redirect()->route('perfil.edit')->with('status', 'Cuenta de demostración: los cambios no se guardaron. Crea una cuenta propia para guardar cambios.');

        $user->fill(['nombre' => $request->input('nombre'), 'apellido' => $request->input('apellido'), 'sexo' => $request->input('sexo') ?? $user->sexo])->save();
        return redirect()->route('perfil.edit')->with('status', 'Información actualizada correctamente');
    }

    // ------  Contraseña  ------
    public function showChangePassword()
    {
        return view('perfil.password');
    }

    public function changePassword(Request $request)
    {
        $request->validate(['current_password' => 'required|string', 'password' => 'required|confirmed|min:6']);
        $user = auth()->user();
        if ($this->isDemoUser($user)) return redirect()->route('perfil.edit')->with('status', 'Cuenta de demostración: la contraseña no fue modificada. Crea una cuenta propia para cambiar tu contraseña.');
        if (!Hash::check($request->input('current_password'), $user->password)) return back()->withErrors(['current_password' => 'La contraseña actual es incorrecta']);

        $user->password = $request->input('password');
        $user->save();
        $request->session()->regenerate();
        return redirect()->route('perfil.edit')->with('status', 'Contraseña cambiada correctamente');
    }

    // ------  Administración: cambios rápidos  ------
    public function changeUserRole(Request $request)
    {
        $request->validate(['id_usuario' => 'required|integer|exists:usuarios,id_usuario', 'rol' => 'required|in:admin,instructor,empleado']);
        $auth = auth()->user();
        if (!$auth || $auth->rol !== 'admin') return response()->json(['error' => 'no autorizado'], 403);
        if ($this->isDemoUser($auth)) return response()->json(['demo' => true, 'message' => 'Cuenta de demostración: no está permitido modificar roles desde este perfil.'], 200);

        $u = Usuario::find($request->input('id_usuario'));
        if (!$u) return response()->json(['error' => 'usuario no encontrado'], 404);
        $u->rol = $request->input('rol');
        $u->save();
        return response()->json(['ok' => true, 'id_usuario' => $u->id_usuario, 'rol' => $u->rol]);
    }

    // ------  Helpers privados (DRY)  ------
    private function sendVerificationEmail(Usuario $usuario, string $token): bool
    {
        try {
            Mail::to($usuario->correo)->send(new VerifyEmail($usuario, $token));
            return true;
        } catch (\Exception $e) {
            logger()->warning('Failed to send verification email: '.$e->getMessage());
            return false;
        }
    }
}
