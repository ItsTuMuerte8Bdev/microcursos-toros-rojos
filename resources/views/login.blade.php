@extends('layouts.app')

{{-- ------ Inicio: login.blade.php ------ --}}

@section('title','Iniciar sesión | Los Toros Rojos')

@section('content')
<style>
/* Estilos de botones OAuth para la página de inicio de sesión */
.btn-google {
    background-color: #db4437; /* Google red */
    color: #fff !important;
    border: 1px solid #c33b30;
    box-shadow: 0 6px 18px rgba(219,68,55,0.12);
    border-radius: 8px;
    font-weight: 600;
}
.btn-google:hover, .btn-google:focus {
    background-color: #c33b30;
    color: #fff !important;
}
.btn-github {
    background-color: #333; /* GitHub dark gray */
    color: #fff !important;
    border: 1px solid #222;
    box-shadow: 0 6px 18px rgba(0,0,0,0.12);
    border-radius: 8px;
    font-weight: 600;
}
.btn-github:hover, .btn-github:focus {
    background-color: #222;
    color: #fff !important;
}
.oauth-container a i {
    color: rgba(255,255,255,0.95);
}
.oauth-info {
    color: rgba(255,255,255,0.92);
}
</style>

<div class="row justify-content-center align-items-center pt-5" style="min-height:75vh;">
    <!-- Left column: form (card) -->
    <div class="col-12 col-md-7 d-flex justify-content-center">
        <x-card classes="card shadow-sm mx-auto" style="max-width:520px; width:100%;" body-classes="card-body p-4 d-flex flex-column" body-style="min-height:360px;">
                <!-- Top: title + intro -->
                <div>
                    <h3 class="card-title mb-3">Iniciar sesión</h3>
                    <p class="text-muted">Usa tu cuenta para acceder a tus cursos y continuar tu progreso.</p>
                </div>

                <!-- Middle: form -->
                <div>
                    <!-- Email / Password form -->
                    <form method="POST" action="{{ url('/login') }}">
                        @csrf
                        @if(session('status'))
                            <div class="alert alert-info">{{ session('status') }}<br><small>Si no lo ves en tu bandeja de entrada, revisa la carpeta de no deseados (spam).</small></div>
                        @endif
                        @if($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach($errors->all() as $err)
                                    <li>{{ $err }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        <div class="mb-3">
                            <label for="email" class="form-label">Correo electrónico</label>
                            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus class="form-control" />
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Contraseña</label>
                            <input id="password" type="password" name="password" required class="form-control" />
                        </div>

                            <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="remember" id="remember" />
                                <label class="form-check-label" for="remember">Recuérdame</label>
                            </div>
                            <div>
                                @if (\Illuminate\Support\Facades\Route::has('password.request'))
                                    <a href="{{ route('password.request') }}">¿Olvidaste tu contraseña?</a>
                                @else
                                    <a href="{{ url('/password/email') }}">¿Olvidaste tu contraseña?</a>
                                @endif
                            </div>
                        </div>

                        <!-- NOTA: los cursos completados se persisten automáticamente en el servidor; no se muestra checkbox -->

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Iniciar sesión</button>
                        </div>
                    </form>
                </div>

                <!-- Bottom: register link pinned to card bottom -->
                <div class="mt-auto text-center">
                    <hr class="my-3" />
                    <div>
                        ¿No tienes cuenta? <a href="{{ url('/register') }}">Regístrate</a>
                    </div>
                </div>
        </x-card>
    </div>

    <!-- Right column: info + OAuth -->
    <div class="col-12 col-md-5 mb-4 mb-md-0 text-center text-md-end">
        <h2 class="display-6 text-white">Microcursos Los Toros Rojos</h2>
        <p class="lead">Aprende a tu ritmo. Accede a tus cursos, retoma tu progreso y continúa donde lo dejaste.</p>

            <div class="d-flex flex-column align-items-center align-items-md-end">
            <div class="d-grid gap-2 w-100 w-md-100 mb-3 oauth-container">
                <a href="{{ url('/auth/google') }}" class="btn btn-google d-flex align-items-center justify-content-center">
                    <i class="bi bi-google me-2"></i> Iniciar sesión con Google
                </a>
                <a href="{{ url('/auth/github') }}" class="btn btn-github d-flex align-items-center justify-content-center">
                    <i class="bi bi-github me-2"></i> Iniciar sesión con GitHub
                </a>
            </div>

            <small class="oauth-info">Los inicios vía Google/GitHub crearán tu cuenta automáticamente si es la primera vez.</small>
        </div>
    </div>
</div>

@endsection

{{-- ------ Fin: login.blade.php ------ --}}

@push('scripts')
<script>
// Accesibilidad: enfocar el primer campo al cargar
document.addEventListener('DOMContentLoaded', function(){
    var email = document.getElementById('email');
    if(email) email.focus();
});
</script>
@endpush
