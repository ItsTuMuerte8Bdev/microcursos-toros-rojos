@extends('layouts.app')

{{-- ------ Inicio: password/email.blade.php ------ --}}

@section('title','Recuperar contraseña | Los Toros Rojos')

@section('content')
<style>
/* Small toro thumbnail reused */
.toro-thumb {
    max-width: 250px;
    border-radius: 8px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.12);
    display: inline-block;
}
.toro-caption {
    color: rgba(255,255,255,0.9);
    font-size: 0.9rem;
    margin-top: 0.5rem;
}
</style>

<div class="row justify-content-center align-items-center pt-5" style="min-height:75vh;">
    <!-- Left column: form (card) -->
    <div class="col-12 col-md-9 d-flex justify-content-center">
            <x-card classes="card shadow-sm mx-auto" style="max-width:760px; width:100%;" body-classes="card-body p-4 d-flex flex-column" body-style="min-height:320px;">
                <!-- Top: title + intro -->
                <div>
                    <h3 class="card-title mb-3">Recuperar contraseña</h3>
                    <p class="text-muted">Introduce tu correo y te enviaremos un enlace para restablecer tu contraseña.</p>
                </div>

                <!-- Middle: form -->
                <div>
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('password.email') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="email" class="form-label">Correo electrónico</label>
                            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus class="form-control" />
                            @error('email')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Enviar enlace de restablecimiento</button>
                        </div>
                    </form>
                </div>

                <!-- Bottom: back to login -->
                <div class="mt-auto text-center">
                    <hr class="my-3" />
                    <div>
                        ¿Recuerdas tu contraseña? <a href="{{ url('/login') }}">Inicia sesión</a>
                    </div>
                </div>
            </x-card>
    </div>

    <!-- Right column: small toro image -->
    <div class="col-12 col-md-4 col-lg-3 pt-4 mb-4 mb-md-0 text-center text-md-end d-flex align-items-center justify-content-center">
        <div>
            <img src="{{ asset('images/Toro Registro.png') }}" alt="Toro" class="toro-thumb" />
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// Accessibility: focus first input when loaded
document.addEventListener('DOMContentLoaded', function(){
    var email = document.getElementById('email');
    if(email) email.focus();
});
</script>
@endpush

{{-- ------ Fin: password/email.blade.php ------ --}}
