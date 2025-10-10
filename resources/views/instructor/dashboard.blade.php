@extends('layouts.app')

@section('title','Panel de Instructor')

@section('content')
<div class="container my-4">
    <h1 class="mb-3">Panel de Instructor</h1>
    <p class="text-muted">Acciones útiles para instructores: ver empleados asignados, revisar progreso y comunicarse con su equipo.</p>

    <div class="card mb-3">
        <div class="card-body">
            <h5>Empleados asignados</h5>
        </div>
        @if($myEmployees && count($myEmployees))
            <div class="row g-3">
                @foreach($myEmployees as $emp)
                    <div class="col-md-6">
                        <div class="card shadow-sm p-2">
                            <div class="card-header bg-white border-0">
                                <h6 class="mb-0">{{ $emp->name }}</h6>
                            </div>

                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="small text-muted">{{ $emp->email }}</div>
                                </div>
                                <div>
                                    <a href="{{ route('instructor.employee.progress', ['id' => $emp->id_usuario ?? $emp->id]) }}" class="btn btn-sm btn--video">Ver progreso</a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-muted">No tienes empleados asignados.</div>
        @endif
    </div>

    <div class="card">
        <div class="card-body">
            <h5>Herramientas rápidas</h5>
                <div class="d-flex gap-2">
                <a href="{{ route('instructor.export_assigned') }}" class="btn btn--simple">Exportar lista</a>
                <button id="markDoneBtn" class="btn btn-outline-secondary">Marcar tareas completadas</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('markDoneBtn')?.addEventListener('click', function(e){
    e.preventDefault();
    if (!confirm('¿Marcar todas las tareas de los empleados asignados como completadas? Esta acción no está implementada y serviría como ejemplo. Continuar?')) return;
    // Placeholder: show a toast or alert. Implement real behavior after confirmation.
    alert('Función no implementada: implementar confirmations y endpoint para marcar progreso.');
});
</script>
@endpush
