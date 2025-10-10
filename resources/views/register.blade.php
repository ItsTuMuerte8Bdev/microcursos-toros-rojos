@extends('layouts.app')

@section('title','Regístrate | Los Toros Rojos')

@section('content')
<style>
/* Miniatura del toro para la página de registro */
.toro-thumb {
    max-width: 300px;
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
        <div class="card shadow-sm mx-auto" style="max-width:760px; width:100%;">
            <div class="card-body p-4 d-flex flex-column" style="min-height:420px;">
                <!-- Top: title + intro -->
                <div>
                    <h3 class="card-title mb-3">Crea tu cuenta</h3>
                    <p class="text-muted">Regístrate para acceder a los cursos y guardar tu progreso.</p>
                </div>

                <!-- Middle: form -->
                <div class="col-9 col-md-6">
                    <form method="POST" action="{{ url('/register') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="name" class="form-label">Nombre(s)</label>
                            <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus class="form-control" />
                        </div>

                        <div class="mb-3">
                            <label for="apellido" class="form-label">Apellido(s)</label>
                            <input id="apellido" type="text" name="apellido" value="{{ old('apellido') }}" required class="form-control" />
                        </div>

                        <div class="mb-3">
                            <label for="sexo" class="form-label">Sexo</label>
                            <select id="sexo" name="sexo" class="form-select">
                                <option value="">-- Preferir no decir --</option>
                                <option value="masculino" {{ old('sexo') == 'masculino' ? 'selected' : '' }}>Masculino</option>
                                <option value="femenino" {{ old('sexo') == 'femenino' ? 'selected' : '' }}>Femenino</option>
                                <option value="no binario" {{ old('sexo') == 'no binario' ? 'selected' : '' }}>No binario</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Correo electrónico</label>
                            <input id="email" type="email" name="email" value="{{ old('email') }}" required class="form-control" />
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Contraseña</label>
                            <input id="password" type="password" name="password" required class="form-control" />
                        </div>

                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label">Confirmar contraseña</label>
                            <input id="password_confirmation" type="password" name="password_confirmation" required class="form-control" />
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn--primary">Registrarse</button>
                        </div>
                    </form>
                </div>

                <!-- Bottom: login link pinned to card bottom -->
                <div class="mt-auto text-center">
                    <hr class="my-3" />
                    <div>
                        ¿Ya tienes cuenta? <a href="{{ url('/login') }}">Inicia sesión</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right column: small toro image -->
    <div class="col-12 col-md-4 col-lg-3 mb-4 mb-md-0 pt-4 text-center text-md-end d-flex align-items-center justify-content-center">
        <div>
            <img src="{{ asset('images/Toro Registro.png') }}" alt="Toro Registro" class="toro-thumb" />
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// Accesibilidad: enfocar el primer campo al cargar
document.addEventListener('DOMContentLoaded', function(){
    var name = document.getElementById('name');
    if(name) name.focus();
});

// Helper: mostrar alerta bootstrap dentro del formulario
function showFormAlert(type, message) {
    // remove existing alert
    var existing = document.getElementById('form-alert');
    if (existing) existing.remove();

    var div = document.createElement('div');
    div.id = 'form-alert';
    div.className = 'alert alert-' + type + ' mt-3';
    div.setAttribute('role', 'alert');
    div.innerText = message;

    var formCol = document.querySelector('.col-9.col-md-6');
    if (formCol) formCol.prepend(div);
}

// AJAX email check on blur
document.addEventListener('DOMContentLoaded', function(){
    var email = document.getElementById('email');
    var password = document.getElementById('password');
    var passwordConfirmation = document.getElementById('password_confirmation');

    if(email){
        email.addEventListener('blur', function(){
            var value = email.value.trim();
            if(!value) return;

            // quick client-side format check
            var re = /\S+@\S+\.\S+/;
            if(!re.test(value)){
                showFormAlert('warning', 'Ingresa un correo con formato válido');
                return;
            }

            // call server endpoint
            fetch('{{ url('/register/check-email') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ email: value })
            }).then(function(res){
                return res.json();
            }).then(function(json){
                if(!json.ok){
                    showFormAlert('danger', json.message || 'El correo parece inválido');
                } else {
                    // remove alert if any
                    var existing = document.getElementById('form-alert');
                    if (existing) existing.remove();
                }
            }).catch(function(err){
                // network or server error: show neutral warning
                showFormAlert('warning', 'No se pudo verificar el correo en este momento');
            });
        });
    }

    // password/confirmation live check
    function checkPasswords(){
        if(!password || !passwordConfirmation) return;
        if(passwordConfirmation.value.length === 0) return;

        if(password.value !== passwordConfirmation.value){
            showFormAlert('danger', 'La contraseña y la confirmación no coinciden');
        } else {
            var existing = document.getElementById('form-alert');
            if (existing) existing.remove();
        }
    }

    if(passwordConfirmation){
        passwordConfirmation.addEventListener('input', checkPasswords);
        password.addEventListener('input', checkPasswords);
    }

});
</script>
@endpush
