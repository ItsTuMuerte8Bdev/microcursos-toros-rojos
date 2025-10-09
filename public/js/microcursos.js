// Interacciones frontend de Microcursos
;(function () {
    // Encapsular toda la inicialización en una función para poder
    // re-ejecutarla tras navegaciones parciales (PJAX/Turbolinks/HTMX)
    // o cuando el DOM esté listo.
    function initMicrocursos() {
        function $(sel) { return document.querySelector(sel); }
        function $all(sel) { return Array.from(document.querySelectorAll(sel)); }

        // Helper to build a localStorage key for activity 'done' state.
        // Scope it by current user when available to avoid leaking marks between users/sessions.
        function activityDoneKey() {
            // use 'anon' when no authenticated user is present
            const userPart = (typeof window.CURRENT_USER !== 'undefined' && window.CURRENT_USER) ? String(window.CURRENT_USER) : 'anon';
            const lessonPart = (window.LECCION_ID || 'global');
            return `activity_done_${userPart}_${lessonPart}`;
        }

    // Example content for demo (would come from DB or API). If server injected lesson-specific HTML, use it.
    const exampleHtml = (window.LESSON_EXAMPLE_HTML && typeof window.LESSON_EXAMPLE_HTML === 'string') ? window.LESSON_EXAMPLE_HTML : `
        <div class="border p-2">
            <strong>Correo A (posible phishing)</strong>
            <p>De: soporte@seguridad-login.com<br/>Asunto: Verifica tu cuenta ahora</p>
            <small class="text-muted">Contiene enlaces extraños y solicita credenciales.</small>
        </div>
    `;

        // Helper de toasts (Bootstrap 5) - crea el contenedor y muestra toasts con icono
        function ensureToastContainer() {
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

        function showBootstrapToast(type, title, message) {
            // tipos: success, info, warning, danger
            const container = ensureToastContainer();
            const id = 'toast_' + Date.now() + Math.floor(Math.random() * 1000);
            const iconMap = {
                success: 'bi-check-circle-fill',
                info: 'bi-info-circle-fill',
                warning: 'bi-exclamation-triangle-fill',
                danger: 'bi-x-circle-fill'
            };

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

            // Inicializar con Bootstrap si está disponible
            try {
                if (window.bootstrap && window.bootstrap.Toast) {
                    const btoast = new bootstrap.Toast(toast, { delay: 5000 });
                    btoast.show();
                    toast.addEventListener('hidden.bs.toast', () => { toast.remove(); });
                } else {
                    // Fallback: eliminar tras un retraso
                    setTimeout(() => { toast.remove(); }, 5000);
                }
            } catch (e) {
                setTimeout(() => { toast.remove(); }, 5000);
            }
        }

        // Exponer globalmente para que vistas blade/inline scripts puedan llamarlo
        try { window.showBootstrapToast = showBootstrapToast; } catch (e) {}

        // Centered modal helper (style like capture 2)
        // Modal centrado con título y contenido (útil para mostrar soluciones o avisos)
        function showCenteredModal(type, title, message) {
            // type: success, info, warning, danger
            const iconMap = {
                success: 'bi-check-circle-fill',
                info: 'bi-info-circle-fill',
                warning: 'bi-exclamation-triangle-fill',
                danger: 'bi-x-circle-fill'
            };
            const colorMap = {
                success: 'text-success',
                info: 'text-primary',
                warning: 'text-warning',
                danger: 'text-danger'
            };

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

            // Forzar estilos legibles dentro del body de la actividad para evitar reglas globales que lo
            // hagan aparecer atenuado.
            try {
                const actBody = modalEl.querySelector('.activity-modal-body');
                if (actBody) {
                    try {
                        actBody.style.setProperty('color', '#0b0b0b', 'important');
                        actBody.style.setProperty('opacity', '1', 'important');
                        actBody.style.setProperty('filter', 'none', 'important');
                        actBody.style.setProperty('-webkit-filter', 'none', 'important');
                        actBody.style.setProperty('mix-blend-mode', 'normal', 'important');
                        actBody.style.setProperty('background', 'rgba(255,255,255,0.98)', 'important');
                    } catch (e) {}

                    const children = actBody.querySelectorAll('*');
                    children.forEach(function (el) {
                        try {
                            el.style.setProperty('color', '#0b0b0b', 'important');
                            el.style.setProperty('opacity', '1', 'important');
                            el.style.setProperty('filter', 'none', 'important');
                            el.style.setProperty('-webkit-filter', 'none', 'important');
                            el.style.setProperty('mix-blend-mode', 'normal', 'important');
                            el.style.setProperty('background', 'transparent', 'important');
                        } catch (e) {}
                    });
                }
            } catch (e) {}

            try {
                const bsModal = new bootstrap.Modal(modalEl);
                modalEl.addEventListener('hidden.bs.modal', function () { try { modalEl.remove(); } catch (e) {} });
                bsModal.show();
            } catch (e) {
                // Fallback: alert
                alert(title + '\n\n' + message);
                try { modalEl.remove(); } catch (e) {}
            }
        }
        try { window.showCenteredModal = showCenteredModal; } catch (e) {}

    if (document.getElementById('exampleArea')){
        document.getElementById('exampleArea').innerHTML = exampleHtml;
        document.getElementById('showSolution').addEventListener('click', function(){
            const sol = (typeof window.LESSON_SOLUTION_HTML === 'string' && window.LESSON_SOLUTION_HTML) ? window.LESSON_SOLUTION_HTML : 'Tip: revisa el dominio del remitente, busca errores ortográficos y nunca ingreses credenciales desde enlaces.';
            showCenteredModal('info', window.location.hostname + ' dice', sol);
        });
    }

    // Activity: generate some options
    if (document.getElementById('activityOptions')){
        // Allow server to inject custom activity options via window.LESSON_ACTIVITY_OPTIONS (array of {text, safe})
        const options = (Array.isArray(window.LESSON_ACTIVITY_OPTIONS) && window.LESSON_ACTIVITY_OPTIONS.length) ? window.LESSON_ACTIVITY_OPTIONS : [
            {text: 'https://tu-banco.com/login', safe: true},
            {text: 'http://secure-login.verify-account.com', safe: false},
            {text: 'https://accounts.google.com', safe: true},
            {text: 'http://bit.ly/1234', safe: false}
        ];
        const container = document.getElementById('activityOptions');
        container.innerHTML = '';
        options.forEach((o, i)=>{
            const text = (o && o.text) ? String(o.text) : '';
            // heuristic: consider it a URL if it starts with http(s) or contains a domain-like pattern
            const isUrl = /^https?:\/\//i.test(text) || /\bwww\./i.test(text) || (text.includes('://') && text.includes('.'));
            if (isUrl) {
                // render as a button styled like a link to prevent default navigation behavior
                // Use a button so clicks are fully controlled (no native anchor navigation)
                const btn = document.createElement('button');
                btn.type = 'button';
                // marker class for CSS truncation and JS targeting
                btn.className = 'btn btn-sm btn-outline-primary m-1 activity-link-btn';
                btn.textContent = text;
                // store the url in a data attribute instead of href
                btn.dataset.url = text;
                btn.addEventListener('click', (e)=>{
                    // prevent default behavior (though buttons don't navigate by default)
                    e.preventDefault();
                    // Visual feedback and controlled navigation only when safe
                    if (o && o.safe) {
                        btn.classList.remove('btn-outline-primary'); btn.classList.add('btn-success');
                        showCenteredModal('success', 'Correcto', 'Correcto — enlace seguro.');
                        // open link after short delay so modal is visible briefly
                        setTimeout(()=>{ try{ window.open(text, '_blank', 'noopener,noreferrer'); }catch(e){} }, 250);
                    } else {
                        btn.classList.remove('btn-outline-primary'); btn.classList.add('btn-danger');
                        showCenteredModal('danger', 'Cuidado', 'Cuidado — posible enlace malicioso.');
                    }
                });
                container.appendChild(btn);
            } else {
                // not a URL: show as a simple instruction block (non-link) with a 'Hecho' button
                const wrapper = document.createElement('div');
                wrapper.className = 'd-inline-block align-middle m-1';
                wrapper.style.maxWidth = '100%';

                const info = document.createElement('div');
                info.className = 'alert alert-secondary d-inline-block p-2 m-0 me-2';
                info.style.cursor = 'pointer';
                info.style.display = 'inline-block';
                info.style.verticalAlign = 'middle';
                info.textContent = text || 'Actividad';
                // clicking shows a richer info modal with instruction and tips (no warning)
                info.addEventListener('click', ()=>{
                    // prefer explicit description from server-provided option
                    const desc = (o && (o.description || o.detail || o.help)) ? (o.description || o.detail || o.help) : null;
                    const lessonTitle = (document.querySelector('h1') && document.querySelector('h1').textContent.trim()) || window.LECCION_TITLE || 'Actividad';
                    let bodyHtml = '';
                    if (desc) {
                        bodyHtml = `<p>${desc}</p>`;
                    } else {
                        // generate a helpful modal body using lesson title and the activity text
                        bodyHtml = `<p><strong>Instrucción:</strong> ${text}</p>`;
                        bodyHtml += `<p><strong>¿Cómo hacerlo?</strong> Realiza la actividad indicada en tu entorno local y, si procede, sube una evidencia (captura o nota) al repositorio de pruebas o compártela con un compañero.</p>`;
                        bodyHtml += `<p><strong>Consejos:</strong></p><ul><li>Sigue los pasos prácticos de la lección: revisa ejemplos y práctica repetida.</li><li>Si necesitas evidencia, toma una captura clara.</li><li>Pide feedback a un compañero para mejorar.</li></ul>`;
                    }
                    // Prefer an explicit activity title from the option, then the option text, then the lesson title
                    const activityLabel = (o && (o.title || o.label)) ? (o.title || o.label) : (text || lessonTitle);
                    const modalTitle = `Actividad — ${activityLabel}`;
                    // show modal — message can include HTML
                    showCenteredModal('info', modalTitle, bodyHtml);
                });

                const doneBtn = document.createElement('button');
                doneBtn.className = 'btn btn-sm btn-outline-primary';
                doneBtn.style.verticalAlign = 'middle';
                doneBtn.textContent = 'Hecho';
                // stop propagation so clicking button doesn't open the info modal
                doneBtn.addEventListener('click', (e)=>{
                    e.stopPropagation();
                    const doneKey = activityDoneKey();
                    if (!doneBtn.dataset.done) {
                        doneBtn.dataset.done = '1';
                        doneBtn.className = 'btn btn-sm btn-success';
                        showBootstrapToast('success', 'Actividad', 'Actividad marcada como hecha.');
                        try{ localStorage.setItem(doneKey, '1'); }catch(e){}
                    } else {
                        delete doneBtn.dataset.done;
                        doneBtn.className = 'btn btn-sm btn-outline-primary';
                        showBootstrapToast('info', 'Actividad', 'Marca de actividad removida.');
                        try{ localStorage.removeItem(doneKey); }catch(e){}
                    }
                });

                // restore previous done state from localStorage
                try{
                    // Check the new per-user key first, then fall back to the legacy key for migration.
                    const prev = localStorage.getItem(activityDoneKey()) || localStorage.getItem('activity_done_'+(window.LECCION_ID || 'global'));
                    if (prev) { doneBtn.dataset.done = '1'; doneBtn.className = 'btn btn-sm btn-success'; }
                }catch(e){}

                wrapper.appendChild(info);
                wrapper.appendChild(doneBtn);
                container.appendChild(wrapper);
            }
        });
    }

    // Quiz (single or multi-question with choices)
    if (document.getElementById('quizOptions')){
        const quizContainer = document.getElementById('quizOptions');
        const quizFeedback = document.getElementById('quizFeedback');
        const quizQuestionEl = document.getElementById('quizQuestion');
        quizContainer.innerHTML = '';

        const qdata = (Array.isArray(window.LESSON_QUIZ_OPTIONS) && window.LESSON_QUIZ_OPTIONS.length) ? window.LESSON_QUIZ_OPTIONS : null;

        // helper to submit result (same payload as before)
        function submitResultado(puntaje){
            const idEval = window.LESSON_EVALUATION_ID || null;
            const idUsuario = window.CURRENT_USER || null;
            const csrf = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : null;
            if (!idEval || !idUsuario) return; // nothing to submit
            const payload = { id_usuario: idUsuario, id_evaluacion: idEval, puntaje_obtenido: puntaje, fecha: new Date().toISOString() };
            fetch('/api/resultados', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type':'application/json', 'X-Requested-With':'XMLHttpRequest', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: JSON.stringify(payload)
            }).then(r=>{
                if (r.ok) showBootstrapToast('success', 'Evaluación', 'Resultado guardado.');
                else if (r.status === 401) try{ var loginModal = new bootstrap.Modal(document.getElementById('loginRequiredModal')); loginModal.show(); }catch(e){ window.location.href = '/login'; }
                else showBootstrapToast('warning', 'Evaluación', 'No fue posible guardar el resultado.');
            }).catch(err=>{
                const pending = JSON.parse(localStorage.getItem('pendingResultados')||'[]');
                pending.push(payload);
                localStorage.setItem('pendingResultados', JSON.stringify(pending));
                showBootstrapToast('info', 'Evaluación', 'Resultado guardado localmente (offline).');
            });
        }

        if (qdata && qdata.length && qdata[0].choices && Array.isArray(qdata[0].choices)){
            // multi-question quiz format: array of questions, each with choices
            // hide the static header because we render per-question titles
            if (quizQuestionEl) quizQuestionEl.style.display = 'none';
            qdata.forEach((q, qi)=>{
                const qWrap = document.createElement('div');
                qWrap.className = 'mb-3 p-2 border rounded';
                const qTitle = document.createElement('div');
                qTitle.className = 'fw-semibold mb-2';
                qTitle.textContent = (q.text || ('Pregunta ' + (qi+1)));
                qWrap.appendChild(qTitle);

                const choicesWrap = document.createElement('div');
                q.choices.forEach((ch, ci)=>{
                    const cb = document.createElement('button');
                    cb.className = 'btn btn-sm btn-outline-secondary m-1';
                    cb.textContent = ch.text || ('Opción ' + (ci+1));
                    cb.addEventListener('click', ()=>{
                        // disable choice buttons for this question
                        Array.from(choicesWrap.querySelectorAll('button')).forEach(b=>b.disabled = true);
                        // show feedback
                        const fb = document.createElement('div');
                        fb.className = 'mt-2';
                        fb.innerHTML = ch.feedback || (ch.correct ? '<span class="text-success">¡Correcto!</span>' : '<span class="text-danger">Respuesta no correcta.</span>');
                        qWrap.appendChild(fb);
                        // visually mark chosen
                        if (ch.correct) cb.className = 'btn btn-sm btn-success m-1'; else cb.className = 'btn btn-sm btn-danger m-1';
                        // submit single result (puntaje 1 or 0)
                        submitResultado(ch.correct ? 1 : 0);
                    });
                    choicesWrap.appendChild(cb);
                });
                qWrap.appendChild(choicesWrap);
                quizContainer.appendChild(qWrap);
            });
        } else if (qdata && qdata.length) {
            // single-question style but with server-provided options array
            // show the static header or replace it with the question text if provided
            if (quizQuestionEl) quizQuestionEl.textContent = qdata[0].text || quizQuestionEl.textContent;
            qdata.forEach(o=>{
                const btn = document.createElement('button');
                btn.className = 'btn btn-sm btn-outline-secondary m-1';
                btn.textContent = o.text;
                btn.addEventListener('click', ()=>{
                    // feedback area
                    if (o && o.feedback) quizFeedback.innerHTML = o.feedback;
                    else quizFeedback.innerHTML = (o.correct ? '<span class="text-success">¡Correcto!</span>' : '<span class="text-danger">Respuesta no correcta.</span>');
                    submitResultado(o.correct ? 1 : 0);
                });
                quizContainer.appendChild(btn);
            });
        } else {
            // fallback: a simple default question
            const btn = document.createElement('button');
            btn.className = 'btn btn-sm btn-outline-secondary m-1';
            btn.textContent = 'Usar un gestor de contraseñas y 2FA';
            btn.addEventListener('click', ()=>{ quizFeedback.innerHTML = '<span class="text-success">¡Correcto!</span>'; submitResultado(1); });
            quizContainer.appendChild(btn);
        }
    }

    // Mark complete -> POST progress
    if (document.getElementById('markComplete')){
        document.getElementById('markComplete').addEventListener('click', function(){
            const id_leccion = window.LECCION_ID || null;
            if (!id_leccion) { showCenteredModal('warning', 'Error', 'ID de lección no encontrado'); return; }
            const userId = window.CURRENT_USER || null;
            const csrf = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : null;
            fetch('/api/progreso', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type':'application/json', 'X-Requested-With':'XMLHttpRequest', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: JSON.stringify({ id_usuario: userId, id_leccion: id_leccion, completado: true })
            }).then(r=>{
                if (r.status === 401){
                    // not authenticated
                    try{ var loginModal = new bootstrap.Modal(document.getElementById('loginRequiredModal')); loginModal.show(); }catch(e){ window.location.href = '/login'; }
                    return;
                }
                if (r.status === 403){
                    r.json().then(j=>{ document.getElementById('completionMsg').innerHTML = '<span class="text-danger">'+(j.error||'No autorizado')+'</span>'; });
                    return;
                }
                if (r.ok) {
                    document.getElementById('completionMsg').innerHTML = '<span class="text-success">¡Lección completada! 🎉</span>';
                    // refresh course progress if present
                    if (window.CURSO_ID) fetchCourseProgress(window.CURSO_ID, userId);
                } else {
                    document.getElementById('completionMsg').innerHTML = '<span class="text-warning">Error al guardar progreso.</span>';
                }
            }).catch(err=>{
                console.error(err);
                document.getElementById('completionMsg').innerHTML = '<span class="text-muted">Progreso guardado localmente (offline).</span>';
                // store in localStorage for later sync (simple demo)
                const pending = JSON.parse(localStorage.getItem('pendingProgreso')||'[]');
                pending.push({ id_usuario:userId, id_leccion:id_leccion, completado:true, ts:Date.now() });
                localStorage.setItem('pendingProgreso', JSON.stringify(pending));
            });
        });
    }

    // On load, try to flush pending progreso
    function flushPending(){
        const pending = JSON.parse(localStorage.getItem('pendingProgreso')||'[]');
        if (!pending.length) return;
        const csrf = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : null;
            pending.forEach(item=>{
            fetch('/api/progreso', { method:'POST', credentials: 'same-origin', headers:{ 'Content-Type':'application/json', 'X-Requested-With':'XMLHttpRequest', 'X-CSRF-TOKEN': csrf, 'Accept':'application/json' }, body:JSON.stringify(item) })
            .then(r=>{ if (r.ok) { /* TODO: remove item from pending list */ }}).catch(()=>{});
        });
        localStorage.removeItem('pendingProgreso');
    }
    window.addEventListener('online', flushPending);
    flushPending();

    // Try to flush pending resultados saved while offline
    function flushPendingResultados(){
        const pending = JSON.parse(localStorage.getItem('pendingResultados')||'[]');
        if (!pending.length) return;
        const csrf = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : null;
        pending.forEach(item=>{
            fetch('/api/resultados', { method:'POST', credentials: 'same-origin', headers:{ 'Content-Type':'application/json', 'X-Requested-With':'XMLHttpRequest', 'X-CSRF-TOKEN': csrf, 'Accept':'application/json' }, body:JSON.stringify(item) })
            .then(r=>{ /* TODO: remove item from pending list on success */ }).catch(()=>{});
        });
        localStorage.removeItem('pendingResultados');
    }
    window.addEventListener('online', flushPendingResultados);
    flushPendingResultados();

    // Fetch and show course progress if element exists (single)
    function fetchCourseProgress(cursoId, userId){
        if (!cursoId) return;
        const uid = userId || window.CURRENT_USER || 1;
    // append cache-busting timestamp to avoid returning stale cached API responses
    const progressUrl = `/api/cursos/${cursoId}/progress?id_usuario=${encodeURIComponent(uid)}&_ts=${Date.now()}`;
    fetch(progressUrl, { credentials: 'same-origin', cache: 'no-store' })
            .then(r=>r.json())
            .then(data=>{
                const el = document.getElementById('cursoProgress');
                if (!el) return;
                // Main course progress
                el.innerHTML = `<div class="mb-2"><div class="progress" style="height:8px;"><div class="progress-bar" role="progressbar" style="width: ${data.percent}%" aria-valuenow="${data.percent}" aria-valuemin="0" aria-valuemax="100"></div></div><small class="text-muted">${data.percent}% completado</small></div>`;
                if (data.percent >= 100) {
                    const badge = document.createElement('div'); badge.className='badge bg-success'; badge.textContent='Completado'; el.appendChild(badge);
                } else if (data.percent >= 50) {
                    const badge = document.createElement('div'); badge.className='badge bg-info'; badge.textContent='En progreso'; el.appendChild(badge);
                }

                // Per-module progress (if provided)
                const listWrap = document.getElementById('moduleProgressList');
                if (listWrap && data.moduleProgress) {
                    listWrap.innerHTML = '';
                    // data.moduleProgress keyed by id_modulo or numeric index; iterate
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

    // Flag to indicate we're fetching courses/progress to avoid racey filtering
    let isFetchingCourses = false;

    // Batch fetch progress for all course cards on the page
    // Returns a Promise that resolves when progress has been applied to cards
    function fetchBatchProgress(courseIds, userId){
        if (!courseIds || !courseIds.length) return;
        const uid = userId || window.CURRENT_USER || 1;
        const csrf = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : null;
        // show spinner in each placeholder while loading
        courseIds.forEach(cid=>{
            const card = document.querySelector(`.course-card[data-curso-id="${cid}"]`);
            if (!card) return;
            const placeholder = card.querySelector('.course-progress-placeholder');
            if (!placeholder) return;
            placeholder.innerHTML = `<div class="mb-2"><div class="progress" style="height:8px;"><div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 30%"></div></div><small class="text-muted">Cargando...</small></div>`;
        });

        return fetch('/api/cursos/progress-batch', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type':'application/json', 'X-Requested-With':'XMLHttpRequest', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: JSON.stringify({ ids: courseIds, id_usuario: uid })
        }).then(r=>{
            // Read raw text always so we can handle HTML/error pages gracefully
            return r.text().then(txt=>{
                if (!r.ok){
                    // If unauthorized, prompt login so user can re-authenticate
                    if (r.status === 401){
                        try{ var loginModal = new bootstrap.Modal(document.getElementById('loginRequiredModal')); loginModal.show(); }
                        catch(e){ window.location.href = '/login'; }
                    }
                    console.warn('Batch progress fetch failed', r.status, r.statusText);
                    console.warn('Batch progress response body:', txt);
                    return null;
                }

                // Try to parse JSON; if it fails dump raw text to console for diagnosis
                try {
                    const parsed = JSON.parse(txt || '{}');
                    return parsed;
                } catch (err) {
                    console.error('Batch progress parse error:', err);
                    console.error('Batch progress response text:', txt);
                    return null;
                }
            });
        }).then(data=>{
            if(!data) {
                // Update placeholders with a friendly error so UI doesn't break
                courseIds.forEach(cid=>{
                    const card = document.querySelector(`.course-card[data-curso-id="${cid}"]`);
                    if (!card) return;
                    const placeholder = card.querySelector('.course-progress-placeholder');
                    if (!placeholder) return;
                    placeholder.innerHTML = '<small class="text-muted text-danger">Error al cargar progreso</small>';
                });
                return;
            }
            // data is an object keyed by cursoId
            Object.keys(data).forEach(cid=>{
                const info = data[cid];
                const card = document.querySelector(`.course-card[data-curso-id="${cid}"]`);
                if (!card) return;
                const placeholder = card.querySelector('.course-progress-placeholder');
                if (!placeholder) return;
                placeholder.innerHTML = `<div class="mb-2"><div class="progress" style="height:8px;"><div class="progress-bar" role="progressbar" style="width: ${info.percent}%" aria-valuenow="${info.percent}" aria-valuemin="0" aria-valuemax="100"></div></div><small class="text-muted">${info.percent}% completado</small></div>`;
                if (info.percent >= 100){
                    const badge = document.createElement('span'); badge.className='badge bg-success ms-2'; badge.textContent='Completado';
                    // append badge if not present
                    if (!card.querySelector('.badge.bg-success')) placeholder.appendChild(badge);
                } else if (info.percent >= 50){
                    const badge = document.createElement('span'); badge.className='badge bg-info ms-2'; badge.textContent='En progreso';
                    if (!card.querySelector('.badge.bg-info')) placeholder.appendChild(badge);
                }
                // store percent on card for filtering
                card.dataset.percent = info.percent;
            });
        }).catch(err=>{ console.error('Batch progress error', err); });
    }

    // Show a larger centered modal with Title / Description / Content (editable)
    function showCourseDetailModal(course){
        // course: { id, title, desc, content }
        const modalId = 'courseDetail_' + Date.now() + Math.floor(Math.random()*1000);
        const wrapper = document.createElement('div');
        wrapper.innerHTML = `
            <div class="modal fade" id="${modalId}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content" style="border-radius:12px; max-width:640px; margin:auto;">
                        <div class="modal-body p-4">
                            <h4 class="mb-3" style="font-weight:700">Editar curso</h4>
                            <div class="card shadow-sm p-3" style="border-radius:12px;">
                                <div class="mb-3">
                                    <label class="form-label" style="font-weight:700">Título</label>
                                    <input type="text" class="form-control" id="${modalId}_title" value="${(course.title||'').replace(/\"/g,'&quot;')}">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" style="font-weight:700">Descripción</label>
                                    <input type="text" class="form-control" id="${modalId}_desc" value="${(course.desc||'').replace(/\"/g,'&quot;')}">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" style="font-weight:700">Contenido</label>
                                    <textarea class="form-control" id="${modalId}_content" rows="6">${(course.content||'')}</textarea>
                                </div>
                                <div class="d-flex justify-content-end">
                                    <!-- If user is admin allow saving, otherwise show read-only and hide save -->
                                    <button class="btn btn-primary me-2" id="${modalId}_save">Guardar</button>
                                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(wrapper);
        const modalEl = document.getElementById(modalId);
            try{
            const bsModal = new bootstrap.Modal(modalEl);
            modalEl.addEventListener('hidden.bs.modal', function(){ try{ modalEl.remove(); }catch(e){} });
            // attach save handler after appended to DOM
            setTimeout(()=>{
                    const btnSave = document.getElementById(`${modalId}_save`);
                    // If the current user role is not admin, disable/hide save and make fields readonly
                    try{
                        const role = (typeof window.CURRENT_USER_ROLE !== 'undefined') ? window.CURRENT_USER_ROLE : null;
                        if (role !== 'admin'){
                            // make inputs readonly
                            const inputs = modalEl.querySelectorAll('input, textarea');
                            inputs.forEach(i=>{ i.setAttribute('readonly','readonly'); i.setAttribute('disabled','disabled'); });
                            if (btnSave) btnSave.style.display = 'none';
                        } else {
                            if (btnSave){
                                btnSave.addEventListener('click', function(){
                                    const newTitle = document.getElementById(`${modalId}_title`).value.trim();
                                    const newDesc = document.getElementById(`${modalId}_desc`).value.trim();
                                    const newContent = document.getElementById(`${modalId}_content`).value;
                                    // simulate save: update card on page if present
                                    if (course.id){
                                        const card = document.querySelector(`.course-card[data-curso-id="${course.id}"]`);
                                        if (card){
                                            const t = card.querySelector('.card-title'); if (t) t.textContent = newTitle;
                                            const d = card.querySelector('.card-text'); if (d) d.textContent = newDesc;
                                            // option: store raw content in data attribute
                                            card.dataset.content = newContent;
                                        }
                                    }
                                    showBootstrapToast('success', 'Guardado', 'Los cambios se aplicaron localmente.');
                                    bsModal.hide();
                                });
                            }
                        }
                    }catch(e){
                        // if anything fails, hide save as a safety measure
                        if (btnSave) btnSave.style.display = 'none';
                    }
            }, 50);
            bsModal.show();
        }catch(e){
            // fallback: simple prompt chain
            const newTitle = prompt('Título', course.title||'');
            const newDesc = prompt('Descripción', course.desc||'');
            const newContent = prompt('Contenido', course.content||'');
            if (newTitle !== null){
                if (course.id){
                    const card = document.querySelector(`.course-card[data-curso-id="${course.id}"]`);
                    if (card){
                        const t = card.querySelector('.card-title'); if (t) t.textContent = newTitle;
                        const d = card.querySelector('.card-text'); if (d) d.textContent = newDesc;
                        card.dataset.content = newContent;
                    }
                }
                showBootstrapToast('success', 'Guardado', 'Los cambios se aplicaron localmente.');
            }
            try{ modalEl.remove(); }catch(e){}
        }
    }

    // Attach click handlers to course cards to open the detail modal
    (function attachCourseCardDetailHandlers(){
        const cards = Array.from(document.querySelectorAll('.course-card'));
        if (!cards.length) return;
        cards.forEach(card=>{
            // only open modal when clicking the card (avoid clicks on buttons inside card)
            card.addEventListener('click', function(e){
                // if click inside an interactive element with class 'no-detail', skip
                if (e.target.closest('.no-detail') || e.target.tagName === 'A' || e.target.closest('button')) return;
                // If user is not admin, do not open edit modal (show small info instead)
                try{
                    const role = (typeof window.CURRENT_USER_ROLE !== 'undefined') ? window.CURRENT_USER_ROLE : null;
                    if (role !== 'admin'){
                        showCenteredModal('info', 'Solo lectura', 'No tienes permisos para editar este curso.');
                        return;
                    }
                }catch(e){}

                const title = card.querySelector('.card-title') ? card.querySelector('.card-title').textContent.trim() : (card.dataset.title || '');
                const desc = card.querySelector('.card-text') ? card.querySelector('.card-text').textContent.trim() : (card.dataset.description || '');
                const content = card.dataset.content || (card.querySelector('.course-content') ? card.querySelector('.course-content').innerHTML : '');
                const cursoId = card.dataset.cursoId || card.getAttribute('data-curso-id') || null;
                showCourseDetailModal({ id: cursoId, title: title, desc: desc, content: content });
            });
        });
    })();

    // Try populate curso progress on load if curso id present
    if (window.CURSO_ID) fetchCourseProgress(window.CURSO_ID, window.CURRENT_USER);
    // If the page has a list of course ids, fetch batch progress
    if (window.PAGE_COURSE_IDS && Array.isArray(window.PAGE_COURSE_IDS)){
        fetchBatchProgress(window.PAGE_COURSE_IDS, window.CURRENT_USER);
    }

    // Client-side filtering: search + checkboxes
    const searchInput = document.getElementById('searchCourses');
    const filterCompleted = document.getElementById('filterCompleted');
    const filterInProgress = document.getElementById('filterInProgress');
    const filterCategory = document.getElementById('filterCategory');

    function applyFilters(){
        // If a fetch for courses/progress is in flight, wait briefly and retry
        if (typeof isFetchingCourses !== 'undefined' && isFetchingCourses){
            try{ setTimeout(applyFilters, 180); }catch(e){}
            return;
        }
        const q = searchInput ? searchInput.value.trim().toLowerCase() : '';
        const showCompleted = filterCompleted ? filterCompleted.checked : false;
        const showInProgress = filterInProgress ? filterInProgress.checked : false;
        const selectedCategory = filterCategory ? (filterCategory.value || '') : '';
        const cards = Array.from(document.querySelectorAll('.course-card'));
        cards.forEach(card=>{
            const title = (card.querySelector('.card-title') ? card.querySelector('.card-title').textContent : '').toLowerCase();
            const desc = (card.querySelector('.card-text') ? card.querySelector('.card-text').textContent : '').toLowerCase();
            const percent = parseInt(card.dataset.percent || '0', 10);
            const cardCat = card.getAttribute('data-category-id') || '';
            const cardCatName = (card.getAttribute('data-category-name') || '').toLowerCase();
            let matchesSearch = true;
            if (q) matchesSearch = title.includes(q) || desc.includes(q);

            let matchesStatus = true;
            if (showCompleted && !showInProgress){
                matchesStatus = percent >= 100;
            } else if (!showCompleted && showInProgress){
                matchesStatus = percent > 0 && percent < 100;
            } else if (showCompleted && showInProgress){
                matchesStatus = (percent >= 100) || (percent > 0 && percent < 100);
            }

            // Category filter: if a category is selected (id), only show cards matching that id
            let matchesCategory = true;
            if (selectedCategory) {
                // selectedCategory holds the id as string
                matchesCategory = (String(cardCat) === String(selectedCategory));
            }

            if (matchesSearch && matchesStatus && matchesCategory) card.style.display = '';
            else card.style.display = 'none';
        });
    }

    // New: server-side fetch for filtered courses. On filter change, request /api/cursos?q=&category=&per_page=12
    function fetchAndRenderCourses(){
        const q = searchInput ? searchInput.value.trim() : '';
        const category = filterCategory ? filterCategory.value : '';
        const per_page = 24;
        const url = `/api/cursos?q=${encodeURIComponent(q)}&category=${encodeURIComponent(category)}&per_page=${per_page}`;
    fetch(url, { credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }})
            .then(r=>{ if (!r.ok) throw new Error('Network response not ok'); return r.json(); })
            .then(data=>{
                // data is a paginated object: data.data is array of cursos
                const list = document.getElementById('coursesList');
                if (!list) return;
                const cursos = Array.isArray(data.data) ? data.data : [];
                // render cards
                list.innerHTML = '';
                const cursoIds = [];
                if (!cursos.length) { list.innerHTML = '<div class="col-12">No hay cursos disponibles.</div>'; return; }
                cursos.forEach(c => {
                    cursoIds.push(c.id_curso);
                    const catName = c.categoria ? (c.categoria.nombre || '') : '';
                    const card = document.createElement('div');
                    card.className = 'col-md-6 course-card';
                    card.setAttribute('data-curso-id', c.id_curso);
                    if (c.id_categoria) card.setAttribute('data-category-id', c.id_categoria);
                    card.setAttribute('data-category-name', catName);
                    // Select image by category name (simple heuristics, same as Blade)
                    (function(){
                        var imgName = '/images/Toro Cursos.png';
                        try{
                            var cn = (catName||'').toLowerCase();
                            if (cn.indexOf('dise') !== -1) imgName = '/images/Toro Diseño.png';
                            else if (cn.indexOf('negoc') !== -1 || cn.indexOf('negocio') !== -1) imgName = '/images/Toro Negocios.png';
                            else if (cn.indexOf('program') !== -1 || cn.indexOf('desarrol') !== -1) imgName = '/images/Toro Programador.png';
                        }catch(e){}
                        card.innerHTML = `
                            <div class="card h-100">
                                <img src="${imgName}" class="card-img-top" alt="${(c.titulo||'')}">
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title">${(c.titulo||'')}</h5>
                                    <p class="mb-1 text-muted small">Curso: ${(c.titulo||'—')}</p>
                                    <div class="mb-2">
                                        <div class="mb-1" style="font-weight:700; color:#333;">Contenido del curso</div>
                                        <p class="card-text text-muted">${(c.descripcion||'')}</p>
                                    </div>
                                    <div class="mt-auto">
                                        <div class="mb-2 course-progress-placeholder">
                                            <div class="progress" style="height:8px;"><div class="progress-bar" role="progressbar" style="width: 0%;"></div></div>
                                            <small class="text-muted">--% completado</small>
                                        </div>
                                        <div class="d-flex action-btn-row">
                                            <div class="action-btn-group">
                                                <a href="/cursos/${c.id_curso}/ver" class="btn btn-outline-primary btn-sm">Ver</a>
                                                <a href="/cursos/${c.id_curso}/continuar" class="btn btn-primary btn-sm">Continuar</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                    })();
                    list.appendChild(card);
                });
                // re-attach handlers
                try{ initMicrocursos(); }catch(e){}

                // Disable status controls while we fetch progress to avoid race conditions
                try{
                    isFetchingCourses = true;
                    if (filterCompleted) filterCompleted.disabled = true;
                    if (filterInProgress) filterInProgress.disabled = true;
                    if (searchInput) searchInput.disabled = true;
                }catch(e){}

                // fetch batch progress for visible cursoIds and then apply client filters
                try{
                    if (cursoIds.length) {
                        fetchBatchProgress(cursoIds, window.CURRENT_USER)
                        .then(()=>{
                            // re-enable controls and apply filters now that percents are set
                            try{ isFetchingCourses = false; if (filterCompleted) filterCompleted.disabled = false; if (filterInProgress) filterInProgress.disabled = false; if (searchInput) searchInput.disabled = false; }catch(e){}
                            try{ applyFilters(); }catch(e){}
                        }).catch((err)=>{
                            try{ isFetchingCourses = false; if (filterCompleted) filterCompleted.disabled = false; if (filterInProgress) filterInProgress.disabled = false; if (searchInput) searchInput.disabled = false; }catch(e){}
                            try{ applyFilters(); }catch(e){}
                        });
                    } else {
                        // nothing to fetch; ensure controls re-enabled and filters applied
                        try{ isFetchingCourses = false; if (filterCompleted) filterCompleted.disabled = false; if (filterInProgress) filterInProgress.disabled = false; if (searchInput) searchInput.disabled = false; }catch(e){}
                        try{ applyFilters(); }catch(e){}
                    }
                }catch(e){
                    try{ isFetchingCourses = false; if (filterCompleted) filterCompleted.disabled = false; if (filterInProgress) filterInProgress.disabled = false; if (searchInput) searchInput.disabled = false; }catch(err){}
                    try{ applyFilters(); }catch(err){}
                }
            }).catch(err=>{
                console.warn('Fetch cursos failed, falling back to client-side filter', err);
                // fallback to client-side filter and ensure controls are enabled
                try{ isFetchingCourses = false; if (filterCompleted) filterCompleted.disabled = false; if (filterInProgress) filterInProgress.disabled = false; if (searchInput) searchInput.disabled = false; }catch(e){}
                applyFilters();
            });
    }

    if (searchInput) searchInput.addEventListener('input', function(){ fetchAndRenderCourses(); });
    // Status checkboxes are client-side filters (they depend on loaded progress metadata).
    // Don't re-query the server when user toggles them; instead run the local filter.
    if (filterCompleted) filterCompleted.addEventListener('change', function(){ try{ applyFilters(); }catch(e){} });
    if (filterInProgress) filterInProgress.addEventListener('change', function(){ try{ applyFilters(); }catch(e){} });
    if (filterCategory) filterCategory.addEventListener('change', function(){ fetchAndRenderCourses(); });

    // Clear filters button
    const clearBtn = document.getElementById('clearFiltersBtn');
    if (clearBtn) clearBtn.addEventListener('click', function(){
        if (searchInput) searchInput.value = '';
        if (filterCompleted) filterCompleted.checked = false;
        if (filterInProgress) filterInProgress.checked = false;
        if (filterCategory) filterCategory.value = '';
        // Refresh list from server for category/search reset, then re-apply client filters
        fetchAndRenderCourses();
        try{ setTimeout(function(){ applyFilters(); }, 350); }catch(e){}
    });
    }

    // expose init so SPA navigations can call it after replacing content
    try{ window.microcursosInit = initMicrocursos; }catch(e){}

    // run on DOMContentLoaded or immediately if already loaded
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initMicrocursos);
    else initMicrocursos();

    // Also listen to common SPA/navigation events to re-init dynamic bits
    ['turbolinks:load','pjax:success','pjax:end','htmx:afterSwap'].forEach(evt=>{
        document.addEventListener(evt, function(){ try{ initMicrocursos(); }catch(e){} });
    });
})();
