@extends('layouts.app')

@section('title','Asignar Empleados a Instructores')

@section('content')
<div class="container my-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="m-0">Asignar empleados a instructores</h1>
    </div>
    
    @if(session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    
    <form method="POST" action="{{ route('admin.assign_instructors') }}">
        @csrf
        <div class="row">
            <div class="col-md-4">
                <label class="form-label">Seleccionar instructor</label>
                <select id="instructorSelect" name="instructor_id" class="form-select" required>
                    <option value="">-- Seleccionar --</option>
                    @foreach($instructors as $ins)
                    <option value="{{ $ins->getKey() }}">{{ $ins->name }} (ID: {{ $ins->getKey() }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-8">
                <label class="form-label">Seleccionar empleados</label>
                <div id="employeesList" class="border p-2" style="max-height:300px; overflow:auto;">
                    @php
                        $demoIds = [1,2];
                        $demoEmails = ['admin@demo.com','juan@demo.com'];
                        $currentIsDemo = false;
                        if(auth()->check()){
                            try{ $cu = auth()->user(); $cuid = $cu->getAuthIdentifier(); if ($cuid && in_array(intval($cuid), $demoIds, true)) $currentIsDemo = true; $cem = $cu->email ?? ($cu->correo ?? null); if ($cem && in_array(strtolower($cem), array_map('strtolower',$demoEmails), true)) $currentIsDemo = true; }catch(\Throwable $e){}
                        }
                    @endphp
                    @foreach($employees as $emp)
                        <div class="form-check">
                            <input class="form-check-input emp-checkbox" type="checkbox" value="{{ $emp->getKey() }}" id="emp{{ $emp->getKey() }}" name="employee_ids[]">
                            <label class="form-check-label" for="emp{{ $emp->getKey() }}">{{ $emp->name }} @if(!$currentIsDemo) ({{ $emp->email }}) @else (<em>correo oculto</em>) @endif</label>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        
        <div class="mt-3">
            <button class="btn btn--primary">Guardar asignaciones</button>
            <a href="{{ url('/admin/cursos') }}" class="btn btn-secondary ms-2">Volver</a>
        </div>
    </form>
    
    <hr>
    <h5>Asignaciones actuales</h5>
    <div>
        @php
        // construir un mapa rápido employeeId => employee (por si se pasó la colección de empleados)
        $empMap = [];
            foreach($employees as $e) { $empMap[$e->getKey()] = $e; }
        @endphp
        
        @if(count($assignments))
        <form method="GET" class="d-flex align-items-center">
            <label class="me-2 mb-0 small text-muted">Ordenar:</label>
            <select name="sort" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="name" {{ (isset($sort) && $sort==='name') ? 'selected' : (request('sort','name')==='name' ? 'selected' : '') }}>Por nombre</option>
                <option value="count" {{ (isset($sort) && $sort==='count') ? 'selected' : (request('sort')==='count' ? 'selected' : '') }}>Por número de asignaciones</option>
            </select>
        </form>
            <div class="row g-3">
                @foreach($instructors as $ins)
                    @php
                        $iid = $ins->getKey();
                        $assigned = isset($assignments[$iid]) ? $assignments[$iid] : [];
                    @endphp
                    <div class="col-md-6">
                        <div class="card shadow-sm p-2">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <strong>{{ $ins->name }}</strong>
                                    <div class="text-muted small">ID: {{ $ins->getKey() }}</div>
                                </div>
                                <div class="text-end">
                                    <small class="text-muted">Asignados: {{ count($assigned) }}</small>
                                </div>
                            </div>
                            <div class="mt-2">
                                @if(!count($assigned))
                                    <div class="text-muted">— Sin empleados asignados —</div>
                                @else
                                    @foreach($assigned as $aid)
                                        @php $emp = $empMap[$aid] ?? null; @endphp
                                        @if($emp)
                                            <span class="badge bg-primary me-1 mb-1">{{ $emp->name }} <small class="text-white-50">(@if(!$currentIsDemo) {{ $emp->email }} @else correo oculto @endif)</small></span>
                                        @else
                                            <span class="badge bg-secondary me-1 mb-1">ID: {{ $aid }}</span>
                                        @endif
                                    @endforeach
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-muted">No hay asignaciones.</div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
// Restaurar checkboxes cuando se seleccione instructor (si hay asignaciones previas)
(function(){
    const assignments = @json($assignments);
    const instrSelect = document.getElementById('instructorSelect');
    const empCheckboxes = document.querySelectorAll('.emp-checkbox');

    function restoreForInstructor(id){
        empCheckboxes.forEach(cb=>cb.checked=false);
        if (!id) return;
        const arr = assignments[id] || [];
        arr.forEach(function(e){
            const el = document.getElementById('emp'+e);
            if (el) el.checked = true;
        });
    }

    instrSelect.addEventListener('change', function(){ restoreForInstructor(this.value); });

    // restore initial if any selected
    if (instrSelect.value) restoreForInstructor(instrSelect.value);
})();
</script>
@endpush
