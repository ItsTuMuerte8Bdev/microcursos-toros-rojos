@extends('layouts.app')

{{-- ------ Inicio: perfil/edit.blade.php ------ --}}

@section('title','Editar perfil')

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-md-6">
        <x-card classes="card shadow-sm mx-auto" style="max-width:720px; width:100%;" body-classes="card-body p-4 d-flex flex-column" body-style="min-height:320px;">
                <div>
                    <h3 class="card-title mb-2">Editar información personal</h3>
                    <p class="text-muted">Actualiza tu nombre, apellido y género. Esta información se mostrará en tu perfil.</p>
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
                            <form method="POST" action="{{ route('perfil.update') }}">
                                @csrf

                                <div class="mb-3">
                                    <label class="form-label">Nombre</label>
                                    <input type="text" name="nombre" class="form-control" value="{{ old('nombre', $user->nombre) }}" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Apellido</label>
                                    <input type="text" name="apellido" class="form-control" value="{{ old('apellido', $user->apellido) }}" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Sexo</label>
                                    <select name="sexo" class="form-select">
                                        <option value="">-- Selecciona --</option>
                                        <option value="masculino" @if(old('sexo', $user->sexo)=='masculino') selected @endif>Masculino</option>
                                        <option value="femenino" @if(old('sexo', $user->sexo)=='femenino') selected @endif>Femenino</option>
                                        <option value="no binario" @if(old('sexo', $user->sexo)=='no binario') selected @endif>No binario</option>
                                    </select>
                                </div>

                                <div class="d-flex gap-2">
                                    <button class="btn btn--primary" type="submit">Guardar</button>
                                    <a href="{{ route('password.change') }}" class="btn btn--simple">Cambiar contraseña</a>
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

{{-- ------ Fin: perfil/edit.blade.php ------ --}}
