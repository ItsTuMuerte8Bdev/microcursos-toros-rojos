@extends('layouts.app')

@section('title','Limpieza PWA | Admin')

@section('content')
<div class="container my-4">
    <h1 class="mb-3">Limpieza PWA (Service Workers)</h1>
    <p class="text-muted">Esta herramienta ejecuta la limpieza opt-in en el cliente actual: desregistrará service workers y eliminará caches relacionadas. Ejecutar solo si entiendes el impacto.</p>

    <div class="card mb-3">
        <div class="card-body">
            <div class="mb-3">
                <button id="runCleanupBtn" class="btn btn-danger">Ejecutar limpieza PWA</button>
                <button id="clearLogsBtn" class="btn btn--simple ms-2">Limpiar registros</button>
            </div>
            <div class="alert alert-info" id="infoBox">Presiona "Ejecutar limpieza PWA" para iniciar. Revisa la consola y los registros abajo.</div>
            <div id="cleanupLogs" style="max-height:300px; overflow:auto; background:#f8f9fa; padding:12px; border-radius:6px; border:1px solid #ececec; font-family: monospace; white-space:pre-wrap;"></div>
        </div>
    </div>

    <p class="small text-muted">Nota: esto limpia el cliente actual. Para limpiar otros clientes, pídeles que visiten esta página mientras están autenticados como admin o usar otra estrategia centralizada.</p>
</div>
@endsection

@push('scripts')
<script>
(function(){
    const logsEl = document.getElementById('cleanupLogs');
    const infoBox = document.getElementById('infoBox');
    const runBtn = document.getElementById('runCleanupBtn');
    const clearBtn = document.getElementById('clearLogsBtn');

    function log(msg){
        const ts = new Date().toISOString();
        logsEl.textContent = ts + ' - ' + msg + '\n' + logsEl.textContent;
    }

    clearBtn.addEventListener('click', function(){ logsEl.textContent = ''; infoBox.textContent = 'Presiona "Ejecutar limpieza PWA" para iniciar.'; });

    runBtn.addEventListener('click', function(){
        infoBox.textContent = 'Ejecutando limpieza... revisa la consola para detalles también.';

        try{
            if (typeof window.cleanupPWA !== 'function'){
                log('ERROR: cleanupPWA() no está definida en esta página.');
                infoBox.textContent = 'ERROR: cleanupPWA() no está disponible. Asegúrate de que la plantilla cargó el script.';
                return;
            }

            // intercept console.log/warn to capture messages
            const originalLog = console.log;
            const originalWarn = console.warn;
            const originalError = console.error;

            console.log = function(){ try{ log(Array.from(arguments).join(' ')); }catch(e){}; originalLog.apply(console, arguments); };
            console.warn = function(){ try{ log('WARN: ' + Array.from(arguments).join(' ')); }catch(e){}; originalWarn.apply(console, arguments); };
            console.error = function(){ try{ log('ERROR: ' + Array.from(arguments).join(' ')); }catch(e){}; originalError.apply(console, arguments); };

            // Ejecutar la limpieza
            window.cleanupPWA();

            // Restaurar consola tras 3s
            setTimeout(function(){ console.log = originalLog; console.warn = originalWarn; console.error = originalError; log('Limpieza iniciada. Revisa Application -> Service Workers y Cache Storage si quieres confirmar.'); }, 3000);

        }catch(e){ log('EXCEPCIÓN al ejecutar cleanupPWA: ' + (e && e.message ? e.message : e)); }
    });
})();
</script>
@endpush
