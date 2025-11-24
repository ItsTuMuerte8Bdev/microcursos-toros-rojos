@php
    $demoText = 'Perfil de prueba — Esta cuenta es solo de demostración. Cualquier cambio (contraseña, perfil, acciones de administrador, progreso) no se guardará en la base de datos. Para una experiencia real crea una cuenta propia.';
@endphp
<div id="demo-warning" class="container" style="position:fixed;right:12px;bottom:12px;z-index:3000;max-width:360px;">
    <div class="alert" style="background:#ff8a00;color:#fff;font-weight:700;border-radius:10px;box-shadow:0 8px 20px rgba(0,0,0,0.12);">
        <div style="display:flex;align-items:flex-start;gap:8px;">
            <div><i class="bi bi-exclamation-triangle-fill" style="font-size:1.2rem;color:#fff;margin-top:2px;"></i></div>
            <div style="flex:1;font-size:0.95rem;line-height:1.1;">{!! $demoText !!}</div>
        </div>
    </div>
</div>
