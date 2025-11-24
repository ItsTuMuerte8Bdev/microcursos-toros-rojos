<html>
<body>
    <p>Hola {{ $nombre }},</p>
{{-- ------ Inicio: emails/verify.blade.php ------ --}}
<p>Hola {{ $nombre }},</p>
    <p>Gracias por registrarte en Microcursos. Para activar tu cuenta, haz click en el siguiente enlace:</p>
    <p><a href="{{ url('/register/verify/'.$token) }}">Verificar mi correo</a></p>
<p>Si no solicitaste esta verificación, ignora este correo.</p>
{{-- ------ Fin: emails/verify.blade.php ------ --}}
    <p>Saludos,<br/>El equipo de Microcursos</p>
</body>
</html>