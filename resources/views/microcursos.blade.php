@extends('layouts.app')

@section('title','Mis Cursos | Los Toros Rojos')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
        <div class="mb-2">
            <h1 class="mb-0">Mis Cursos</h1>
            <small class="text-muted">Aquí verás los cursos en los que estás inscrito</small>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <input class="form-control" id="searchCourses" placeholder="Buscar cursos..." style="min-width:300px;" />
            <a href="{{ url('/cursos/crear') }}" class="btn btn-primary">Crear curso</a>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-3">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Filtros</h5>
                </div>
                <div class="card-body pt-3">
                    <div class="mb-2">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" value="" id="filterCompleted">
                            <label class="form-check-label d-block" for="filterCompleted">Completados</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" value="" id="filterInProgress">
                            <label class="form-check-label d-block" for="filterInProgress">En progreso</label>
                        </div>

                        {{-- Offline-only forced downloaded filter. Hidden on online. When offline this
                             checkbox is shown checked and disabled to indicate the enforced filter. --}}
                        <div class="form-check mb-2" id="offline-only-downloads-wrap" style="display:none;">
                            <input class="form-check-input" type="checkbox" value="1" id="filterOfflineOnlyDownloads" checked disabled>
                            <label class="form-check-label d-block" for="filterOfflineOnlyDownloads">Mostrando sólo descargas (modo sin conexión)</label>
                        </div>

                        <div class="mt-3">
                            <label class="form-label">Categoría</label>
                            @php $categorias = \App\Models\Categoria::orderBy('nombre')->get(); @endphp
                            <select id="filterCategory" class="form-select">
                                <option value="">Todas</option>
                                @foreach($categorias as $cat)
                                    <option value="{{ $cat->id_categoria }}">{{ $cat->nombre }}</option>
                                @endforeach
                            </select>
                            <div class="mt-2">
                                <button id="clearFiltersBtn" class="btn btn-sm btn-outline-secondary">Limpiar filtros</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-9">
                <div class="row g-3" id="coursesList">
                @php $cursoIds = []; @endphp
                @forelse($cursos as $curso)
                    @php $cursoIds[] = $curso->id_curso; @endphp
                    @php
                        // Selección de imagen por categoría (fallback a Toro Cursos.png)
                        $catName = optional($curso->categoria)->nombre ?? '';
                        $imgName = 'Toro Cursos.png';
                        if (stripos($catName, 'dise') !== false) {
                            $imgName = 'Toro Diseño.png';
                        } elseif (stripos($catName, 'negoc') !== false || stripos($catName, 'negocio') !== false) {
                            $imgName = 'Toro Negocios.png';
                        } elseif (stripos($catName, 'program') !== false || stripos($catName, 'desarrol') !== false) {
                            $imgName = 'Toro Programador.png';
                        }
                        $imgPath = asset('images/' . $imgName);
                    @endphp
                    <div class="col-md-4 course-card" data-curso-id="{{ $curso->id_curso }}" data-category-id="{{ $curso->id_categoria }}" data-category-name="{{ optional($curso->categoria)->nombre }}">
                        <div class="card h-100">
                            <img src="{{ $imgPath }}" class="card-img-top" alt="{{ $curso->titulo }}">
                            <div class="card-body d-flex flex-column">
                                <!-- Reordenado: Título arriba, luego meta (módulo/curso), luego contenido/descrpción -->
                                <h5 class="card-title">{{ $curso->titulo }}</h5>

                                {{-- Mostrar meta (ej. Módulo / Curso) si está disponible. No eliminar datos. --}}
                                @php
                                    // Intentamos obtener datos de módulo o categoría si existen
                                    $moduloLine = null;
                                    // Si el curso tiene relación con modulos, mostramos el primer módulo como ejemplo
                                    if (isset($curso->modulos) && $curso->modulos->count()) {
                                        $primerModulo = $curso->modulos->first();
                                        $moduloLine = 'Módulo: ' . ($primerModulo->titulo ?? '—');
                                    }
                                    // Agregamos siempre referencia al curso (texto pequeño)
                                    $cursoLine = 'Curso: ' . ($curso->titulo ?? '—');
                                @endphp
                                @if($moduloLine)
                                    <p class="mb-1 text-muted small">{{ $moduloLine }} — {{ $cursoLine }}</p>
                                @else
                                    <p class="mb-1 text-muted small">{{ $cursoLine }}</p>
                                @endif

                                <div class="mb-2">
                                    <div class="mb-1" style="font-weight:700; color:#333;">Contenido del curso</div>
                                    <p class="card-text text-muted">{{ $curso->descripcion }}</p>
                                </div>

                                    <div class="mt-auto">
                                    <div class="mb-2 course-progress-placeholder">
                                        <div class="progress" style="height:8px;">
                                            <div class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <small class="text-muted">--% completado</small>
                                    </div>
                                        <div class="d-flex action-btn-row">
                                            <div class="action-btn-group">
                                                <a href="{{ url('/cursos/'.$curso->id_curso.'/ver') }}" class="btn btn-outline-primary btn-sm">Ver</a>
                                                <a href="{{ url('/cursos/'.$curso->id_curso.'/continuar') }}" class="btn btn-primary btn-sm">Continuar</a>
                                                @if(auth()->check())
                                                    <button class="btn btn-sm btn-secondary download-course-btn" data-curso-id="{{ $curso->id_curso }}">Descargar</button>
                                                @endif
                                            </div>
                                        </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12">No hay cursos disponibles.</div>
                @endforelse
                <script>window.PAGE_COURSE_IDS = @json($cursoIds ?? []);</script>
            </div>
        </div>
        </div>
    </div>

    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/microcursos.js') }}?v=5"></script>
    <script>
        (function(){
            function params(){ return new URLSearchParams(window.location.search); }

            // Return an array of downloaded course ids (strings)
            async function getDownloadedIds(){
                if (!window.OfflinePWA || typeof window.OfflinePWA.getDownloadedCourses !== 'function') return [];
                try{ const downloads = await window.OfflinePWA.getDownloadedCourses(); return (downloads || []).map(d=> String(d.id)); }catch(e){ return []; }
            }

            // Enhance existing applyFilters logic by also considering the 'Descargados' checkbox
            async function applyFiltersCombined(){
                const q = document.getElementById('searchCourses') ? document.getElementById('searchCourses').value.trim().toLowerCase() : '';
                const showCompleted = document.getElementById('filterCompleted') ? document.getElementById('filterCompleted').checked : false;
                const showInProgress = document.getElementById('filterInProgress') ? document.getElementById('filterInProgress').checked : false;
                const showDownloaded = false; // removed UI filter; downloads are managed in Admin view
                const selectedCategory = document.getElementById('filterCategory') ? (document.getElementById('filterCategory').value || '') : '';

                // If downloaded filter is active, fetch downloaded ids once
                let downloadedIds = null;
                if (showDownloaded) downloadedIds = await getDownloadedIds();

                const cards = Array.from(document.querySelectorAll('.course-card'));
                cards.forEach(card=>{
                    const title = (card.querySelector('.card-title') ? card.querySelector('.card-title').textContent : '').toLowerCase();
                    const desc = (card.querySelector('.card-text') ? card.querySelector('.card-text').textContent : '').toLowerCase();
                    const percent = parseInt(card.dataset.percent || '0', 10);
                    const cardCat = card.getAttribute('data-category-id') || '';
                    const cardCatName = (card.getAttribute('data-category-name') || '').toLowerCase();
                    const cid = String(card.getAttribute('data-curso-id'));

                    let matchesSearch = true;
                    if (q) matchesSearch = title.includes(q) || desc.includes(q);

                    let matchesStatus = true;
                    if (showCompleted && !showInProgress){ matchesStatus = percent >= 100; }
                    else if (!showCompleted && showInProgress){ matchesStatus = percent > 0 && percent < 100; }
                    else if (showCompleted && showInProgress){ matchesStatus = (percent >= 100) || (percent > 0 && percent < 100); }

                    let matchesCategory = true;
                    if (selectedCategory) matchesCategory = (String(cardCat) === String(selectedCategory));

                    let matchesDownloaded = true;

                    if (matchesSearch && matchesStatus && matchesCategory && matchesDownloaded) card.style.display = '';
                    else card.style.display = 'none';
                });
            }

            // Hook filters to re-run applyFiltersCombined
            document.addEventListener('change', function(e){
                if (e.target && (e.target.id === 'filterCompleted' || e.target.id === 'filterInProgress' || e.target.id === 'filterCategory')){
                    try{ applyFiltersCombined(); }catch(e){}
                }
            });

            // Hook search input
            const searchInput = document.getElementById('searchCourses');
            if (searchInput) searchInput.addEventListener('input', function(){ try{ applyFiltersCombined(); }catch(e){} });

            // If query param offline_downloads=1, redirect to downloads management view
            async function applyOfflineFilterIfRequested(){
                const p = params();
                if (p.get('offline_downloads') !== '1') return;
                // Instead of redirecting automatically, show the offline-only indicator and
                // a small prompt allowing the user to go to the downloads management page.
                try{
                    const wrapper = document.getElementById('offline-only-downloads-wrap');
                    if(wrapper) wrapper.style.display = '';
                    const alertContainer = document.getElementById('network-alert');
                    if(alertContainer){
                        alertContainer.innerHTML = '<div class="alert alert-warning" style="max-width:980px;margin:0 auto;">Filtrando a sólo descargas: <a href="/descargas/administrar" class="fw-bold">Ir a Mis Descargas</a></div>';
                        alertContainer.style.display = 'block';
                    }
                }catch(e){ console.warn('applyOfflineFilterIfRequested fallback', e); }
            }

            // Enforce downloaded-only filter when offline. This will hide any course cards
            // that are not present in the local downloads store. It also disables other
            // filters to avoid contradictory UI while offline.
            async function applyOfflineDownloadedFilter(){
                try{
                    const isOffline = !navigator.onLine;
                    const wrapper = document.getElementById('offline-only-downloads-wrap');
                    const otherControls = [document.getElementById('filterCompleted'), document.getElementById('filterInProgress'), document.getElementById('filterCategory'), document.getElementById('searchCourses')];

                    if(!isOffline){
                        // restore UI: hide offline-only indicator and re-run normal filters
                        if(wrapper) wrapper.style.display = 'none';
                        otherControls.forEach(c=>{ if(!c) return; c.disabled = false; if(c.type === 'checkbox') c.parentElement.style.opacity = 1; });
                        try{ applyFiltersCombined(); }catch(e){}
                        return;
                    }

                    // Show the offline-only indicator (checked & disabled already)
                    if(wrapper) wrapper.style.display = '';

                    // Disable other filter controls to prevent user from changing them while offline
                    otherControls.forEach(c=>{ if(!c) return; c.disabled = true; if(c.type === 'checkbox') c.parentElement.style.opacity = 0.6; });

                    // Get downloaded ids and filter DOM
                    const downloaded = await getDownloadedIds();
                    const set = new Set((downloaded || []).map(String));
                    const cards = Array.from(document.querySelectorAll('.course-card'));
                    cards.forEach(card=>{
                        const cid = String(card.getAttribute('data-curso-id'));
                        if(set.has(cid)) card.style.display = '';
                        else card.style.display = 'none';
                    });

                }catch(err){ console.warn('applyOfflineDownloadedFilter failed', err); }
            }

            // Annotate cards/buttons with downloaded status and add badges
            async function annotateDownloadedState(){
                const ids = await getDownloadedIds();
                if(!ids || !ids.length) return;
                document.querySelectorAll('.course-card').forEach(card=>{
                    const cid = String(card.getAttribute('data-curso-id'));
                    const btn = card.querySelector('.download-course-btn');
                    // add badge on card
                    if(ids.includes(cid)){
                        // add badge if not present
                        if(!card.querySelector('.download-badge')){
                            const badge = document.createElement('div');
                            badge.className = 'download-badge badge bg-success';
                            badge.style.position = 'absolute';
                            badge.style.right = '12px';
                            badge.style.top = '12px';
                            badge.style.zIndex = 20;
                            badge.innerText = 'Descargado';
                            // ensure card position is relative
                            const cardEl = card.querySelector('.card');
                            if(cardEl) cardEl.style.position = 'relative';
                            if(cardEl) cardEl.appendChild(badge);
                        }
                        // update download button if present
                        if(btn){ btn.classList.remove('btn-secondary'); btn.classList.add('btn-success'); btn.innerText = 'Descargado'; btn.disabled = true; try{ btn.classList.add('download-animated'); setTimeout(()=>btn.classList.remove('download-animated'),900); }catch(e){} }
                    }
                });
            }

            // Hook download buttons (preserve original behavior)
            document.addEventListener('click', async function(e){
                const btn = e.target.closest && e.target.closest('.download-course-btn');
                if(!btn) return;
                const cursoId = btn.getAttribute('data-curso-id');
                if(!cursoId) return;
                btn.disabled = true; btn.innerText = 'Descargando...';
                try{
                    const payload = { id: cursoId, titulo: (btn.closest('.card')? (btn.closest('.card').querySelector('.card-title')||{}).innerText : '') , resources: [location.origin + '/api/cursos/' + cursoId] };
                    if(window.OfflinePWA && typeof window.OfflinePWA.saveDownloadedCourse === 'function'){
                        await window.OfflinePWA.saveDownloadedCourse(payload);
                    }
                    if(navigator.serviceWorker && navigator.serviceWorker.controller){
                        navigator.serviceWorker.controller.postMessage({ action: 'cache-resources', resources: payload.resources });
                    }
                    btn.innerText = 'Descargado';
                    btn.classList.remove('btn-secondary'); btn.classList.add('btn-success');
                    btn.disabled = true;
                    setTimeout(()=>{ try{ annotateDownloadedState(); }catch(e){} }, 600);
                }catch(err){ console.warn('download failed', err); btn.innerText='Error'; setTimeout(()=>{ btn.innerText = 'Descargar'; btn.disabled = false; },1500); }
            }, { passive:false });

            document.addEventListener('DOMContentLoaded', function(){
                setTimeout(function(){ applyOfflineFilterIfRequested(); annotateDownloadedState(); applyOfflineDownloadedFilter(); }, 200);
            });

            // Listen for cross-page download events and refresh annotations
            window.addEventListener('offlinepwa:course-downloaded', function(e){
                try{ annotateDownloadedState(); }catch(err){}
            });

            // When a course is deleted from downloads, remove badge and reset button state
            window.addEventListener('offlinepwa:course-deleted', function(e){
                try{
                    const id = e && e.detail && String(e.detail.id);
                    if(!id) return;
                    document.querySelectorAll('.course-card').forEach(card=>{
                        const cid = String(card.getAttribute('data-curso-id'));
                        if(cid !== id) return;
                        // remove badge
                        const b = card.querySelector('.download-badge'); if(b) b.remove();
                        // reset button if present
                        const btn = card.querySelector('.download-course-btn');
                        if(btn){ try{ btn.classList.remove('btn-success'); btn.classList.add('btn-secondary'); btn.innerText = 'Descargar'; btn.disabled = false; }catch(e){} }
                    });
                    // Re-apply offline filter as the set of downloaded ids changed
                    try{ applyOfflineDownloadedFilter(); }catch(e){}
                }catch(err){}
            });

            // If OfflinePWA becomes ready after load, re-annotate state
            window.addEventListener('offlinepwa:ready', function(){ try{ annotateDownloadedState(); }catch(e){} });
            // Re-apply offline-enforced filter whenever OfflinePWA is ready or when downloads change
            window.addEventListener('offlinepwa:ready', function(){ try{ applyOfflineDownloadedFilter(); }catch(e){} });

            // Re-run filter when connectivity changes
            window.addEventListener('online', function(){ try{ applyOfflineDownloadedFilter(); }catch(e){} });
            window.addEventListener('offline', function(){ try{ applyOfflineDownloadedFilter(); }catch(e){} });
        })();
    </script>
@endpush

@push('styles')
<style>
    .download-badge{ font-weight:700; padding:6px 10px; border-radius:8px; box-shadow: 0 6px 12px rgba(11,22,40,0.08); }
</style>
@endpush
