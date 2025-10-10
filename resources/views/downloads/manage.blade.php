@extends('layouts.app')

@section('title','Administrar descargas | Los Toros Rojos')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="mb-0">Administrar descargas</h1>
        <small class="text-muted">Aquí puedes ver los cursos descargados localmente y administrarlos.</small>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <!-- Local alert area for feedback -->
        <div id="downloadsAlert" style="margin-bottom:12px; display:none;">
        </div>
    </div>
    <div id="downloadsList">
        <p class="text-muted">Cargando descargas...</p>
    </div>

    <!-- Confirmation modal (reusable) -->
    <div class="modal fade" id="confirmActionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmActionModalTitle">Confirmar acción</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body" id="confirmActionModalBody">¿Estás seguro?</div>
                <div class="modal-footer">
                    <button type="button" class="btn btn--simple" data-bs-dismiss="modal" id="confirmActionModalCancel">Cancelar</button>
                    <button type="button" class="btn btn--option" id="confirmActionModalConfirm">Confirmar</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Result modal to show operation outcome -->
    <div class="modal fade" id="confirmResultModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmResultModalTitle">Resultado</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body" id="confirmResultModalBody">Operación completada.</div>
                <div class="modal-footer">
                    <button type="button" class="btn btn--primary" id="confirmResultModalOk">Aceptar</button>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
