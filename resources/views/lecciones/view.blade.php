@extends('layouts.app')

@section('title', $leccion->titulo . ' | Lección')

@section('content')
    <div class="mb-3">
    <a href="{{ route('cursos.show', ['id' => $leccion->modulo->id_curso]) }}" class="btn btn--simple">&larr; Volver</a>
    </div>

    {{-- Onboarding lateral: panel colapsable al centro-izquierda --}} 
    <div id="onboardingPanel" aria-hidden="false" style="position:fixed;left:0;top:50%;transform:translateY(-50%);z-index:1050;display:flex;align-items:flex-start;">
        <div id="onboardingToggle" role="button" aria-label="Mostrar u ocultar índice" style="background:transparent;border-radius:0 6px 6px 0;padding:10px 8px;cursor:pointer;box-shadow:0 2px 6px rgba(0,0,0,0.08);">
            <div style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;background:var(--bs-primary,#0d6efd);color:#fff;border-radius:6px;font-weight:700;">&gt;</div>
        </div>
        <div id="onboardingBody" style="width:320px;max-height:70vh;background:#fff;border-radius:6px;padding:14px;margin-left:8px;box-shadow:0 8px 24px rgba(15,23,42,0.08);overflow:auto;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                <div style="font-weight:700;color:var(--bs-dark,#212529)">Onboarding</div>
                <small class="text-muted">Curso: {{ $leccion->modulo->curso->titulo }}</small>
            </div>
            <div id="onboardingList">
                @php
                    $currentId = $leccion->id_leccion;
                    $lastModulo = null;
                @endphp
                @foreach($leccionesEnCurso as $l)
                    @php
                        $isActive = ((int)$l->id_leccion === (int)$currentId);
                    @endphp
                    <div class="onb-item" style="padding:8px;border-radius:6px;margin-bottom:6px;display:flex;align-items:center;justify-content:space-between;background:{{ $isActive ? '#f0f8ff' : 'transparent' }};">
                        <div style="flex:1;min-width:0;">
                            <a href="{{ route('lecciones.view', ['id' => $l->id_leccion]) }}" style="color:{{ $isActive ? 'var(--bs-primary,#0d6efd)' : 'var(--bs-dark,#212529)' }};font-weight:{{ $isActive ? '700' : '600' }};text-decoration:none;display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:220px;">{{ $l->titulo }}</a>
                            <div class="small text-muted">Módulo: {{ $l->modulo->titulo }}</div>
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

    <div class="card">
        <div class="card-body d-flex flex-column">
            <h2>{{ $leccion->titulo }}</h2>
            <p class="text-muted">Módulo: {{ $leccion->modulo->titulo }} — Curso: {{ $leccion->modulo->curso->titulo }}</p>

            <hr />

            <div id="leccionContent" class="d-flex flex-column">
                <h5>Explicación</h5>
                <div id="contenido" class="mb-3">{!! $leccion->contenido !!}</div>

                <h5>Ejemplo práctico</h5>
                @if(!empty($leccion->recurso_url))
                    <h5>Recurso recomendado</h5>
                    <div class="mb-3 d-flex align-items-center">
                        @php
                            $ytId = null;
                            if (Str::contains($leccion->recurso_url, 'youtube.com') || Str::contains($leccion->recurso_url, 'youtu.be')) {
                                // try to extract video id
                                preg_match('/(v=|youtu\.be\/)([A-Za-z0-9_-]{6,})(?:&|$)/', $leccion->recurso_url, $m);
                                if (!empty($m[2])) $ytId = $m[2];
                            }
                        @endphp
                        @if($ytId)
                            <img src="https://img.youtube.com/vi/{{ $ytId }}/hqdefault.jpg" alt="Miniatura video" style="width:160px;height:90px;object-fit:cover;border-radius:6px;margin-right:12px;" onerror="this.style.display='none'" />
                        @endif
                        <div>
                            <a href="{{ $leccion->recurso_url }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn--video">Ver recurso recomendado</a>
                            <div class="text-muted small mt-1">Recurso externo: se abrirá en otra pestaña</div>
                        </div>
                    </div>
                @endif
                <div class="mb-3">
                    <div id="exampleArea">Cargando ejemplo...</div>
                    <button id="showSolution" class="btn btn-sm btn--option mt-2">Ver solución</button>
                </div>

                <h5>Actividad</h5>
                <div id="activityArea" class="mb-3">
                    <p>Selecciona los enlaces seguros:</p>
                    <div id="activityOptions"></div>
                </div>

                <h5>Pregunta</h5>
                <div id="quizArea" class="mb-3">
                    <div id="quizQuestion">¿Cuál de las siguientes opciones es la más segura?</div>
                    <div id="quizOptions" class="mt-2"></div>
                    <div id="quizFeedback" class="mt-2"></div>
                </div>

                <div class="d-flex justify-content-between align-items-center">
                    <div id="completionMsg"></div>
                    <div>
                        <button id="markComplete" class="btn btn--status is-downloaded">Marcar como completada</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between mt-3">
        <div>
            @if(isset($prev) && $prev)
                <a href="{{ route('lecciones.view', ['id' => $prev->id_leccion]) }}" class="btn btn--simple">&larr; Anterior: {{ Str::limit($prev->titulo, 40) }}</a>
            @endif
        </div>
        <div>
            @if(isset($next) && $next)
                <a href="{{ route('lecciones.view', ['id' => $next->id_leccion]) }}" class="btn btn--simple">Siguiente: {{ Str::limit($next->titulo, 40) }} &rarr;</a>
            @endif
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        // Expose ids and optional per-lesson payloads so the frontend can render lesson-specific examples/activities/quizzes
        window.LECCION_ID = {{ $leccion->id_leccion }};
        window.CURSO_ID = {{ $leccion->modulo->id_curso }};
        // Optional custom blocks stored on the Leccion model (if populated).
        // If example_html is null, generate a simple contextual example from the title and content so the page is relevant.
        <?php
            $exampleFallback = $leccion->example_html ?? null;
            if (!$exampleFallback) {
                // build a small HTML snippet from title and truncated content
                $snippet = strip_tags($leccion->contenido);
                $snippet = strlen($snippet) > 220 ? substr($snippet,0,217) . '...' : $snippet;
                $exampleFallback = '<div class="border p-2"><strong>' . addslashes($leccion->titulo) . '</strong><p>' . addslashes($snippet) . '</p></div>';
            }
        ?>
        window.LESSON_EXAMPLE_HTML = @json($exampleFallback);
        window.LESSON_ACTIVITY_OPTIONS = @json($leccion->activity_options ?? null);
        window.LESSON_QUIZ_OPTIONS = @json($leccion->quiz_options ?? null);
        // If the module has an evaluation, expose its id so the frontend can record resultados
        @php
            $evaluacion = $leccion->modulo->evaluaciones->first() ?? null;
        @endphp
        window.LESSON_EVALUATION_ID = {{ $evaluacion ? $evaluacion->id_evaluacion : 'null' }};
        // Expose current user id if authenticated (frontend uses this for API calls)
        window.CURRENT_USER = {{ auth()->check() ? auth()->id() : 'null' }};
        // Provide a lesson-specific solution text for the 'Ver solución' modal.
        <?php
            $solution = $leccion->solution_text ?? null;
            if (!$solution) {
                // create a short, helpful tip based on the leccion title/content
                $s = strip_tags($leccion->contenido);
                $s = strlen($s) > 300 ? substr($s,0,297) . '...' : $s;
                $solution = 'Tip: ' . addslashes($s) . ' — Nunca ingreses credenciales desde enlaces sospechosos.';
            }
        ?>
        window.LESSON_SOLUTION_HTML = @json($solution);
    </script>
    <script src="{{ asset('js/microcursos.js') }}?v=5"></script>
    <script>
        (function(){
            const markBtn = document.getElementById('markComplete');
            const completionMsg = document.getElementById('completionMsg');
            if(markBtn){
                markBtn.addEventListener('click', async function(){
                    markBtn.disabled = true;
                    markBtn.innerText = 'Guardando...';
                    try{
                        // Save progress locally
                        if(window.OfflinePWA && typeof window.OfflinePWA.saveProgress === 'function'){
                            await window.OfflinePWA.saveProgress(window.CURSO_ID, window.LECCION_ID, { completado: true });
                        }
                        // If online, attempt sync immediately
                        if(navigator.onLine && window.OfflinePWA && typeof window.OfflinePWA.syncProgress === 'function'){
                            await window.OfflinePWA.syncProgress();
                        }
                        completionMsg.innerHTML = '<span class="text-success">Lección marcada como completada (guardado localmente).</span>';
                        markBtn.innerText = 'Completado';
                        // intentamos actualizar el panel de progreso si existe
                        try{
                            if (window.CURSO_ID) {
                                const uid = window.CURRENT_USER || '';
                                fetch(`/api/cursos/${window.CURSO_ID}/progress?id_usuario=${uid}`, { credentials: 'same-origin' })
                                    .then(r=>r.json())
                                    .then(data=>{
                                        const el = document.getElementById('cursoProgress');
                                        if (el && data && typeof data.percent !== 'undefined'){
                                            el.innerHTML = `<div class="mb-2"><div class="progress" style="height:8px;"><div class="progress-bar" role="progressbar" style="width: ${data.percent}%" aria-valuenow="${data.percent}" aria-valuemin="0" aria-valuemax="100"></div></div><small class="text-muted">${data.percent}% completado</small></div>`;
                                            if (data.percent >= 100) { const badge = document.createElement('div'); badge.className='badge bg-success'; badge.textContent='Completado'; el.appendChild(badge); }
                                            else if (data.percent >= 50) { const badge = document.createElement('div'); badge.className='badge bg-info'; badge.textContent='En progreso'; el.appendChild(badge); }
                                        }
                                        const listWrap = document.getElementById('moduleProgressList');
                                        if (listWrap && data && data.moduleProgress){
                                            listWrap.innerHTML = '';
                                            Object.values(data.moduleProgress).forEach(mp => {
                                                const block = document.createElement('div');
                                                block.className = 'mb-2 p-2';
                                                block.style.borderRadius = '8px';
                                                block.style.background = '#f8f9fa';
                                                block.innerHTML = `
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div style="font-weight:600">${mp.titulo}</div>
                                                        <div style="text-align:right"><small class="text-muted">${mp.percent}%</small></div>
                                                    </div>
                                                    <div class="mt-1"><div class="progress" style="height:8px;"><div class="progress-bar" role="progressbar" style="width:${mp.percent}%"></div></div></div>
                                                    <div class="mt-1 small text-muted">${mp.completed}/${mp.total} lecciones</div>
                                                `;
                                                listWrap.appendChild(block);
                                            });
                                        }
                                    }).catch(()=>{});
                            }
                        }catch(e){}
                    }catch(e){
                        console.warn('save progress failed', e);
                        completionMsg.innerHTML = '<span class="text-danger">No fue posible guardar el progreso localmente.</span>';
                        markBtn.innerText = 'Marcar como completada';
                        markBtn.disabled = false;
                    }
                });
            }
        })();
    </script>
    <script>
        try{
            if (navigator.serviceWorker && navigator.serviceWorker.controller) {
                navigator.serviceWorker.controller.postMessage({ type: 'SKIP_WAITING' });
            }
        }catch(e){}
    </script>
    <script>
        // Toggle para el panel de onboarding: muestra/oculta y guarda preferencia
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
            // optional: close when clicking outside (for small screens)
            document.addEventListener('click', function(e){
                if(window.innerWidth <= 768) return; // only for larger screens
                if(!panel.contains(e.target)){
                    // keep it open
                }
            });

        })();
    </script>
@endpush
