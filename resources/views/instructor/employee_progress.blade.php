@extends('layouts.app')

@section('title','Progreso del empleado')

@section('content')
    <div class="container my-4">
        <h1 class="mb-3">Progreso del empleado</h1>
        <div class="card">
            <div class="card-body">
                <div class="mb-3">
                    <strong>{{ $employee->nombre ?? $employee->name }}</strong>
                    @php
                        $demoIds = config('demo.ids', []);
                        $demoEmails = config('demo.emails', []);
                        $mask = false;
                        if(auth()->check()){
                            try{ $au = auth()->user(); $aid = $au->getAuthIdentifier(); if ($aid && in_array(intval($aid), $demoIds, true)) $mask = true; $aem = $au->email ?? ($au->correo ?? null); if ($aem && in_array(strtolower($aem), array_map('strtolower',$demoEmails), true)) $mask = true; }catch(\Throwable $e){}
                        }
                    @endphp
                    <div class="small text-muted">@if($mask) correo oculto @else {{ $employee->correo ?? $employee->email }} @endif</div>
                </div>

                <p class="small text-muted">Aquí puedes ver el porcentaje de avance por curso (como en administración).</p>

                <div id="progressResults"></div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
(function(){
    const csrf = '{{ csrf_token() }}';
    // Provide the list of course ids from server-rendered variable if available
    const cursos = @json($cursos->pluck('id_curso')) || [];
    const out = document.getElementById('progressResults');
    out.innerHTML = '';

    if (!cursos || cursos.length === 0) {
        out.innerHTML = '<div class="text-muted">No hay cursos disponibles.</div>';
        return;
    }

    fetch('/api/cursos/progress-batch', {
        method: 'POST',
        headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': csrf, 'Accept':'application/json' },
        body: JSON.stringify({ ids: cursos, id_usuario: {{ $employee->id_usuario ?? $employee->id }} })
    }).then(r=>r.json()).then(data=>{
        const list = document.createElement('div');
        list.className = 'list-group';
        const cursosMap = @json($cursos->keyBy('id_curso'));
        for(const cid of Object.keys(data)){
            const info = data[cid];
            const curso = cursosMap[cid];
            const title = curso ? curso.titulo : ('Curso '+cid);
            const item = document.createElement('div');
            item.className = 'list-group-item d-flex justify-content-between align-items-center';
            item.innerHTML = `<div><strong>${title}</strong><div class="small text-muted">${info.completed}/${info.total} lecciones</div></div><div style="min-width:120px"><div class="progress" style="height:8px; width:120px"><div class="progress-bar" role="progressbar" style="width:${info.percent}%" aria-valuenow="${info.percent}" aria-valuemin="0" aria-valuemax="100"></div></div><small class="text-muted">${info.percent}%</small></div>`;
            list.appendChild(item);
        }
        out.appendChild(list);
    }).catch(err=>{ out.innerHTML = '<div class="text-danger small">Error al obtener avances</div>'; });
})();
</script>
@endpush
