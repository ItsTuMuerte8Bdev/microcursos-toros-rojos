@extends('layouts.app')

{{-- ------ Inicio: auth/verify_pending.blade.php ------ --}}

@section('title','Verifica tu correo')

@section('content')
<div class="row justify-content-center pt-5">
    <div class="col-12">
        <div class="card">
            <div class="card-body p-4 d-flex flex-column" style="min-height:320px;">
                <div>
                    <h4>Verificación pendiente</h4>
                    <p class="text-muted">Hemos enviado un correo con un enlace de verificación. Si no lo recibes, puedes reenviarlo o cambiar la dirección aquí.</p>
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
                        <div class="mx-auto" style="max-width:720px;">
                            <div class="d-flex flex-column gap-3 align-items-center">
                                <div class="card shadow-sm p-3" style="min-width:220px; max-width:420px; width:100%;">
                                    <h5 class="mb-3 text-center">Reenviar correo de verificación</h5>
                                    <form method="POST" action="{{ route('register.verify.resend') }}">
                                        @csrf
                                        <div class="mb-3">
                                            <label for="email" class="form-label">Correo registrado</label>
                                            <input id="email" name="email" class="form-control" value="{{ old('email') }}" required />
                                        </div>
                                        <div class="d-grid gap-2">
                                            <button class="btn btn-primary">Reenviar comprobación</button>
                                        </div>
                                    </form>
                                </div>

                                <div class="card shadow-sm p-3" style="min-width:220px; max-width:420px; width:100%;">
                                    <h5 class="mb-3 text-center">Cambiar dirección de correo</h5>
                                    <form method="POST" action="{{ route('register.verify.change_email') }}">
                                        @csrf
                                        <div class="mb-3">
                                            <label for="old_email" class="form-label">Correo actual</label>
                                            <input id="old_email" name="old_email" class="form-control" value="{{ old('old_email') }}" required />
                                        </div>
                                        <div class="mb-3">
                                            <label for="new_email" class="form-label">Nuevo correo</label>
                                            <input id="new_email" name="new_email" class="form-control" value="{{ old('new_email') }}" required />
                                        </div>
                                        <div class="d-grid gap-2">
                                            <button class="btn btn--simple">Cambiar correo y reenviar</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-auto text-center">
                    <!-- optional footer inside card -->
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

{{-- ------ Fin: auth/verify_pending.blade.php ------ --}}
