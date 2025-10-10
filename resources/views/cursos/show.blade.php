@extends('layouts.app')

@section('title', $curso->titulo . ' | Curso')

@section('content')
    <div class="course-page">
        {{-- Onboarding lateral: navegar entre cursos (similar al panel de lecciones) --}}
        <div id="onboardingPanel" aria-hidden="false" style="position:fixed;left:0;top:50%;transform:translateY(-50%);z-index:1050;display:flex;align-items:flex-start;">
            <div id="onboardingToggle" role="button" aria-label="Mostrar u ocultar índice" style="background:transparent;border-radius:0 6px 6px 0;padding:10px 8px;cursor:pointer;box-shadow:0 2px 6px rgba(0,0,0,0.08);">
                <div style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;background:var(--bs-primary,#0d6efd);color:#fff;border-radius:6px;font-weight:700;">&gt;</div>
            </div>
            <div id="onboardingBody" style="width:320px;max-height:70vh;background:#fff;border-radius:6px;padding:14px;margin-left:8px;box-shadow:0 8px 24px rgba(15,23,42,0.08);overflow:auto;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                    <div style="font-weight:700;color:var(--bs-dark,#212529)">Cursos</div>
                    <small class="text-muted">Explorar</small>
                </div>
                <div id="onboardingList">
                    @foreach($cursosList as $c)
                        @php $isActive = ((int)$c->id_curso === (int)$curso->id_curso); @endphp
                        <div class="onb-item" style="padding:8px;border-radius:6px;margin-bottom:6px;display:flex;align-items:center;justify-content:space-between;background:{{ $isActive ? '#f0f8ff' : 'transparent' }};">
                            <div style="flex:1;min-width:0;">
                                <a href="{{ route('cursos.show', ['id' => $c->id_curso]) }}" style="color:{{ $isActive ? 'var(--bs-primary,#0d6efd)' : 'var(--bs-dark,#212529)' }};font-weight:{{ $isActive ? '700' : '600' }};text-decoration:none;display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:200px;">{{ $c->titulo }}</a>
                                <div class="small text-muted">{{ Str::limit(strip_tags($c->descripcion), 60) }}</div>
                            </div>
                            <div style="margin-left:8px;">
                                @if($isActive)
                                    <span class="badge bg-success">Ahora</span>
                                @else
                                    <span class="badge bg-secondary">&nbsp;</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
                <div id="cursoProgress" class="mt-2"></div>
            </div>
        </div>
        <style>
            /* Scoped overrides sólo para la vista de curso: prioridad alta pero localizada */
            .course-page .course-header .lead { color: var(--color-white) !important; }
            .course-page .module-card .card-body p { color: #222222 !important; }
            .course-page .module-card .list-group .small { color: #333333 !important; }
            /* Asegurar que los badges/pequeños snippets no queden demasiado claros */
            .course-page .module-card .card-body .small { color: #333333 !important; }

            /* Evitar desbordamientos en breakpoints intermedios (md).
               - permitir que los flex-items se encojan (min-width:0)
               - forzar quiebre de palabras largas y wrapping seguro
               - evitar que botones/URLs anchos rompieran el layout */
            .course-page .list-group-item { min-width: 0; flex-wrap: wrap; }
            .course-page .list-group-item > div { min-width: 0; }
            .course-page .module-card .card-body { overflow: hidden; }
            .course-page, .course-page * { overflow-wrap: anywhere; word-break: break-word; }

            /* Específico para las opciones de actividad: truncar y evitar que enlaces enormes
               expandan el contenedor en md (y en móviles). */
            #activityOptions .activity-link-btn,
            #activityOptions .btn { max-width: 100%; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

            /* Cuando el ancho es muy pequeño permitir multilinea en la descripción */
            @media (max-width: 575.98px) {
                #activityOptions .activity-link-btn { white-space: normal; }
            }
        </style>
    <div class="d-flex justify-content-between align-items-center mb-4 course-header">
        <div>
            <h1 class="mb-0">{{ $curso->titulo }}</h1>
            {{-- descripción: más visible: tamaño y contraste --}}
            <p class="lead text-light-emphasis" style="max-width:800px; line-height:1.4; font-size:1.05rem; color:var(--color-white);">{{ $curso->descripcion }}</p>
        </div>
        <div>
            <a href="{{ url('/cursos') }}" class="btn btn--simple">Volver</a>
            @if(auth()->check())
                <button id="downloadCourseBtn" class="btn btn--primary ms-2">Descargar curso</button>
            @endif
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            @foreach($curso->modulos as $modulo)
                <div class="card mb-3 module-card" data-modulo-id="{{ $modulo->id_modulo }}">
                    <div class="d-flex justify-content-between align-items-center"><h5 class="text-center justify-content-center w-100 m-0 py-2" style="border-top-left-radius: .25rem; border-top-right-radius: .25rem;">
                    @php $mp = $moduleProgress[$modulo->id_modulo] ?? ['percent'=>0,'completed'=>0,'total'=>0]; @endphp
                    <small class="text-muted">{{ $mp['percent'] }}% completado</small>
                    </h5></div>
                        
                    <div class="card-body align-items-stretch d-flex flex-column">
                        <h5 class="d-flex justify-content-between align-items-center">
                            <span>{{ $modulo->titulo }}</span>
                        </h5>
                        <p class="mb-2" style="color:#dfeefb; font-size:0.98rem;">{{ $modulo->descripcion }}</p>
                        <div class="mb-2">
                            <div class="progress" style="height:8px;">
                                <div class="progress-bar" role="progressbar" style="width: {{ $mp['percent'] }}%" aria-valuenow="{{ $mp['percent'] }}" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                        <ul class="list-group list-group-flush">
                            @foreach($modulo->lecciones as $leccion)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>{{ $leccion->titulo }}</strong>
                                        <div class="small" style="color:#cfddeb">{{ Str::limit(strip_tags($leccion->contenido), 120) }}</div>
                                    </div>
                                    <div>
                                        <a href="{{ route('lecciones.view', $leccion->id_leccion) }}" class="btn btn-sm btn--primary">Abrir</a>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-12 text-center">
                            <h6 class="text-center">Progreso del curso</h6>
                            <div id="cursoProgress">Cargando...</div>
                        </div>
                        <div class="col-12">
                            <div id="moduleProgressList" class=""></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>window.CURSO_ID = {{ $curso->id_curso }};</script>
    <script src="{{ asset('js/microcursos.js') }}?v=5"></script>
    <script>
        // Si hay un service worker activo, pedirle que aplique la nueva versión (skipWaiting)
        try{
            if (navigator.serviceWorker && navigator.serviceWorker.controller) {
                navigator.serviceWorker.controller.postMessage({ type: 'SKIP_WAITING' });
            }
        }catch(e){}
    </script>
    <script>
        (function(){
            const btn = document.getElementById('downloadCourseBtn');
            if(!btn) return;

            // Helper to mark button as downloaded
            function markDownloaded(){
                try{ btn.classList.remove('btn--primary'); btn.classList.add('btn--status','is-downloaded'); btn.innerText = 'Descargado'; btn.disabled = true; try{ btn.classList.add('download-animated'); setTimeout(()=>btn.classList.remove('download-animated'),900); }catch(e){} }catch(e){}
            }

            function markNotDownloaded(){
                try{ btn.classList.remove('btn--status','is-downloaded'); btn.classList.add('btn--primary'); btn.innerText = 'Descargar curso'; btn.disabled = false; }catch(e){}
            }

            async function checkDownloadedOnLoad(){
                if(!window.OfflinePWA || typeof window.OfflinePWA.getDownloadedCourses !== 'function') return;
                try{
                    const list = await window.OfflinePWA.getDownloadedCourses();
                    if(Array.isArray(list) && list.find(i=> String(i.id) === String(window.CURSO_ID))){
                        markDownloaded();
                    } else {
                        markNotDownloaded();
                    }
                }catch(e){ /* ignore */ }
            }

            btn.addEventListener('click', async function(){
                btn.disabled = true;
                btn.innerText = 'Descargando...';
                try{
                    const payload = {
                        id: window.CURSO_ID,
                        titulo: document.querySelector('.course-header h1') ? document.querySelector('.course-header h1').innerText.trim() : 'Curso',
                        descripcion: document.querySelector('.course-header .lead') ? document.querySelector('.course-header .lead').innerText.trim() : '',
                        resources: [
                            location.origin + '/api/cursos/' + window.CURSO_ID,
                            location.origin + '/api/cursos/' + window.CURSO_ID + '/modulos'
                        ]
                    };

                    // Save metadata to IDB/localStorage
                    if(window.OfflinePWA && typeof window.OfflinePWA.saveDownloadedCourse === 'function'){
                        await window.OfflinePWA.saveDownloadedCourse(payload);
                    }

                    // Ask service worker to cache provided resources (if registered)
                    if(navigator.serviceWorker && navigator.serviceWorker.controller){
                        navigator.serviceWorker.controller.postMessage({ action: 'cache-resources', resources: payload.resources });
                    }

                    // Mark UI immediately as downloaded
                    markDownloaded();

                    // Also send a small event so other pages (course list) can update without full reload
                    try{ window.dispatchEvent(new CustomEvent('offlinepwa:course-downloaded', { detail: { id: window.CURSO_ID } })); }catch(e){}

                }catch(e){
                    console.warn('download course failed', e);
                    btn.innerText = 'Error';
                    setTimeout(()=>{ markNotDownloaded(); }, 1500);
                }
            });

            // Check state on load
            document.addEventListener('DOMContentLoaded', function(){ checkDownloadedOnLoad(); });

            // If OfflinePWA is initialized after DOMContentLoaded, react to ready event
            window.addEventListener('offlinepwa:ready', function(){ try{ checkDownloadedOnLoad(); }catch(e){} });

            // Also listen to global event (dispatched after download in other tabs/pages)
            window.addEventListener('offlinepwa:course-downloaded', function(e){
                try{ if(e && e.detail && String(e.detail.id) === String(window.CURSO_ID)) markDownloaded(); }catch(err){}
            });

            // Update UI if the course was deleted from downloads elsewhere
            window.addEventListener('offlinepwa:course-deleted', function(e){
                try{
                    const id = e && e.detail && String(e.detail.id);
                    if(String(id) === String(window.CURSO_ID)){
                        markNotDownloaded();
                    }
                }catch(err){}
            });
        })();
    </script>
    <script>
        // Toggle para el panel de onboarding: muestra/oculta y guarda preferencia (misma lógica que en la vista de lecciones)
        (function(){
            const panel = document.getElementById('onboardingPanel');
            if(!panel) return;
            const body = document.getElementById('onboardingBody');
            const toggle = document.getElementById('onboardingToggle');
            const arrow = toggle && toggle.querySelector('div');
            const STORAGE_KEY = 'mc_onboarding_visible';

            function setVisible(visible, save=true){
                if(visible){
                    body.style.display = 'block';
                    panel.style.width = '';
                    if(arrow) arrow.innerHTML = '&gt;';
                    panel.setAttribute('aria-hidden','false');
                } else {
                    body.style.display = 'none';
                    if(arrow) arrow.innerHTML = '&lt;';
                    panel.setAttribute('aria-hidden','true');
                }
                if(save) localStorage.setItem(STORAGE_KEY, visible ? '1' : '0');
            }

            // Init from localStorage (default: visible)
            const pref = localStorage.getItem(STORAGE_KEY);
            const visible = pref === null ? true : pref === '1';
            setVisible(visible, false);

            toggle.addEventListener('click', function(e){
                const nowVisible = panel.getAttribute('aria-hidden') === 'false';
                setVisible(!nowVisible);
            });
        })();
    </script>
    <!-- Service worker registration removed to avoid ghost PWA behavior.
         If you need a PWAs in the future, add an explicit opt-in and versioning.
    -->
@endpush
