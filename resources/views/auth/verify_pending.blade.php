@extends('layouts.app')

@section('title','Verifica tu correo')

@section('content')
<div class="row justify-content-center pt-5">
    <div class="col-12 col-md-8">
        <div class="card">
            <div class="card-body">
                <h4>Verificación pendiente</h4>

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

                <p>Hemos enviado un correo con un enlace de verificación. Si no lo recibes, puedes reenviarlo o cambiar la dirección aquí.</p>

                <hr/>
                <h5>Reenviar correo de verificación</h5>
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

                <hr/>
                <h5>Cambiar dirección de correo</h5>
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
                        <button class="btn btn-secondary">Cambiar correo y reenviar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
