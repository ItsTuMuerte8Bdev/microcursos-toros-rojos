@extends('layouts.app')

{{-- ------ Inicio: admin/cursos/index.blade.php ------ --}}

@section('title','Admin - Cursos')

@section('content')
    <div class="mb-4">
        <h1 class="mb-0 text-center">Administración — Cursos & Usuarios</h1>
    </div>

    <div class="d-flex flex-column align-items-center">

        <div class="card mb-4" style="max-width:900px; width:100%; border-radius:12px;">
            <div class="card-body">
                <h5>Cursos</h5>
                <table class="table table-sm">
                    <thead>
                        <tr><th>ID</th><th>Título</th><th>Creador</th><th>Estado</th></tr>
                    </thead>
                    <tbody>
                        @foreach($cursos as $c)
                            <tr>
                                <td>{{ $c->id_curso }}</td>
                                <td>{{ $c->titulo }}</td>
                                <td>{{ $c->creador->nombre ?? '—' }}</td>
                                <td>{{ $c->estado }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card mb-4" style="max-width:900px; width:100%; border-radius:12px;">
            <div class="card-body">
                <h5>Avances por usuario</h5>
                <p class="small text-muted">Selecciona un usuario para ver su porcentaje de avance en cada curso.</p>
                <div id="userProgressArea">
                    <div class="mb-3">
                        <label class="form-label">Usuario</label>
                        <select id="selectUser" class="form-select">
                            <option value="">-- Selecciona un usuario --</option>
                            @foreach($usuarios as $u)
                                <option value="{{ $u->id_usuario }}">{{ $u->nombre }} {{ $u->apellido }} — {{ $u->rol }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div id="progressResults"></div>
                </div>
            </div>
        </div>

        <div class="card mb-4" style="max-width:900px; width:100%; border-radius:12px;">
            <div class="card-body">
                <h5>Usuarios</h5>
                <p class="small text-muted">Solo mostramos nombre completo y rol. Desde aquí puedes cambiar el rol de un usuario.</p>
                <table class="table table-sm">
                    <thead><tr><th>Nombre</th><th>Rol</th><th></th></tr></thead>
                    <tbody>
                        @foreach($usuarios as $u)
                            <tr data-user-id="{{ $u->id_usuario }}">
                                <td>{{ $u->nombre }} {{ $u->apellido }}</td>
                                <td class="current-role">{{ $u->rol }}</td>
                                <td style="width:1%">
                                    <select class="form-select form-select-sm change-role-select" style="min-width:120px">
                                        <option value="admin" {{ $u->rol === 'admin' ? 'selected' : '' }}>admin</option>
                                        <option value="instructor" {{ $u->rol === 'instructor' ? 'selected' : '' }}>instructor</option>
                                        <option value="empleado" {{ $u->rol === 'empleado' ? 'selected' : '' }}>empleado</option>
                                    </select>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <script>
        (function(){
            const csrf = '{{ csrf_token() }}';
            const cursos = @json($cursos->pluck('id_curso'));

            // When user selected, fetch progress for all courses for that user
            document.getElementById('selectUser').addEventListener('change', function(){
                const userId = this.value;
                const out = document.getElementById('progressResults');
                out.innerHTML = '';
                if(!userId) return;

                fetch('/api/cursos/progress-batch', {
                    method: 'POST',
                    headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': csrf, 'Accept':'application/json' },
                    body: JSON.stringify({ ids: cursos, id_usuario: parseInt(userId) })
                }).then(r=>r.json()).then(data=>{
                    const list = document.createElement('div');
                    list.className = 'list-group';
                    for(const cid of Object.keys(data)){
                        const info = data[cid];
                        const curso = @json($cursos->keyBy('id_curso'))[cid];
                        const title = curso ? curso.titulo : ('Curso '+cid);
                        const item = document.createElement('div');
                        item.className = 'list-group-item d-flex justify-content-between align-items-center';
                        item.innerHTML = `<div><strong>${title}</strong><div class="small text-muted">${info.completed}/${info.total} lecciones</div></div><div style="min-width:120px"><div class="progress" style="height:8px; width:120px"><div class="progress-bar" role="progressbar" style="width:${info.percent}%" aria-valuenow="${info.percent}" aria-valuemin="0" aria-valuemax="100"></div></div><small class="text-muted">${info.percent}%</small></div>`;
                        list.appendChild(item);
                    }
                    out.appendChild(list);
                }).catch(err=>{
                    out.innerHTML = '<div class="text-danger small">Error al obtener avances</div>';
                });
            });

            // Change role handler
            document.querySelectorAll('.change-role-select').forEach(function(sel){
                sel.addEventListener('change', function(){
                    const tr = this.closest('tr');
                    const id = tr.getAttribute('data-user-id');
                    const newRole = this.value;
                    fetch('/admin/user/change-role', {
                        method: 'POST',
                        headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': csrf, 'Accept':'application/json' },
                        body: JSON.stringify({ id_usuario: parseInt(id), rol: newRole })
                    }).then(r=>r.json()).then(json=>{
                        if(json && json.demo){
                            // demo admin attempted to change role: revert select and notify
                            const prev = tr.querySelector('.current-role').textContent.trim();
                            try{ sel.value = prev; }catch(e){}
                            if (window.showBootstrapToast) window.showBootstrapToast('warning','Cuenta demo', json.message || 'Acción no permitida en cuenta de demostración');
                            return;
                        }
                        if(json.ok){
                            tr.querySelector('.current-role').textContent = json.rol;
                            if (window.showBootstrapToast) window.showBootstrapToast('success','Rol actualizado', 'Se actualizó el rol del usuario.');
                        } else if(json.error){
                            try{ sel.value = tr.querySelector('.current-role').textContent.trim(); }catch(e){}
                            if (window.showBootstrapToast) window.showBootstrapToast('danger','Error', json.error || 'Error al cambiar rol');
                        }
                    }).catch(e=>{ try{ sel.value = tr.querySelector('.current-role').textContent.trim(); }catch(err){} if (window.showBootstrapToast) window.showBootstrapToast('danger','Error','Error al cambiar rol'); });
                });
            });
        })();
    </script>
@endsection

{{-- ------ Fin: admin/cursos/index.blade.php ------ --}}
