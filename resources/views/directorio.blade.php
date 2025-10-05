@extends('layouts.app')

@section('title','Directorio | Los Toros Rojos')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-0">Directorio</h1>
            <small class="text-muted">Conoce al equipo de Los Toros Rojos</small>
        </div>
        <div class="d-flex gap-2">
            <input class="form-control" id="searchPeople" placeholder="Buscar por nombre o puesto..." style="min-width:300px;" />
            <button id="clearSearch" class="btn btn-outline-secondary">Limpiar</button>
        </div>
    </div>

    <div class="row row-cols-1 row-cols-md-3 g-3" id="directoryList">
            <!-- Card: Director General -->
            <div class="col">
                <div class="card h-100 text-center">
                    <div class="card-body">
                        <img src="{{ asset('images/Logo General.png') }}" alt="Avatar" class="rounded-circle avatar-img" style="width:96px;height:96px;object-fit:cover;border:4px solid #b30000;">
                        <div class="person-details">
                            <h5 class="card-title mb-1">Jovan Bautista</h5>
                            <p class="text-muted mb-1">Director General</p>
                            <p class="small text-muted mb-1">Email: <a href="mailto:jovan@torosrojos.com">jovan@torosrojos.com</a></p>
                            <p class="small text-muted">Tel: <a href="tel:+525512345000">+52 55 1234 5000</a></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card: Jefe de Contenido -->
            <div class="col">
                <div class="card h-100 text-center">
                    <div class="card-body">
                        <img src="{{ asset('images/Toro Cursos.png') }}" alt="Avatar" class="rounded-circle avatar-img" style="width:96px;height:96px;object-fit:cover;border:4px solid #b30000;">
                        <div class="person-details">
                            <h5 class="card-title mb-1">María López</h5>
                            <p class="text-muted mb-1">Jefa de Contenido</p>
                            <p class="small text-muted mb-1">Email: <a href="mailto:maria@torosrojos.com">maria@torosrojos.com</a></p>
                            <p class="small text-muted">Tel: <a href="tel:+525512345001">+52 55 1234 5001</a></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card: Responsable de Operaciones -->
            <div class="col">
                <div class="card h-100 text-center">
                    <div class="card-body">
                        <img src="{{ asset('images/Toro Contacto.png') }}" alt="Avatar" class="rounded-circle avatar-img" style="width:96px;height:96px;object-fit:cover;border:4px solid #b30000;">
                        <div class="person-details">
                            <h5 class="card-title mb-1">Carlos Méndez</h5>
                            <p class="text-muted mb-1">Responsable de Operaciones</p>
                            <p class="small text-muted mb-1">Email: <a href="mailto:carlos@torosrojos.com">carlos@torosrojos.com</a></p>
                            <p class="small text-muted">Tel: <a href="tel:+525512345002">+52 55 1234 5002</a></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional fictional roles -->
            <div class="col">
                <div class="card h-100 text-center">
                    <div class="card-body">
                        <img src="{{ asset('images/Toro Cursos.png') }}" alt="Avatar" class="rounded-circle avatar-img" style="width:96px;height:96px;object-fit:cover;border:4px solid #b30000;">
                        <div class="person-details">
                            <h5 class="card-title mb-1">Lucía Ramos</h5>
                            <p class="text-muted mb-1">Coordinadora de Formación</p>
                            <p class="small text-muted mb-1">Email: <a href="mailto:lucia@torosrojos.com">lucia@torosrojos.com</a></p>
                            <p class="small text-muted">Tel: <a href="tel:+525512345003">+52 55 1234 5003</a></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="card h-100 text-center">
                    <div class="card-body">
                        <img src="{{ asset('images/Toro Contacto.png') }}" alt="Avatar" class="rounded-circle avatar-img" style="width:96px;height:96px;object-fit:cover;border:4px solid #b30000;">
                        <div class="person-details">
                            <h5 class="card-title mb-1">Diego Vargas</h5>
                            <p class="text-muted mb-1">Soporte Técnico</p>
                            <p class="small text-muted mb-1">Email: <a href="mailto:diego@torosrojos.com">diego@torosrojos.com</a></p>
                            <p class="small text-muted">Tel: <a href="tel:+525512345004">+52 55 1234 5004</a></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="card h-100 text-center">
                    <div class="card-body">
                        <img src="{{ asset('images/Logo General.png') }}" alt="Avatar" class="rounded-circle avatar-img" style="width:96px;height:96px;object-fit:cover;border:4px solid #b30000;">
                        <div class="person-details">
                            <h5 class="card-title mb-1">Alejandra Cruz</h5>
                            <p class="text-muted mb-1">Relaciones Públicas</p>
                            <p class="small text-muted mb-1">Email: <a href="mailto:alejandra@torosrojos.com">alejandra@torosrojos.com</a></p>
                            <p class="small text-muted">Tel: <a href="tel:+525512345005">+52 55 1234 5005</a></p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function(){
            const input = document.getElementById('searchPeople');
            const clearBtn = document.getElementById('clearSearch');
            const cards = Array.from(document.querySelectorAll('#directoryList .card'));

            function filter(value){
                const q = value.trim().toLowerCase();
                cards.forEach(card => {
                    const text = card.innerText.toLowerCase();
                    const col = card.closest('.col') || card.parentElement;
                    if (col) col.style.display = q && !text.includes(q) ? 'none' : '';
                });
            }

            input.addEventListener('input', (e) => filter(e.target.value));
            clearBtn.addEventListener('click', () => { input.value = ''; filter(''); input.focus(); });
        })();
    </script>
@endpush
