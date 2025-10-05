<html>
<body>
    <p>Hola {{ $nombre }},</p>
    <p>Gracias por registrarte en Microcursos. Para activar tu cuenta, haz click en el siguiente enlace:</p>
    <p><a href="{{ url('/register/verify/'.$token) }}">Verificar mi correo</a></p>
    <p>Si no solicitaste esta cuenta, ignora este correo.</p>
    <p>Saludos,<br/>El equipo de Microcursos</p>
</body>
</html>