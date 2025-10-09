<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Los Toros Rojos')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/style.css') }}" rel="stylesheet">
    <style>
        /* Navegación superior: evitar que los dropdowns queden recortados y asegurar su visibilidad */
        .main-menu {
            position: relative; /* mantener en el flujo normal pero permitir hijos posicionados */
            z-index: 1030; /* por encima del contenido de la página, debajo de modales */
            overflow: visible; /* importante para que los dropdowns no queden recortados */
        }
             /* Menú desplegable: permanecer por encima del encabezado y contenido.
                 Oculto por defecto; se muestra cuando Bootstrap añade la clase .show */
        .main-menu .dropdown-menu {
            position: absolute;
            z-index: 3000;
            background: var(--color-white) !important; /* ensure white background for readability */
            color: #212529 !important;
            min-width: 220px !important;
            border-radius: 10px;
            box-shadow: 0 12px 32px rgba(11,22,40,0.18);
            overflow: visible;
            /* Oculto por defecto para evitar mostrarse sin la clase .show */
            display: none !important;
            visibility: hidden !important;
            opacity: 0;
            transform: translateY(-6px);
            transition: opacity 160ms ease, transform 160ms ease;
        }

          /* Forzar ancho responsivo que sobrescribe min-width inline.
             clamp(min, preferido, max) mantiene el dropdown usable en pantallas pequeñas
             y lo expande en viewports medianos. */
          .main-menu .dropdown-menu {
                min-width: 0 !important;
                width: clamp(180px, 40vw, 340px) !important;
                max-width: calc(100vw - 24px) !important;
                box-sizing: border-box !important;
                overflow-wrap: anywhere !important;
          }

            /* Cabecera móvil: toggle a la izquierda y área de auth a la derecha en una fila */
            @media (max-width: 767.98px) {
                .main-menu .container {
                    display: flex !important;
                    flex-direction: row !important;
                    align-items: center !important;
                    justify-content: space-between !important;
                    gap: 8px;
                    padding: 8px 12px !important;
                }
                /* la lista del menú se convierte en dropdown absoluto debajo del header cuando se expande */
                .main-menu ul {
                    position: absolute !important;
                    top: 100% !important;
                    left: 0 !important;
                    right: 0 !important;
                    background: var(--color-primary) !important;
                    padding: 12px !important;
                    display: none !important;
                    z-index: 1100 !important;
                }
                /* mantener estado colapsado oculto; la apertura la controla JS quitando 'collapsed' */
                .main-menu ul.collapsed { display: none !important; }
                .main-menu ul:not(.collapsed) { display: block !important; }

                /* Orden: toggle a la izquierda, área de auth a la derecha, luego la lista de menú */
                #menu-toggle { order: 1; display: inline-flex !important; }
                #auth-area { order: 2; margin-left: 0 !important; }
                .main-menu ul { order: 3; }

                /* Ajustes de estilo del dropdown para pantallas pequeñas (evitar overflow) */
                .main-menu .dropdown-menu {
                    right: 12px !important;
                    left: auto !important;
                    top: calc(100% + 8px) !important;
                    min-width: 180px !important;
                    max-width: calc(100vw - 24px) !important;
                    box-sizing: border-box !important;
                    word-break: break-word !important;
                    white-space: normal !important;
                    padding: 8px !important;
                }
                .main-menu .dropdown-item { font-size: 0.95rem !important; }
                .main-menu .dropdown-header strong { font-size: 1rem !important; }
                .main-menu .logout-btn { padding: 8px 14px !important; }
            }
    /* Al activarse el dropdown (Bootstrap añade .show) renderizar como tarjeta vertical */
        .main-menu .dropdown-menu.show {
            display: flex !important;
            flex-direction: column !important;
            align-items: stretch !important;
            gap: 6px;
            visibility: visible !important;
            opacity: 1 !important;
            transform: none !important;
        }
          /* Pantallas medianas: dar más espacio al dropdown para que etiquetas largas envuelvan dentro de la tarjeta */
        @media (min-width: 768px) and (max-width: 991.98px) {
            .main-menu .dropdown-menu {
                /* allow the box to be flexible but never overflow viewport */
                min-width: 260px !important;
                max-width: calc(100vw - 32px) !important;
                width: auto !important;
                right: 12px !important;
                left: auto !important;
                box-sizing: border-box !important;
                /* ensure long words/emails wrap inside the card */
                word-break: break-word !important;
                overflow-wrap: anywhere !important;
                white-space: normal !important;
                padding: 8px 10px !important;
                overflow: visible !important;
            }
            /* ítems ligeramente más pequeños en md para que quepan en una o dos líneas */
            .main-menu .dropdown-item { font-size: 0.98rem !important; font-weight:600 !important; padding-left:0.85rem !important; padding-right:0.85rem !important; }
            .main-menu .dropdown-header strong { font-size: 1.03rem !important; }
            .main-menu .logout-btn { padding: 8px 14px !important; }
        }
    /* Ajuste visual: garantizar legibilidad de items del dropdown sin importar estilos globales del header */
        .main-menu .dropdown-menu .dropdown-item,
        .main-menu .dropdown-menu a.dropdown-item {
            color: #212529 !important; /* default readable text color */
            background: transparent !important;
            display: block !important;
            width: 100% !important;
            text-align: left !important;
            padding: .5rem 0.75rem !important;
        }
        .main-menu .dropdown-menu .dropdown-item:hover {
            background: #f6f7f8 !important;
            color: #000 !important;
        }
        .main-menu .dropdown-menu .dropdown-header,
        .main-menu .dropdown-menu .dropdown-divider { color: #6c757d !important; }
    /* Estilo del botón de cerrar sesión dentro del dropdown para mayor visibilidad */
        .main-menu .dropdown-menu .btn {
            background-color: var(--color-accent) !important;
            color: var(--color-white) !important;
            border: none !important;
            display: block !important;
            margin: 0.25rem 0 !important;
        }
        /* Mantener avatar del toggle alineado */
        .main-menu .dropdown-toggle img { display:inline-block; }
        /* Make disabled links lightly greyed but not interfering with pointer events */
        .disabled-link { cursor: pointer; opacity: 0.7; }
        /* Mejorar la apariencia del dropdown */
        .main-menu .dropdown-menu {
            border-radius: 12px !important;
            padding: 8px !important;
            background-clip: padding-box !important;
        }
        .main-menu .dropdown-header small { color: #6c757d; }
        .main-menu .dropdown-item { font-weight:700; padding-left: 1rem !important; padding-right:1rem !important; }
    /* Botón de logout más agradable: tarjeta más pequeña, compacta y con sombra sutil */
        .main-menu .logout-wrap { padding: 0.5rem 0.5rem 0 !important; }
        .main-menu .logout-btn {
            border-radius: 10px !important;
            padding: 10px 18px !important;
            box-shadow: 0 8px 18px rgba(11,22,40,0.12) !important;
            font-weight: 600 !important;
        }
        /* Posicionar dropdown justo debajo del header (evitar separaciones grandes) */
        .main-menu .dropdown-menu {
            top: calc(100% + 6px) !important;
            right: 12px !important;
        }
        /* Asegurar que los items del dropdown estén apilados verticalmente y compactos */
        .main-menu .dropdown-menu.show { gap: 6px; padding-bottom: 8px !important; }
    </style>
    <link rel="icon" type="image/png" href="{{ asset('images/Logo General.png') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body>
    <nav class="main-menu">
        <div class="container d-flex justify-content-between align-items-center py-2">
            <!-- Mobile menu toggle -->
            <button id="menu-toggle" class="menu-toggle d-none" aria-label="Abrir menú">
                <span class="hamburger" aria-hidden="true">☰</span>
            </button>
            <ul class="mb-0 list-unstyled d-flex gap-3 mb-0">
                <li><a href="{{ url('/') }}" class="@if(request()->is('/')) active-menu @endif">Inicio</a></li>
                <li>
                    @if(auth()->check())
                        <a href="{{ url('/microcursos') }}" class="@if(request()->is('microcursos*')) active-menu @endif">Mis Cursos</a>
                    @else
                        <a href="#" class="text-muted disabled-link" data-target-url="{{ url('/microcursos') }}">Mis Cursos</a>
                    @endif
                </li>
                @if(auth()->check())
                    <li><a href="{{ url('/descargas/administrar') }}" class="@if(request()->is('descargas/administrar')) active-menu @endif">Mis Descargas</a></li>
                @endif
                <li><a href="{{ url('/directorio') }}" class="@if(request()->is('directorio*')) active-menu @endif">Directorio</a></li>
                @if(auth()->check() && auth()->user()->rol === 'instructor')
                    <li><a href="{{ url('/instructor/dashboard') }}" class="@if(request()->is('instructor*')) active-menu @endif">Instructor</a></li>
                @endif
                @if(auth()->check() && auth()->user()->rol === 'admin')
                    <li class="dropdown">
                        <a href="#" class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">Admin</a>
                        <ul class="dropdown-menu p-2">
                            <li><a class="dropdown-item @if(request()->is('admin/cursos*')) active-menu @endif" href="{{ url('/admin/cursos') }}">Cursos</a></li>
                            <li><a class="dropdown-item @if(request()->is('admin/cleanup-pwa')) active-menu @endif" href="{{ url('/admin/cleanup-pwa') }}">Limpieza PWA</a></li>
                            <li><a class="dropdown-item @if(request()->is('admin/assign-instructors*')) active-menu @endif" href="{{ url('/admin/assign-instructors') }}">Asignar instructores</a></li>
                        </ul>
                    </li>
                @endif
                <!-- Contacto removed from menu - unified footer now contains contact information -->
            </ul>
            <div id="auth-area" class="ms-auto">
                @if(auth()->check())
                    <div class="dropdown">
                        <a class="d-flex align-items-center text-decoration-none dropdown-toggle" href="#" id="userMenuLink" data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="me-2">
                                <div class="user-name" style="font-weight:700; color:#fff;">{{ auth()->user()->nombre ?? auth()->user()->email }}</div>
                            </div>
                            <img src="{{ asset('images/Logo General.png') }}" alt="avatar" style="height:36px; width:36px; object-fit:cover; border-radius:6px;">
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end p-2" aria-labelledby="userMenuLink">
                            <li class="px-3 py-2 dropdown-header">
                                <strong style="display:block; font-size:1.05rem;">Hola, {{ auth()->user()->nombre ?? auth()->user()->email }}</strong>
                                <small class="text-muted">{{ auth()->user()->correo ?? auth()->user()->email }}</small>
                            </li>
                            <li><a class="dropdown-item" href="{{ url('/perfil') }}">Editar información <br> personal</a></li>
                            <li><a class="dropdown-item" href="{{ url('/password/change') }}">Cambiar contraseña</a></li>
                            @if(!empty(auth()->user()->email_verified_at) === false)
                                <li><a class="dropdown-item text-warning" href="{{ route('register.verify.pending') }}"><strong>Verifica tu cuenta</strong><br><small class="text-muted">Reenvía el correo de verificación</small></a></li>
                            @endif
                            <li><hr class="dropdown-divider"></li>
                            <li class="px-3 py-2 logout-wrap">
                                <form method="POST" action="{{ route('logout') }}" style="margin:0;">
                                    @csrf
                                    <button class="btn btn-danger w-100 logout-btn">Cerrar sesión</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                @else
                    <a href="{{ url('/login') }}" class="btn auth-btn" id="loginButton">
                        <i class="bi bi-person-circle me-1"></i> Iniciar sesión
                    </a>
                @endif
            </div>
        </div>
    </nav>

    <!-- Offline/Online alert container -->
    <div id="network-alert" aria-live="polite" style="position:fixed;left:12px;right:12px;top:76px;z-index:4000;display:none;">
        <!-- JS will populate this element with alerts -->
    </div>

    <main class="container my-5">
        @yield('content')
    </main>

    <!-- Unified contact footer (Los Toros Rojos) -->
    <footer id="site-footer" class="site-footer">
        <div class="container py-3" style="background-color:var(--color-dark); color:var(--color-light); min-height:18vh;">
            <div class="row align-items-center" style="font-size:0.95rem;">
                <div class="col-md-6 d-flex gap-3 align-items-center">
                    <img src="{{ asset('images/Logo General.png') }}" alt="Los Toros Rojos / Logo" style="height:64px; width:64px; object-fit:cover; border-radius:8px;">
                    <div>
                        <h5 class="mb-1" style="color:var(--color-light); font-size:1.1rem;">Los Toros Rojos</h5>
                        <p class="mb-0" style="line-height:1.1;">Av. Insurgentes Sur 704, Col. Hipódromo — Benito Juárez, Ciudad de México, CDMX 06100</p>
                        <p class="mb-0" style="line-height:1.1;">Tel: +52 (55) 1234 5678 — Email: <a href="mailto:contacto@torosrojos.com" class="text-white">contacto@torosrojos.com</a></p>
                    </div>
                </div>
                <div class="col-md-6 text-md-end">
                    <h6 style="color:#9fe7ef; font-size:1rem; margin-bottom:0.5rem; text-align:center;">Administración & Soporte</h6>
                    <p class="mb-0" style="line-height:1.1;">Oficinas centrales — Av. Reforma 222, Piso 3, Juárez, Ciudad de México, CDMX<br>
                    Tel: +52 (55) 7654 3210</p>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-12 text-center">
                    <small class="d-block footer-copyright">&copy; {{ date('Y') }} Los Toros Rojos. Todos los derechos reservados.</small>
                </div>
            </div>
        </div>
    </footer>

    <!-- footer is static; removed dynamic show/hide script to keep it part of the normal page flow -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Expose current user id to frontend for JS actions (null if guest)
        window.CURRENT_USER = @json(auth()->check() ? auth()->id() : null);
        // Expose current user role so frontend can decide whether to show edit UI
        // NOTE: Authorization must always be enforced on the server. This flag is
        // only for improving the UI/UX (hiding edit controls for non-admins).
        window.CURRENT_USER_ROLE = @json(auth()->check() ? auth()->user()->rol : null);
    </script>
    <script>
        // Refuerzo: si estamos en la página de administrar descargas y el navegador está offline,
        // bloquear cualquier intento de navegar fuera de esta vista desde cualquier enlace global.
        (function(){
            try{
                const path = window.location.pathname || '';
                if(path.indexOf('/descargas/administrar') !== -1 && !navigator.onLine){
                    document.querySelectorAll('a').forEach(function(a){
                        const href = a.getAttribute('href') || a.getAttribute('data-target-url') || '';
                        if(href.indexOf('/descargas') === -1){
                            a.classList.add('disabled-link');
                            a.setAttribute('aria-disabled','true');
                            if(!a.__offlineBlockAttachedGlobal){
                                a.addEventListener('click', function(e){ e.preventDefault(); e.stopPropagation(); return false; });
                                a.__offlineBlockAttachedGlobal = true;
                            }
                        }
                    });
                }
            }catch(e){}
        })();
    </script>
    <script>
        /* Limpieza opt-in de PWA / Service Workers
           Definimos una función global `cleanupPWA()` que desregistrará service workers
           y borrará caches relacionados. NO se ejecuta automáticamente. Para activarla:
             - establecer `window.CLEANUP_PWA = true` en la consola antes de recargar, o
             - visitar la URL con `?cleanup_pwa=1` cuando se acceda como administrador.
           Esto evita que la limpieza se ejecute accidentalmente para usuarios finales.
        */

        (function(){
            function cleanupPWA(){
                try{
                    console.info('Inicio de limpieza PWA (opt-in)');

                    // Eliminar enlaces a manifiesto para que el navegador no trate la página como PWA
                    document.querySelectorAll('link[rel="manifest"]').forEach(function(n){ try{ n.remove(); }catch(e){} });

                    // Desregistrar service workers activos
                    if (('serviceWorker' in navigator) && navigator.serviceWorker.getRegistrations){
                        navigator.serviceWorker.getRegistrations().then(function(regs){
                            regs.forEach(function(r){
                                try{
                                    r.unregister().then(function(ok){ console.log('SW desregistrado:', ok, r.scope); }).catch(function(e){ console.warn('Error unregistering SW', e); });
                                }catch(e){ console.warn('Error al intentar desregistrar SW', e); }
                            });
                        }).catch(function(e){ console.warn('No fue posible enumerar service workers', e); });
                    }

                    // Borrar caches cuyo nombre parezca relacionado con el proyecto (mejor esfuerzo)
                    if (window.caches && typeof caches.keys === 'function'){
                        caches.keys().then(function(keys){
                            keys.forEach(function(k){
                                try{
                                    if (!k) return;
                                    var kn = k.toString().toLowerCase();
                                    if (kn.includes('microcursos') || kn.includes('micro') || kn.includes('pwa')){
                                        caches.delete(k).then(function(del){ console.log('Cache eliminada:', k, del); }).catch(function(e){ console.warn('Error eliminando cache', k, e); });
                                    }
                                }catch(e){ console.warn('Error procesando cache key', e); }
                            });
                        }).catch(function(e){ console.warn('No fue posible listar caches', e); });
                    }
                }catch(e){ console.warn('La limpieza PWA falló', e); }
            }

            // Exponer función global para ejecución manual (ej: desde consola o página admin)
            try{ window.cleanupPWA = cleanupPWA; }catch(e){}

            // Ejecutar automáticamente sólo si se activa explícitamente o si la URL contiene ?cleanup_pwa=1
            var params = null;
            try{ params = new URLSearchParams(window.location.search); }catch(e){ params = null; }

            var urlFlag = params && params.get && params.get('cleanup_pwa') === '1';
            var isAdminAndUrlFlag = urlFlag && (window.CURRENT_USER_ROLE === 'admin');

            if (window.CLEANUP_PWA === true) {
                // activado explicitamente por el administrador (por ejemplo desde consola antes de recargar)
                cleanupPWA();
            } else if (isAdminAndUrlFlag) {
                // activado por acceso directo protegido por rol admin
                cleanupPWA();
            } else {
                // No ejecutar por defecto. La función cleanupPWA() está disponible para invocarla manualmente.
            }
        })();
    </script>
    <script>
    // Asegura que cualquier dropdown de Bootstrap abierto en el header se cierre al hacer clic fuera
        (function(){
            // Using Bootstrap's dropdown API
            const header = document.querySelector('.main-menu');
            if(!header) return;

                // Close dropdown when clicking outside of it
                document.addEventListener('click', function(e){
                    // If the click is on the dropdown toggle itself, and it's not offline-disabled, do nothing here and let Bootstrap handle it
                        if (e.target.closest && e.target.closest('[data-bs-toggle="dropdown"]')){
                            const clickedToggle = e.target.closest('[data-bs-toggle="dropdown"]');
                            if(clickedToggle && clickedToggle.classList.contains('offline-dropdown-disabled')){
                                // If it's disabled for offline, prevent Bootstrap from toggling it
                                e.preventDefault(); e.stopPropagation();
                                return;
                            }
                            return;
                        }

                    const openDropdown = header.querySelector('.dropdown-menu.show');
                    if(!openDropdown) return;
                    if(openDropdown.contains(e.target)) return; // click inside dropdown
                    // find the toggle inside header that controls the open dropdown
                    const toggle = header.querySelector('[data-bs-toggle="dropdown"]');
                    try {
                        if(toggle){
                            const bsToggle = bootstrap.Dropdown.getInstance(toggle) || new bootstrap.Dropdown(toggle);
                            bsToggle.hide();
                        } else {
                            // fallback: remove 'show' classes
                            openDropdown.classList.remove('show');
                            // also remove .show from parent .dropdown if present
                            const parent = openDropdown.closest('.dropdown');
                            if(parent) parent.classList.remove('show');
                        }
                    } catch(err){
                        openDropdown.classList.remove('show');
                        const parent = openDropdown.closest('.dropdown');
                        if(parent) parent.classList.remove('show');
                    }
                }, { passive: true });

            // Also ensure dropdown menus are not clipped by parent containers when the page is scrolled/resized
            function refreshDropdownPositions(){
                document.querySelectorAll('.main-menu .dropdown-menu').forEach(dm=>{
                    dm.style.willChange = 'transform';
                });
            }
            window.addEventListener('resize', refreshDropdownPositions);
            window.addEventListener('scroll', refreshDropdownPositions, { passive: true });
            // On initial load ensure dropdowns are closed (protect against server-rendered .show)
            document.addEventListener('DOMContentLoaded', function(){
                header.querySelectorAll('.dropdown, .dropdown-menu').forEach(el=>{
                    el.classList.remove('show');
                    if(el.getAttribute && el.getAttribute('aria-expanded') === 'true') el.setAttribute('aria-expanded','false');
                });
            });
        })();
    </script>
        <!-- Login required modal -->
        <div class="modal fade" id="loginRequiredModal" tabindex="-1" aria-labelledby="loginRequiredModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="loginRequiredModalLabel">Acceso requerido</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        Necesitas iniciar sesión para acceder a los microcursos.
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <a href="{{ url('/login') }}" class="btn btn-primary" id="modalLoginBtn">Ir a iniciar sesión</a>
                    </div>
                </div>
                </div>
            </div>

                <script>
                    // Accessibility: ensure focus is restored when Bootstrap modals are hidden
                    // and blur any focused element left inside a hidden modal to avoid
                    // "Blocked aria-hidden on an element because its descendant retained focus" warnings.
                    (function(){
                        // Store opener on show, restore focus on hidden
                        document.addEventListener('show.bs.modal', function(e){
                            try{
                                // save the element that had focus before modal opened
                                e.target.__opener = document.activeElement;
                            }catch(err){}
                        }, true);

                        document.addEventListener('hidden.bs.modal', function(e){
                            try{
                                // if something inside the modal still has focus, blur it
                                var focused = e.target.querySelector && e.target.querySelector(':focus');
                                if (focused && typeof focused.blur === 'function'){
                                    try{ focused.blur(); }catch(err){}
                                }

                                // restore focus to the element that opened the modal, if available
                                var opener = e.target.__opener;
                                if (opener && typeof opener.focus === 'function'){
                                    try{ opener.focus(); }catch(err){}
                                } else {
                                    // fallback: move focus to a safe, focusable header element or body
                                    var safe = document.querySelector('.main-menu [href]') || document.querySelector('.main-menu') || document.body;
                                    if (safe && typeof safe.focus === 'function'){
                                        if (!safe.hasAttribute('tabindex')) safe.setAttribute('tabindex','-1');
                                        try{ safe.focus(); }catch(err){}
                                    }
                                }
                            }catch(err){}
                        }, true);
                    })();
                </script>

                <script>
                    (function(){
                        const MOBILE_MAX = 767.98;
            const header = document.querySelector('.main-menu');
            const ul = header ? header.querySelector('ul') : null;
            const toggle = document.getElementById('menu-toggle');
            if(!header || !ul) return;

            let lastScroll = window.scrollY || 0;

            function isMobile(){ return window.innerWidth <= MOBILE_MAX; }

            function closeMenu(){
                ul.classList.add('collapsed');
                header.classList.remove('menu-open');
                ul.setAttribute('aria-hidden','true');
                if(toggle) toggle.setAttribute('aria-expanded','false');
            }
            function openMenu(){
                ul.classList.remove('collapsed');
                header.classList.add('menu-open');
                ul.setAttribute('aria-hidden','false');
                if(toggle) toggle.setAttribute('aria-expanded','true');
            }

            function onScroll(){
                const current = window.scrollY || 0;
                if(isMobile()){
                    if(header.classList.contains('menu-open')) closeMenu();
                    lastScroll = current;
                    return;
                }
                if(current > lastScroll && current > 40){
                    header.classList.add('hidden');
                } else {
                    header.classList.remove('hidden');
                }
                lastScroll = current;
            }

            if(toggle){
                toggle.setAttribute('aria-controls','main-menu-list');
                ul.setAttribute('id','main-menu-list');
                if(!toggle.hasAttribute('aria-expanded')) toggle.setAttribute('aria-expanded','false');
                toggle.addEventListener('click', function(){
                    if(ul.classList.contains('collapsed')) openMenu(); else closeMenu();
                    header.classList.remove('hidden');
                });
            }

            function onResize(){
                if(isMobile()){
                    if(toggle) toggle.classList.remove('d-none');
                    if(!ul.classList.contains('collapsed')) ul.classList.add('collapsed');
                } else {
                    if(toggle) toggle.classList.add('d-none');
                    ul.classList.remove('collapsed');
                    header.classList.remove('menu-open');
                    header.classList.remove('hidden');
                    ul.setAttribute('aria-hidden','false');
                }
            }

            onResize();
            onScroll();

            window.addEventListener('scroll', onScroll, { passive: true });
            window.addEventListener('resize', onResize);
        })();
    </script>
    <script>
        // Intercept clicks to microcursos when user is guest and show modal
        (function(){
            const isAuthenticated = !!window.CURRENT_USER;
            if (isAuthenticated) return; // nothing to do

            // Links in navbar and other links that were changed have data-target-url
            document.querySelectorAll('a[data-target-url]').forEach(a=>{
                a.addEventListener('click', function(e){
                    e.preventDefault();
                    const target = a.getAttribute('data-target-url') || '/microcursos';
                    // show bootstrap modal
                    const modalEl = document.getElementById('loginRequiredModal');
                    if (modalEl){
                        const modal = new bootstrap.Modal(modalEl);
                        // update login button to redirect back after login
                        const loginBtn = document.getElementById('modalLoginBtn');
                        if (loginBtn) {
                            // append redirect param so after login user returns
                            const url = new URL(loginBtn.getAttribute('href'), window.location.origin);
                            url.searchParams.set('redirect_to', target);
                            loginBtn.setAttribute('href', url.toString());
                        }
                        modal.show();
                    } else {
                        window.location.href = '/login?redirect_to=' + encodeURIComponent(target);
                    }
                });
            });

            // Also intercept any plain links to /microcursos (in case exists elsewhere)
            document.querySelectorAll('a[href="/microcursos"]').forEach(a=>{
                a.addEventListener('click', function(e){ e.preventDefault(); a.click(); });
            });
        })();
    </script>

    @stack('scripts')
    <script>
        (function(){
            // Helpers para mostrar alertas en el layout
            const alertContainer = document.getElementById('network-alert');
            function createAlert(message, type, persistent){
                // type: 'warning' (orange) or 'success' (green)
                const el = document.createElement('div');
                el.setAttribute('role','alert');
                el.className = 'alert';
                el.style.margin = '0 auto';
                el.style.maxWidth = '980px';
                el.style.borderRadius = '8px';
                el.style.boxShadow = '0 6px 18px rgba(11,22,40,0.12)';
                el.style.display = 'flex';
                el.style.justifyContent = 'space-between';
                el.style.alignItems = 'center';
                el.style.padding = '10px 14px';
                el.style.color = '#fff';
                el.style.fontWeight = '600';

                if(type === 'warning'){
                    el.style.background = 'var(--color-warning)';
                    el.style.border = '1px solid rgba(0,0,0,0.08)';
                } else {
                    el.style.background = 'var(--color-success)';
                    el.style.border = '1px solid rgba(0,0,0,0.06)';
                }

                const span = document.createElement('div');
                // message may contain simple HTML (internal use only)
                span.innerHTML = message;
                el.appendChild(span);

                const actions = document.createElement('div');
                actions.style.display = 'flex';
                actions.style.gap = '8px';

                // Add refresh button for reconnect messages
                if(!persistent && type === 'success'){
                    const btn = document.createElement('button');
                    btn.className = 'btn btn-sm btn-light';
                    btn.style.fontWeight = 700;
                    btn.innerText = 'Actualizar';
                    btn.addEventListener('click', function(){ window.location.reload(); });
                    actions.appendChild(btn);
                }

                el.appendChild(actions);

                // Replace existing transient alert
                if(!persistent){
                    alertContainer.innerHTML = '';
                    alertContainer.appendChild(el);
                    alertContainer.style.display = 'block';
                } else {
                    // persistent: keep as only child
                    alertContainer.innerHTML = '';
                    alertContainer.appendChild(el);
                    alertContainer.style.display = 'block';
                }

                return el;
            }

            function hideAlert(){
                if(!alertContainer) return;
                alertContainer.innerHTML = '';
                alertContainer.style.display = 'none';
            }

            // IndexedDB tiny wrapper with fallback to localStorage
            const DB_NAME = 'microcursos_offline_v1';
            const DB_VERSION = 1;
            let db = null;

            function openDb(){
                return new Promise((resolve, reject)=>{
                    if(!('indexedDB' in window)) return resolve(null);
                    const req = indexedDB.open(DB_NAME, DB_VERSION);
                    req.onupgradeneeded = function(e){
                        const idb = e.target.result;
                        if(!idb.objectStoreNames.contains('downloads')) idb.createObjectStore('downloads',{ keyPath: 'id' });
                        if(!idb.objectStoreNames.contains('progress')) idb.createObjectStore('progress',{ keyPath: 'key' });
                    };
                    req.onsuccess = function(e){ db = e.target.result; resolve(db); };
                    req.onerror = function(e){ console.warn('IDB open error', e); resolve(null); };
                });
            }

            async function idbPut(store, value){
                if(!db) return fallbackPut(store, value);
                return new Promise((resolve, reject)=>{
                    const tx = db.transaction(store,'readwrite');
                    const st = tx.objectStore(store);
                    const req = st.put(value);
                    req.onsuccess = ()=> resolve(true);
                    req.onerror = (e)=> { console.warn('idbPut error', e); resolve(false); };
                });
            }

            async function idbGetAll(store){
                if(!db) return fallbackGetAll(store);
                return new Promise((resolve, reject)=>{
                    const tx = db.transaction(store,'readonly');
                    const st = tx.objectStore(store);
                    const req = st.getAll();
                    req.onsuccess = ()=> resolve(req.result || []);
                    req.onerror = ()=> resolve([]);
                });
            }

            function fallbackPut(store, value){
                try{
                    const key = store + '_' + (value.key || value.id || JSON.stringify(value).slice(0,40));
                    localStorage.setItem(key, JSON.stringify(value));
                    return Promise.resolve(true);
                }catch(e){ return Promise.resolve(false); }
            }

            function fallbackGetAll(store){
                try{
                    const out = [];
                    for(let i=0;i<localStorage.length;i++){
                        const k = localStorage.key(i);
                        if(!k) continue;
                        if(k.indexOf(store + '_') === 0){
                            try{ out.push(JSON.parse(localStorage.getItem(k))); }catch(e){}
                        }
                    }
                    return Promise.resolve(out);
                }catch(e){ return Promise.resolve([]); }
            }

            // Read single item from IDB or localStorage fallback
            async function idbGet(store, key){
                if(!db) return fallbackGet(store, key);
                return new Promise((resolve)=>{
                    try{
                        const tx = db.transaction(store,'readonly');
                        const st = tx.objectStore(store);
                        const req = st.get(key);
                        req.onsuccess = ()=> resolve(req.result || null);
                        req.onerror = ()=> resolve(null);
                    }catch(e){ resolve(null); }
                });
            }

            function fallbackGet(store, key){
                try{
                    const k = store + '_' + (key || '');
                    if(localStorage.getItem(k) !== null) return Promise.resolve(JSON.parse(localStorage.getItem(k)));
                    for(let i=0;i<localStorage.length;i++){
                        const lk = localStorage.key(i);
                        if(!lk) continue;
                        if(lk.indexOf(store + '_') === 0){
                            try{ const v = JSON.parse(localStorage.getItem(lk)); if(v && (String(v.id) === String(key) || String(v.key) === String(key))) return Promise.resolve(v); }catch(e){}
                        }
                    }
                    return Promise.resolve(null);
                }catch(e){ return Promise.resolve(null); }
            }

                async function idbDelete(store, key){
                    if(!db) return fallbackDelete(store, key);
                    return new Promise((resolve)=>{
                        try{
                            const tx = db.transaction(store,'readwrite');
                            const st = tx.objectStore(store);
                            const req = st.delete(key);
                            req.onsuccess = ()=> resolve(true);
                            req.onerror = ()=> resolve(false);
                        }catch(e){ resolve(false); }
                    });
                }

                function fallbackDelete(store, key){
                    try{
                        const k = store + '_' + (key || '');
                        if(localStorage.getItem(k) !== null){ localStorage.removeItem(k); return Promise.resolve(true); }
                        for(let i=0;i<localStorage.length;i++){
                            const lk = localStorage.key(i);
                            if(!lk) continue;
                            if(lk.indexOf(store + '_') === 0){
                                try{ const v = JSON.parse(localStorage.getItem(lk)); if(v && (String(v.id) === String(key) || String(v.key) === String(key))){ localStorage.removeItem(lk); return Promise.resolve(true); } }catch(e){}
                            }
                        }
                        return Promise.resolve(false);
                    }catch(e){ return Promise.resolve(false); }
                }

            // Save a downloaded course
            async function saveDownloadedCourse(course){
                // course must have id and full payload
                const payload = Object.assign({}, course);
                payload.id = payload.id || payload.curso_id || ('' + Date.now());
                return idbPut('downloads', payload);
            }

            async function getDownloadedCourses(){ return idbGetAll('downloads'); }

            // Save progress locally for later sync
            async function saveProgress(courseId, lessonId, progress){
                const user = window.CURRENT_USER || 'guest';
                const key = `${user}:${courseId}:${lessonId}`;
                const payload = { key, user, courseId, lessonId, progress, updated_at: (new Date()).toISOString(), synced: false };
                return idbPut('progress', payload);
            }

            async function getPendingProgress(){
                const all = await idbGetAll('progress');
                return all.filter(p=>!p.synced);
            }

            // Attempt sync to server; expects an endpoint to accept bulk progress.
            async function syncProgress(){
                if(!navigator.onLine) return false;
                const pending = await getPendingProgress();
                if(!pending.length) return true;
                try{
                    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    const res = await fetch('/offline/sync-progress', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
                        body: JSON.stringify({ progresses: pending })
                    });
                    if(res.ok){
                        // mark all as synced
                        for(const p of pending){ p.synced = true; await idbPut('progress', p); }
                        return true;
                    }
                    return false;
                }catch(e){ console.warn('syncProgress failed', e); return false; }
            }

            // UI adjustments for offline state
            function adjustMenuForOffline(isOffline){
                // Enhance offline behavior:
                // - keep /descargas accessible
                // - disable links that require network (directorio, microcursos, admin, instructor)
                // - prevent opening Admin/Instructor dropdowns while offline to avoid navigating to a server-rendered session page
                document.querySelectorAll('.main-menu a, .main-menu [data-bs-toggle="dropdown"]').forEach(el=>{
                    try{
                        const isToggle = el.getAttribute && el.getAttribute('data-bs-toggle') === 'dropdown';
                        const href = el.getAttribute ? (el.getAttribute('href') || el.getAttribute('data-target-url') || '') : '';
                        const isDownloadsLink = href.indexOf('/descargas') !== -1;
                        const isLoginLink = (el.id === 'loginButton' || href.indexOf('/login') !== -1);
                        const isPotentiallyOnline = (href.indexOf('/directorio') !== -1 || href.indexOf('/microcursos') !== -1 || href.indexOf('/admin') !== -1 || href.indexOf('/instructor') !== -1);

                        if(isOffline){
                            // never disable access to downloads management
                            if(!isDownloadsLink && isPotentiallyOnline){
                                el.classList.add('disabled-link');
                                el.setAttribute('aria-disabled','true');
                                if(!el.__offlineDisabledAttached){ el.addEventListener('click', preventIfDisabled); el.__offlineDisabledAttached = true; }
                            }

                            // If guest, disable login links/buttons (they don't work offline)
                            if(!window.CURRENT_USER && isLoginLink){
                                el.classList.add('disabled-link');
                                el.setAttribute('aria-disabled','true');
                                if(!el.__offlineDisabledAttached){ el.addEventListener('click', preventIfDisabled); el.__offlineDisabledAttached = true; }
                            }

                            // specifically block dropdown toggles (Admin / user menu) from opening when offline
                            if(isToggle){
                                // mark toggle so our global click handler knows to block it
                                el.classList.add('offline-dropdown-disabled');
                                if(!el.__offlineToggleAttached){
                                    el.addEventListener('click', function(e){
                                        if(el.classList.contains('offline-dropdown-disabled')){
                                            e.preventDefault(); e.stopPropagation();
                                            // show a small persistent alert explaining why
                                            try{ createAlert('Esta funcionalidad (Admin/Instructor) no está disponible sin conexión. Accede a <a href="/descargas/administrar" style="color:#fff; text-decoration:underline; font-weight:800;">Mis Descargas</a>.', 'warning', true); }catch(_){ }
                                            return false;
                                        }
                                    });
                                    el.__offlineToggleAttached = true;
                                }
                            }
                        } else {
                            // restore previously disabled elements
                            if(el.classList.contains('disabled-link')){
                                el.classList.remove('disabled-link');
                                el.removeAttribute('aria-disabled');
                                if(el.__offlineDisabledAttached){ try{ el.removeEventListener('click', preventIfDisabled); }catch(e){} el.__offlineDisabledAttached = false; }
                            }
                            if(isToggle && el.classList.contains('offline-dropdown-disabled')){
                                el.classList.remove('offline-dropdown-disabled');
                                // leave the event listener attached as it checks the class at runtime
                            }
                        }
                    }catch(e){ /* ignore per-element errors */ }
                });

                // Close any open admin/instructor dropdowns when going offline to avoid accidental navigation
                if(isOffline){
                    try{
                        document.querySelectorAll('.main-menu .dropdown-menu.show').forEach(dm=>{
                            dm.classList.remove('show');
                            const parent = dm.closest('.dropdown'); if(parent) parent.classList.remove('show');
                        });
                    }catch(e){}
                }
            }

            function preventIfDisabled(e){
                if(e.currentTarget && e.currentTarget.classList.contains('disabled-link')){
                    e.preventDefault();
                    // show login required modal if trying to go to microcursos without session
                    if(e.currentTarget.getAttribute('data-target-url')){
                        const modalEl = document.getElementById('loginRequiredModal');
                        if(modalEl){ const modal = new bootstrap.Modal(modalEl); modal.show(); }
                    }
                    return false;
                }
            }

            // Behavior on connectivity changes
            let persistentOffline = false;

            function handleWentOfflineTransient(){
                // user lost connection while using app
                createAlert('Conexión perdida. Estás visualizando una vista cargada previamente. Si recargas la página entrarás en modo sin conexión.', 'warning', false);
                // auto-hide after 10s
                setTimeout(()=>{ if(!persistentOffline) hideAlert(); }, 10000);
            }

            function handleStartOfflinePersistent(){
                persistentOffline = true;
                if(window.CURRENT_USER){
                    // include a small link to administrar descargas
                    const msg = 'Estás en modo sin conexión. Puedes acceder a tus cursos descargados. <a href="/descargas/administrar" style="color:#fff; text-decoration:underline; font-weight:800;">Ir a Mis Descargas</a>';
                    createAlert(msg, 'warning', true);
                } else {
                    createAlert('Estás en modo sin conexión. Es necesario iniciar sesión para poder usar cursos offline.', 'warning', true);
                }
            }

            async function handleWentOnline(){
                persistentOffline = false;
                const el = createAlert('Conexión restaurada. Sincronizando datos...', 'success', false);
                // try to sync
                const ok = await syncProgress();
                if(ok){
                    // replace message to say todo sincronizado
                    el.firstChild.innerHTML = 'Conexión restaurada. Los datos se sincronizaron correctamente. Actualiza para volver al modo en línea.';
                } else {
                    el.firstChild.innerHTML = 'Conexión restaurada. No fue posible sincronizar ahora — inténtalo de nuevo o actualiza.';
                }
                // keep visible for 3s to allow user to click Actualizar
                setTimeout(()=>{ hideAlert(); }, 8000);
                adjustMenuForOffline(false);
            }

            // On load
            document.addEventListener('DOMContentLoaded', async function(){
                await openDb();
                // Signal that the OfflinePWA runtime is available after IDB is ready
                try{ window.dispatchEvent(new Event('offlinepwa:ready')); }catch(e){}
                const online = navigator.onLine;
                if(!online){
                    handleStartOfflinePersistent();
                    adjustMenuForOffline(true);
                }
            });

            // listen for events
            window.addEventListener('offline', function(){ adjustMenuForOffline(true); handleWentOfflineTransient(); });
            window.addEventListener('online', function(){ handleWentOnline(); });

            // Expose API for other scripts/pages
            window.OfflinePWA = {
                saveDownloadedCourse,
                getDownloadedCourses,
                deleteDownloadedCourse: async function(id){
                    if(!id) return false;
                    try{
                        // Try direct get first (works when key types match)
                        let meta = await idbGet('downloads', id);
                        // If not found, try to scan all entries and match by id property loosely
                        if(!meta){
                            const all = await idbGetAll('downloads');
                            for(const entry of (all||[])){
                                if(!entry) continue;
                                // Match numerics/strings flexibly
                                if(String(entry.id) === String(id) || String(entry.key || '') === String(id)){
                                    meta = entry; break;
                                }
                            }
                        }

                        const resources = (meta && meta.resources) ? meta.resources : [];

                        // Ask SW to delete cached resources (best-effort)
                        if(navigator.serviceWorker && navigator.serviceWorker.controller && Array.isArray(resources) && resources.length){
                            try{ navigator.serviceWorker.controller.postMessage({ action: 'delete-resources', resources }); }catch(e){}
                        }

                        // Determine the actual key to delete in IDB
                        let deleteKey = id;
                        if(meta && typeof meta.id !== 'undefined') deleteKey = meta.id;
                        if(meta && typeof meta.key !== 'undefined') deleteKey = meta.key;

                        const ok = await idbDelete('downloads', deleteKey);

                        try{ window.dispatchEvent(new CustomEvent('offlinepwa:course-deleted', { detail: { id: String(id) } })); }catch(e){}

                        return ok;
                    }catch(e){ console.warn('deleteDownloadedCourse failed', e); return false; }
                },
                saveProgress,
                syncProgress
            };
            // offlinepwa:ready is dispatched after IndexedDB opens (see DOMContentLoaded handler)
        })();
        // Registrar service worker (no intrusivo) si el navegador lo soporta
        if ('serviceWorker' in navigator) {
            try {
                navigator.serviceWorker.register('/sw.js').then(function(reg){
                    console.log('Service worker registrado en scope:', reg.scope);
                }).catch(function(err){ console.warn('Registro SW falló', err); });
            } catch(e) { console.warn('Registro SW no soportado', e); }
        }
        
        // Detect user changes (login/logout or switch) and ensure the service worker
        // doesn't keep serving content from a previous user (the "usuario fantasma").
        // Strategy:
        //  - store the lastKnown user id in localStorage ('microcursos_sw_user')
        //  - on page load, if it differs from window.CURRENT_USER, unregister SW and
        //    clear related caches (best-effort). If a new user is present, re-register SW.
        (function(){
            const STORAGE_KEY = 'microcursos_sw_user';

            async function clearRelatedCaches(){
                try{
                    if(window.caches && typeof caches.keys === 'function'){
                        const keys = await caches.keys();
                        for(const k of keys){
                            try{
                                if(!k) continue;
                                const kn = String(k).toLowerCase();
                                if(kn.includes('microcursos') || kn.includes('micro') || kn.includes('pwa')){
                                    await caches.delete(k).catch(()=>{});
                                }
                            }catch(e){}
                        }
                    }
                }catch(e){}
            }

            async function unregisterAllSW(){
                try{
                    if('serviceWorker' in navigator && navigator.serviceWorker.getRegistrations){
                        const regs = await navigator.serviceWorker.getRegistrations();
                        for(const r of (regs||[])){
                            try{ await r.unregister(); }catch(e){}
                        }
                    }
                }catch(e){}
            }

            async function unregisterAndClear(){
                try{
                    await unregisterAllSW();
                    await clearRelatedCaches();
                }catch(e){ console.warn('unregisterAndClear failed', e); }
            }

            // Ask the active service worker to self-unregister via postMessage. Waits up to timeoutMs ms for confirmation.
            async function askSWToSelfUnregister(timeoutMs = 800){
                return new Promise(async (resolve)=>{
                    if(!('serviceWorker' in navigator) || !navigator.serviceWorker.controller){ return resolve(false); }
                    let resolved = false;
                    function onMsg(e){ try{ const d = e.data || {}; if(d && (d.type === 'sw-unregistered' || d.type === 'sw-unregister-started')){ resolved = true; navigator.serviceWorker.removeEventListener('message', onMsg); resolve(true); } }catch(_){}}
                    navigator.serviceWorker.addEventListener('message', onMsg);
                    try{
                        navigator.serviceWorker.controller.postMessage({ action: 'self-unregister' });
                    }catch(e){ navigator.serviceWorker.removeEventListener('message', onMsg); return resolve(false); }
                    // timeout fallback
                    setTimeout(function(){ if(!resolved){ try{ navigator.serviceWorker.removeEventListener('message', onMsg); }catch(_){} resolve(false); } }, timeoutMs);
                });
            }

            // Small helper to show a quick toast/alert in #network-alert
            function showTransientToast(message, type='success', duration=5000){
                try{
                    const container = document.getElementById('network-alert');
                    if(!container) return;
                    container.innerHTML = '';
                    const el = document.createElement('div');
                    el.className = 'alert';
                    el.style.background = (type==='success' ? 'var(--color-success)' : 'var(--color-warning)');
                    el.style.color = 'var(--color-white)';
                    el.style.padding = '8px 12px';
                    el.style.borderRadius = '8px';
                    el.style.maxWidth = '980px';
                    el.style.margin = '0 auto';
                    el.style.display = 'flex';
                    el.style.justifyContent = 'space-between';
                    el.innerHTML = '<div>' + message + '</div>';
                    container.appendChild(el); container.style.display = 'block';
                    setTimeout(function(){ try{ container.style.display = 'none'; container.innerHTML = ''; }catch(_){} }, duration);
                }catch(e){}
            }

            async function ensureSWMatchesUser(){
                try{
                    const stored = localStorage.getItem(STORAGE_KEY);
                    const current = window.CURRENT_USER ? String(window.CURRENT_USER) : '';
                    // If no stored value, set it and exit
                    if(stored === null){ localStorage.setItem(STORAGE_KEY, current); return; }
                    if(String(stored) !== String(current)){
                        console.log('Detected user change for SW: stored=', stored, 'current=', current);
                        // Ask SW to self-unregister first (fast path)
                        let cleaned = false;
                        try{
                            cleaned = await askSWToSelfUnregister(900);
                        }catch(e){ cleaned = false; }

                        if(!cleaned){
                            // fallback: unregister via the page and clear caches
                            try{ await unregisterAndClear(); cleaned = true; }catch(e){ cleaned = false; }
                        }

                        // update stored value
                        try{ localStorage.setItem(STORAGE_KEY, current); }catch(e){}

                        if(cleaned){
                            showTransientToast('Service worker limpiado por cambio de usuario.', 'success', 4500);
                        } else {
                            if(navigator.onLine){
                                showTransientToast('No fue posible limpiar el service worker automáticamente. Recarga la página para garantizar que no haya contenido de otro usuario.', 'warning', 7000);
                            } else {
                                try{ if(typeof createAlert === 'function') createAlert('Se detectó un cambio de usuario. Para evitar problemas con la PWA, recarga la página cuando tengas conexión.', 'warning', true); }catch(e){}
                            }
                        }

                        // If we're online, reload so the page is no longer controlled by the old SW
                        if(navigator.onLine){
                            try{ setTimeout(function(){ try{ window.location.reload(); }catch(e){} }, 160); return; }catch(e){}
                        }

                        // Try to re-register only if we have an active user and are online (best-effort)
                        if(current && navigator.onLine && 'serviceWorker' in navigator){
                            try{ await navigator.serviceWorker.register('/sw.js'); console.log('SW re-registered after user change'); }catch(e){ console.warn('Re-register SW failed', e); }
                        }
                    }
                }catch(e){ console.warn('ensureSWMatchesUser failed', e); }
            }

            // Intercept logout forms to perform unregister/clear before submit so the SW isn't left serving
            try{
                document.querySelectorAll('form[action*="/logout"]').forEach(form=>{
                    if(form.__swLogoutIntercept) return; form.__swLogoutIntercept = true;
                    form.addEventListener('submit', function(e){
                        try{
                            e.preventDefault();
                            // best-effort: unregister, clear caches, then submit the form
                            unregisterAndClear().finally(function(){
                                try{ form.submit(); }catch(err){ window.location.href = form.getAttribute('action') || '/login'; }
                            });
                        }catch(err){ try{ form.submit(); }catch(_){ window.location.href = form.getAttribute('action') || '/login'; } }
                    });
                });
            }catch(e){}

            // Run check on load and when coming back online (in case server changed session)
            document.addEventListener('DOMContentLoaded', function(){ setTimeout(ensureSWMatchesUser, 80); });
            window.addEventListener('online', function(){ setTimeout(ensureSWMatchesUser, 150); });
        })();
    </script>
        <script>
            // Mostrar aviso cuando haya una nueva versión del SW y permitir activarla
            (function(){
                if(!('serviceWorker' in navigator)) return;
                navigator.serviceWorker.getRegistration().then(function(reg){
                    if(!reg) return;
                    // Listen for updates found after registration
                    reg.addEventListener('updatefound', function(){
                        const newWorker = reg.installing;
                        if(!newWorker) return;
                        newWorker.addEventListener('statechange', function(){
                            if(newWorker.state === 'installed'){
                                // If there's an existing controller, this is an update
                                if(navigator.serviceWorker.controller){
                                    // Show simple persistent banner in #network-alert
                                    try{
                                        const container = document.getElementById('network-alert');
                                        if(container){
                                            container.innerHTML = '';
                                            const el = document.createElement('div');
                                            el.style.background = 'var(--color-light)';
                                            el.style.color = 'var(--color-white)';
                                            el.style.padding = '10px 14px';
                                            el.style.borderRadius = '8px';
                                            el.style.maxWidth = '980px';
                                            el.style.margin = '0 auto';
                                            el.style.display = 'flex';
                                            el.style.justifyContent = 'space-between';
                                            el.style.alignItems = 'center';
                                            el.innerHTML = '<div>Nueva versión disponible.</div>';
                                            const btn = document.createElement('button');
                                            btn.className = 'btn btn-light btn-sm';
                                            btn.style.fontWeight = '700';
                                            btn.innerText = 'Actualizar';
                                            btn.addEventListener('click', function(){
                                                // Ask SW to skipWaiting
                                                if(reg.waiting) reg.waiting.postMessage({ type: 'SKIP_WAITING' });
                                            });
                                            el.appendChild(btn);
                                            container.appendChild(el);
                                            container.style.display = 'block';
                                        }
                                    }catch(e){ console.warn('show update banner failed', e); }
                                }
                            }
                        });
                    });
                });

                // When the new worker becomes active, reload the page to use the new assets
                navigator.serviceWorker.addEventListener('controllerchange', function(){
                    console.log('[Page] SW controller changed — reloading to activate new SW');
                    window.location.reload();
                });
            })();
        </script>
    <style>
        /* Small animation to highlight downloaded items */
        .download-animated {
            animation: pulseDownload 900ms ease-in-out 1;
            transform-origin: center;
        }
        @keyframes pulseDownload {
            0% { transform: scale(0.98); box-shadow: 0 4px 10px rgba(40,167,69,0.08); }
            50% { transform: scale(1.04); box-shadow: 0 12px 30px rgba(40,167,69,0.16); }
            100% { transform: scale(1.0); box-shadow: 0 6px 14px rgba(40,167,69,0.08); }
        }
        /* Force activity modal body readable even if other styles try to dim it */
        .modal .activity-modal-body,
        .modal.show .activity-modal-body {
            background: rgba(255,255,255,0.98) !important;
            color: #0b0b0b !important;
            opacity: 1 !important;
            filter: none !important;
            -webkit-filter: none !important;
            mix-blend-mode: normal !important;
            font-size: 0.95rem !important;
            line-height: 1.6 !important;
            text-align: left !important;
            padding: 0.6rem 0.8rem !important;
            border-radius: 8px !important;
            box-shadow: 0 1px 0 rgba(0,0,0,0.02) inset !important;
        }

        /* Slightly smaller modal title for better balance */
        .modal .modal-body h4,
        .modal .modal-title {
            font-size: 1.05rem !important;
            font-weight: 700 !important;
            word-break: break-word !important;
        }
    </style>
    
</body>
</html>