(function(){
    async function renderDownloads(){
        const wrap = document.getElementById('downloadsList');
        if(!wrap) return;
        if(!window.OfflinePWA || typeof window.OfflinePWA.getDownloadedCourses !== 'function'){
            // Offline runtime not yet available. Wait for it (one-shot) and retry rendering.
            wrap.innerHTML = '<div class="text-muted">Esperando subsistema offline...</div>';
            try{
                const once = () => { try{ renderDownloads(); }catch(e){} finally { window.removeEventListener('offlinepwa:ready', once); } };
                window.addEventListener('offlinepwa:ready', once);
            }catch(e){}
            return;
        }
        const list = await window.OfflinePWA.getDownloadedCourses();
        if(!list || !list.length){ wrap.innerHTML = '<div class="text-muted">No tienes descargas guardadas.</div>'; return; }
        wrap.innerHTML = '';
        list.forEach(c=>{
            const el = document.createElement('div');
            el.className = 'd-flex align-items-center justify-content-between mb-2 p-2 border rounded';
            el.innerHTML = `
                <div>
                    <div style="font-weight:700">${(c.titulo||('Curso '+c.id))}</div>
                    <div class="small text-muted">ID: ${c.id}</div>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn--option btn-sm delete-download-btn" data-id="${c.id}">Eliminar descarga</button>
                    <button class="btn btn--video btn-sm view-download-btn" data-id="${c.id}">Ver</button>
                </div>
            `;
            wrap.appendChild(el);
        });

        // Attach handlers
        document.querySelectorAll('.delete-download-btn').forEach(btn=>{
            btn.addEventListener('click', async function(){
                const id = this.getAttribute('data-id');
                console.log('[downloads] delete clicked for id=', id);
                try{
                    const okConfirm = await (window.__downloadsConfirm ? window.__downloadsConfirm('Eliminar descarga', `¿Eliminar la descarga local ID ${id}?`) : confirm(`Eliminar la descarga local ID ${id}?`));
                    console.log('[downloads] user confirmed?', okConfirm);
                    if(!okConfirm) return;
                    btn.disabled = true; const prevText = btn.innerText; btn.innerText = 'Eliminando...';

                    if(!window.OfflinePWA){
                        console.warn('[downloads] window.OfflinePWA not available');
                        (window.__downloadsShowResult || function(o){ alert(o.body||o.title); })({ title: 'Error', body: 'El subsistema offline no está disponible en esta página.' });
                        btn.disabled = false; btn.innerText = prevText;
                        return;
                    }
                    console.log('[downloads] OfflinePWA type', typeof window.OfflinePWA, 'deleteDownloadedCourse:', typeof window.OfflinePWA.deleteDownloadedCourse);
                    if(typeof window.OfflinePWA.deleteDownloadedCourse !== 'function'){
                        console.warn('[downloads] OfflinePWA.deleteDownloadedCourse is not a function');
                        (window.__downloadsShowResult || function(o){ alert(o.body||o.title); })({ title: 'Error', body: 'La función de eliminación no está disponible.' });
                        btn.disabled = false; btn.innerText = prevText;
                        return;
                    }

                    // call the offline delete and log the result
                    let ok = false;
                    try{
                        console.log('[downloads] calling OfflinePWA.deleteDownloadedCourse', id);
                        ok = await window.OfflinePWA.deleteDownloadedCourse(id);
                        console.log('[downloads] delete result for', id, ok);
                    }catch(callErr){
                        console.error('[downloads] delete call threw', callErr);
                        (window.__downloadsShowResult || function(o){ alert(o.body||o.title); })({ title: 'Error', body: 'Ocurrió un error al intentar eliminar la descarga.' });
                        btn.disabled = false; btn.innerText = prevText;
                        return;
                    }

                    if(ok) {
                        // Show result modal for success
                        try{ await (window.__downloadsShowResult ? window.__downloadsShowResult({ title: 'Eliminado', body: `La descarga ${id} fue eliminada correctamente.`, type: 'success' }) : Promise.resolve(true)); }catch(e){}
                        // trigger event and re-render after modal close via resultOk handler
                        try{ window.dispatchEvent(new CustomEvent('offlinepwa:course-deleted', { detail: { id } })); }catch(e){}
                    } else {
                        // Show result modal for failure
                        try{ await (window.__downloadsShowResult ? window.__downloadsShowResult({ title: 'Error', body: `No se pudo eliminar la descarga ${id}.`, type: 'error' }) : Promise.resolve(false)); }catch(e){}
                        btn.disabled = false; btn.innerText = prevText;
                    }
                }catch(e){
                    console.error('[downloads] delete error', e);
                    (window.__downloadsAlert || function(m,t){ alert(m); })(`Ocurrió un error al eliminar la descarga: ${e && e.message ? e.message : 'error desconocido'}`, 'danger');
                    try{ btn.disabled = false; btn.innerText = 'Eliminar descarga'; }catch(_){ }
                }
            });
        });

        document.querySelectorAll('.view-download-btn').forEach(btn=>{
            btn.addEventListener('click', function(){
                const id = this.getAttribute('data-id');
                // If online, prefer redirect to course view
                if(navigator.onLine){ window.location.href = '/cursos/'+id+'/ver'; return; }
                // If offline, try to open the cached view; we rely on the service worker to serve the cached course resources
                // Navigate to the lesson list for the course view — SW will serve cached files if available
                window.location.href = '/cursos/'+id+'/ver';
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function(){ setTimeout(renderDownloads, 150); });
})();
</script>
<script>
// Improved confirmation flow using a modal and inline alert area
(function(){
    const modalEl = document.getElementById('confirmActionModal');
    const bsModal = modalEl ? new bootstrap.Modal(modalEl) : null;
    const modalTitle = document.getElementById('confirmActionModalTitle');
    const modalBody = document.getElementById('confirmActionModalBody');
    const modalConfirm = document.getElementById('confirmActionModalConfirm');
    const alertArea = document.getElementById('downloadsAlert');
    const resultModalEl = document.getElementById('confirmResultModal');
    const resultBsModal = resultModalEl ? new bootstrap.Modal(resultModalEl) : null;
    const resultTitle = document.getElementById('confirmResultModalTitle');
    const resultBody = document.getElementById('confirmResultModalBody');
    const resultOk = document.getElementById('confirmResultModalOk');

    function showAlert(message, type='warning', timeout=4000){
        if(!alertArea) return;
        alertArea.innerHTML = `<div class="alert alert-${type}">${message}</div>`;
        alertArea.style.display = 'block';
        if(timeout>0) setTimeout(()=>{ alertArea.style.display = 'none'; alertArea.innerHTML = ''; }, timeout);
    }

    // replace confirm() usage by showing modal. Returns a Promise<boolean>
    function confirmAction(title, body){
        return new Promise((resolve)=>{
            try{
                if(modalTitle) modalTitle.innerText = title || 'Confirmar';
                if(modalBody) modalBody.innerText = body || '';
                if(!bsModal) { resolve(confirm(body || title)); return; }

                // Use a single hidden handler and a confirmed flag to avoid race conditions
                let confirmed = false;

                function onConfirm(){
                    // blur any focused element so it won't remain focused when modal is hidden
                    try{ if(document.activeElement && typeof document.activeElement.blur === 'function') document.activeElement.blur(); }catch(e){}
                    confirmed = true;
                    try{ if(bsModal && typeof bsModal.hide === 'function') bsModal.hide(); }catch(e){}
                }

                function onHidden(){
                    try{ modalConfirm.removeEventListener('click', onConfirm); modalEl.removeEventListener('hidden.bs.modal', onHidden); }catch(e){}
                    // small fallback cleanup in case backdrop remains
                    try{ document.body.classList.remove('modal-open'); }catch(_){ }
                    try{ document.querySelectorAll('.modal-backdrop').forEach(function(b){ try{ b.parentNode && b.parentNode.removeChild(b); }catch(_){}}); }catch(_){ }
                    resolve(!!confirmed);
                }

                modalConfirm.addEventListener('click', onConfirm, { once: true });
                modalEl.addEventListener('hidden.bs.modal', onHidden, { once: true });
                bsModal.show();
            }catch(e){ resolve(false); }
        });
    }

    // expose helpers for renderDownloads to use
    window.__downloadsConfirm = confirmAction;
    window.__downloadsAlert = showAlert;
    // Show result modal: { title, body, type='success' }
    function showResult(opts){
        try{
            if(!opts) opts = {};
            if(resultTitle) resultTitle.innerText = opts.title || (opts.type==='success' ? 'Completado' : 'Resultado');
            if(resultBody) resultBody.innerText = opts.body || '';
            if(resultBsModal) {
                // show and then focus the OK button once visible to avoid aria-hidden focus warnings
                resultBsModal.show();
                try{
                    resultModalEl.addEventListener('shown.bs.modal', function onShown(){
                        try{ if(resultOk && typeof resultOk.focus === 'function') resultOk.focus(); }catch(_){ }
                        resultModalEl.removeEventListener('shown.bs.modal', onShown);
                    });
                }catch(e){}
            }
            return Promise.resolve(true);
        }catch(e){ return Promise.resolve(false); }
    }
    window.__downloadsShowResult = showResult;

    if(resultOk){ resultOk.addEventListener('click', function(){ try{ if(resultBsModal) resultBsModal.hide(); }catch(e){}; setTimeout(function(){ try{ renderDownloads(); }catch(_){ } }, 250); }); }
})();
</script>
<script>
// Cuando estamos en la vista de administrar descargas y estamos offline,
// bloquear navegación hacia otras secciones (Mis Cursos, Directorio) porque
// esas vistas pueden requerir acceso a la red.
(function(){
    function enforceOfflineRestrictions(){
        if(navigator.onLine) return; // nothing to do when online
        // Add a visual banner explaining restrictions
        try{
            const banner = document.createElement('div');
            banner.className = 'alert alert-warning';
            banner.style.marginBottom = '12px';
            banner.innerHTML = '<strong>Modo sin conexión:</strong> sólo puedes navegar dentro de tus descargas locales. Otras vistas están deshabilitadas.';
            const wrap = document.getElementById('downloadsList');
            if(wrap && wrap.parentNode) wrap.parentNode.insertBefore(banner, wrap);
        }catch(e){}

        // Find header links and disable those that are not /descargas
        try{
            document.querySelectorAll('.main-menu a').forEach(function(a){
                const href = (a.getAttribute('href') || a.getAttribute('data-target-url') || '');
                const isDownloads = href.indexOf('/descargas') !== -1;
                if(!isDownloads){
                    a.classList.add('disabled-link');
                    a.setAttribute('aria-disabled','true');
                    // intercept click
                    if(!a.__offlineBlockAttached){
                        a.addEventListener('click', function(e){ e.preventDefault(); e.stopPropagation(); return false; });
                        a.__offlineBlockAttached = true;
                    }
                }
            });
        }catch(e){}

        // Also ensure view buttons don't redirect to networked pages; if they do, prevent navigation
        try{
            document.querySelectorAll('.view-download-btn').forEach(function(b){
                b.addEventListener('click', function(e){
                    e.preventDefault(); e.stopPropagation();
                    const id = b.getAttribute('data-id');
                    // Instead of navigating, show a modal/inline view if desired. For now, open cached content via SW by attempting to navigate but only if SW confirms cached.
                    // We'll attempt to open the course view in a new tab so current page retains restrictions.
                    try{ window.open('/cursos/' + id + '/ver', '_blank'); }catch(e){}
                });
            });
        }catch(e){}
    }

    // Run on load and on offline event
    document.addEventListener('DOMContentLoaded', function(){ setTimeout(enforceOfflineRestrictions, 200); });
    window.addEventListener('offline', function(){ setTimeout(enforceOfflineRestrictions, 200); });
})();
</script>
@endpush
