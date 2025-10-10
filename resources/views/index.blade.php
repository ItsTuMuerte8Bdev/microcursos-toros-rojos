@extends('layouts.app')

@section('title','Inicio | Los Toros Rojos')

@section('content')
    <div class="row align-items-center ">
        <div class="col-md-6">
            <h1 class="display-5 text-md-start">Microcursos Los Toros Rojos</h1>
            <p class="lead text-md-start text-justify">En Los Toros Rojos creemos que aprender debe ser sencillo y aprovechar el tiempo. Por eso ofrecemos microcursos breves y prácticos, pensados para quienes quieren mejorar sus habilidades sin largos compromisos: cortos, claros y listos para usar en el taller o en la pista.</p>

            <h5 id="section_designed">Diseñado para tu ritmo</h5>
            <p id="section_designed_p" class="text-md-start">Nuestros cursos están estructurados en lecciones cortas que puedes consultar cuando tengas un momento libre, desde cualquier dispositivo. La experiencia está hecha para facilitar el aprendizaje en pequeñas dosis.</p>

            <h5 id="section_progress">Tu progreso, seguro y ordenado</h5>
            <p id="section_progress_p" class="text-md-start">Cada usuario tiene su cuenta y su progreso guardado: así puedes retomar siempre donde lo dejaste y ver tu avance de forma clara.</p>

            <p class="mt-4 text-md-start"><strong>En pocas palabras:</strong> ofrecemos cursos para democratizar el acceso al conocimiento técnico, eliminando barreras de tiempo y conectividad, y entregando una experiencia de aprendizaje moderna y motivadora.</p>

            <div class="mt-4">
                        <a href="#" data-target-url="{{ url('/microcursos') }}" class="btn btn--primary btn-lg me-2" id="mainCoursesBtn">Ir a Mis Cursos</a>
                        <a href="{{ url('/descargas/administrar') }}" class="btn btn--simple btn-lg me-2 d-none" id="offlineDownloadsBtn">Ir a Mis Descargas</a>
            </div>
        </div>
        <div class="col-md-6 text-center d-none d-md-block">
            <img src="{{ asset('images/Toro Cursos.png') }}" alt="Los Toros Rojos" class="img-fluid rounded" style="max-height:320px;">
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function(){
            // When offline and user logged in, change CTA and show helpful text
            const isOnline = navigator.onLine;
            const isAuth = !!window.CURRENT_USER;
            const mainBtn = document.getElementById('mainCoursesBtn');
            const downloadsBtn = document.getElementById('offlineDownloadsBtn');

            function showOfflineMessage(){
                // Replace the lead paragraph with offline specific text
                const lead = document.querySelector('.lead');
                if(lead){
                    lead.innerText = 'Estás en modo sin conexión. Puedes acceder a los cursos que descargaste previamente en este dispositivo. Tus avances se guardarán localmente y se sincronizarán cuando recuperes la conexión.';
                }
            }

            async function filterToDownloads(){
                if(!window.OfflinePWA) return;
                const courses = await window.OfflinePWA.getDownloadedCourses();
                if(courses && courses.length){
                    window.location.href = '/descargas/administrar';
                } else {
                    alert('No tienes cursos descargados localmente.');
                }
            }

            // Initial adjustment
            if(!isOnline){
                showOfflineMessage();
                // When offline and authenticated, show downloads CTA
                if(isAuth){
                    if(mainBtn) mainBtn.classList.add('d-none');
                    if(downloadsBtn) downloadsBtn.classList.remove('d-none');
                    if(downloadsBtn) downloadsBtn.addEventListener('click', function(e){ e.preventDefault(); filterToDownloads(); });
                } else {
                    // not authenticated: disable courses
                    if(mainBtn) mainBtn.classList.add('disabled-link');
                }
                // hide longer marketing sections when truly offline (we have specific ids)
                try{
                    const s1 = document.getElementById('section_designed');
                    const p1 = document.getElementById('section_designed_p');
                    if(s1) s1.style.display = 'none'; if(p1) p1.style.display = 'none';
                    const s2 = document.getElementById('section_progress');
                    const p2 = document.getElementById('section_progress_p');
                    if(s2) s2.style.display = 'none'; if(p2) p2.style.display = 'none';
                }catch(e){}
            }
            // Ensure mainCoursesBtn always handles clicks explicitly so that
            // on mobile devices we don't get the default anchor behaviour
            // (href="#") which scrolls the page to the top. This handler
            // will navigate when the user is authenticated or show the
            // login-required modal (with redirect) for guests.
            if (mainBtn) {
                try{
                    mainBtn.addEventListener('click', function(e){
                        e.preventDefault();
                        e.stopPropagation();
                        const target = mainBtn.getAttribute('data-target-url') || mainBtn.getAttribute('href') || '/microcursos';

                        // If authenticated, go directly
                        if (window.CURRENT_USER) {
                            window.location.href = target;
                            return;
                        }

                        // Otherwise, show login modal and ensure redirect param is set
                        const modalEl = document.getElementById('loginRequiredModal');
                        if (modalEl){
                            try{
                                const modal = new bootstrap.Modal(modalEl);
                                const loginBtn = document.getElementById('modalLoginBtn');
                                if (loginBtn) {
                                    try{
                                        const url = new URL(loginBtn.getAttribute('href'), window.location.origin);
                                        url.searchParams.set('redirect_to', target);
                                        loginBtn.setAttribute('href', url.toString());
                                    }catch(_){ /* ignore URL errors */ }
                                }
                                modal.show();
                            }catch(_){ window.location.href = '/login?redirect_to=' + encodeURIComponent(target); }
                        } else {
                            window.location.href = '/login?redirect_to=' + encodeURIComponent(target);
                        }
                    });
                }catch(e){}
            }

            // Also react to changes while on the page
            window.addEventListener('offline', function(){ showOfflineMessage(); if(isAuth){ if(mainBtn) mainBtn.classList.add('d-none'); if(downloadsBtn) downloadsBtn.classList.remove('d-none'); } else { if(mainBtn) mainBtn.classList.add('disabled-link'); } });
            window.addEventListener('online', function(){ location.reload(); });
        })();
    </script>
@endpush
