// Utilidades compartidas para microcursos.js
(function(){
    if(window.MicrocursosUtils) return; // don't override if already present

    function debounce(fn, wait){
        let t = null;
        return function(){
            const ctx = this, args = arguments;
            clearTimeout(t);
            t = setTimeout(function(){ fn.apply(ctx, args); }, wait);
        };
    }

    function $(sel){ try{ return document.querySelector(sel); }catch(e){ return null; } }
    function $all(sel){ try{ return Array.from(document.querySelectorAll(sel)); }catch(e){ return []; } }

    function ensureToastContainer(){
        const existing = document.getElementById('appToasts');
        if (existing) return existing;
        const container = document.createElement('div');
        container.id = 'appToasts';
        container.style.position = 'fixed';
        container.style.top = '1rem';
        container.style.right = '1rem';
        container.style.zIndex = 1080;
        container.setAttribute('aria-live', 'polite');
        container.setAttribute('aria-atomic', 'true');
        document.body.appendChild(container);
        return container;
    }

    function showBootstrapToast(type, title, message){
        const container = ensureToastContainer();
        const id = 'toast_' + Date.now() + Math.floor(Math.random() * 1000);
        const iconMap = { success: 'bi-check-circle-fill', info: 'bi-info-circle-fill', warning: 'bi-exclamation-triangle-fill', danger: 'bi-x-circle-fill' };
        const bgClass = type === 'danger' ? 'bg-danger text-white' : (type === 'warning' ? 'bg-warning text-dark' : 'bg-light');
        const useWhiteClose = type === 'danger';
        const toast = document.createElement('div');
        toast.className = 'toast ' + bgClass;
        toast.id = id;
        toast.role = 'alert';
        toast.ariaLive = 'assertive';
        toast.ariaAtomic = 'true';
        toast.style.minWidth = '250px';
        toast.style.marginBottom = '0.5rem';
        toast.innerHTML = `
            <div class="d-flex align-items-start">
                <div class="toast-body">
                    <div class="d-flex align-items-center">
                        <div style="font-size:1.2rem; margin-right:0.5rem"><i class="bi ${iconMap[type] || 'bi-info-circle-fill'}"></i></div>
                        <div>
                            <div style="font-weight:600">${title}</div>
                            <div style="font-size:0.9rem">${message}</div>
                        </div>
                    </div>
                </div>
                <button type="button" class="${useWhiteClose ? 'btn-close btn-close-white' : 'btn-close'} me-2 m-auto" data-bs-dismiss="toast" aria-label="Cerrar"></button>
            </div>
        `;
        container.appendChild(toast);
        try {
            if (window.bootstrap && window.bootstrap.Toast) {
                const btoast = new bootstrap.Toast(toast, { delay: 5000 });
                btoast.show();
                toast.addEventListener('hidden.bs.toast', () => { toast.remove(); });
            } else {
                setTimeout(() => { toast.remove(); }, 5000);
            }
        } catch (e) { setTimeout(() => { toast.remove(); }, 5000); }
    }

    function showCenteredModal(type, title, message){
        const iconMap = { success: 'bi-check-circle-fill', info: 'bi-info-circle-fill', warning: 'bi-exclamation-triangle-fill', danger: 'bi-x-circle-fill' };
        const colorMap = { success: 'text-success', info: 'text-primary', warning: 'text-warning', danger: 'text-danger' };
        const modalId = 'centeredModal_' + Date.now() + Math.floor(Math.random() * 1000);
        const wrapper = document.createElement('div');
        wrapper.innerHTML = `
            <div class="modal fade" id="${modalId}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content" style="border-radius:12px;">
                        <div class="modal-body text-center p-4">
                            <div class="mb-3" style="font-size:3rem;"><i class="bi ${iconMap[type] || 'bi-info-circle-fill'} ${colorMap[type] || 'text-primary'}"></i></div>
                            <h4 class="mb-2" style="font-weight:700; font-size:1.1rem; word-break:break-word; margin-bottom:0.5rem;">${title}</h4>
                            <div class="mb-3 activity-modal-body" style="background: rgba(255,255,255,0.94) !important; color: #050505 !important; font-size:0.95rem !important; line-height:1.6 !important; text-align:left !important; font-weight:400 !important; opacity:1 !important; padding:0.6rem 0.8rem; border-radius:8px; box-shadow: 0 1px 0 rgba(0,0,0,0.02) inset;">
                                ${message}
                            </div>
                            <div class="d-grid">
                                <button type="button" class="btn btn-${type === 'danger' ? 'secondary' : 'primary'}" data-bs-dismiss="modal">Cerrar</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(wrapper);
        const modalEl = document.getElementById(modalId);
        try {
            const bsModal = new bootstrap.Modal(modalEl);
            modalEl.addEventListener('hidden.bs.modal', function () { try { modalEl.remove(); } catch (e) { } });
            bsModal.show();
        } catch (e) { alert(title + '\n\n' + message); try { modalEl.remove(); } catch (e) { } }
    }

    function activityDoneKey(){
        const userPart = (typeof window.CURRENT_USER !== 'undefined' && window.CURRENT_USER) ? String(window.CURRENT_USER) : 'anon';
        const lessonPart = (window.LECCION_ID || 'global');
        return `activity_done_${userPart}_${lessonPart}`;
    }

    // Lightweight fetch helper that attaches CSRF token if present
    function fetchWithCsrf(url, opts){
        opts = opts || {};
        opts.headers = opts.headers || {};
        try{ const csrf = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : null; if(csrf) opts.headers['X-CSRF-TOKEN'] = csrf; }catch(e){}
        return fetch(url, opts);
    }

    window.MicrocursosUtils = { debounce, $, $all, ensureToastContainer, showBootstrapToast, showCenteredModal, activityDoneKey, fetchWithCsrf };
})();
