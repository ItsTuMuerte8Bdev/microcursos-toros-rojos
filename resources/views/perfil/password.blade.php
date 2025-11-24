@extends('layouts.app')

{{-- ------ Inicio: perfil/password.blade.php ------ --}}

@section('title','Cambiar contraseña')

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-md-6">
        <x-card classes="card shadow-sm mx-auto" style="max-width:720px; width:100%;" body-classes="card-body p-4 d-flex flex-column" body-style="min-height:320px;">
                <div>
                    <h3 class="card-title mb-2">Cambiar contraseña</h3>
                    <p class="text-muted">Introduce tu contraseña actual y la nueva para actualizarla.</p>
                </div>

                @if(session('status'))
                    <div class="alert alert-success">{{ session('status') }}</div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="flex-fill d-flex align-items-center">
                    <div class="w-100">
                        <div class="mx-auto" style="max-width:420px;">
                        <form method="POST" action="{{ route('password.change.post') }}">
                            @csrf

                            <div class="mb-3">
                                <label class="form-label">Contraseña actual</label>
                                <input type="password" name="current_password" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Nueva contraseña</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Confirmar nueva contraseña</label>
                                <input type="password" name="password_confirmation" class="form-control" required>
                            </div>

                            <div class="d-flex gap-2">
                                <button class="btn btn--primary" type="submit">Cambiar contraseña</button>
                                <a href="{{ route('perfil.edit') }}" class="btn btn--simple">Volver</a>
                            </div>
                        </form>
                        </div>
                    </div>
                </div>

                <div class="mt-auto text-center">
                    <!-- optional footer inside card -->
                </div>
        </x-card>
    </div>
</div>
@endsection

{{-- ------ Fin: perfil/password.blade.php ------ --}}
