# Microcursos Toros Rojos

Microcursos Toros Rojos es una plataforma para crear, distribuir y seguir microcursos en línea desarrollada con Laravel. Está pensada para instructores y estudiantes que desean aprender en módulos cortos y prácticos.

## ¿Qué puedes hacer?

- Explorar cursos y módulos.
- Inscribirte en cursos (en la instancia local/demo las inscripciones no afectan a la base de datos pública y tienen ajustes de privacidad estrictos).
- Ver lecciones y recursos asociados.
- Realizar evaluaciones y ver resultados.
- Llevar un seguimiento de progreso por lección y módulo.
- Para administradores: crear/editar cursos, módulos, lecciones e instructores.

## Sitio en vivo

Visita el sitio en: https://bautista.castelancarpinteyro.com

## Credenciales demo (solo para pruebas)

Estas cuentas son de demostración y están destinadas únicamente a pruebas. No verás cambios reflejados en la instancia pública, y las cuentas tienen ajustes de privacidad y limitaciones para proteger a todos los usuarios. Si quieres probar la experiencia real (guardar datos propios, participar activamente), crea una cuenta nueva en el sitio.

- Instructor demo:
  - Email: juan@demo.com
  - Contraseña: instructor123

- Empleado demo:
  - Email: ana@demo.com
  - Contraseña: empleado123

- Administrador demo:
  - Email: admin@demo.com
  - Contraseña: admin123

> Nota: Las credenciales están pensadas para acceder a las funcionalidades públicas de demostración. Si algún botón o acción no refleja cambios persistentes, es por medidas de seguridad y privacidad en esta instancia demo.

## Requisitos y ejecución local

Esta aplicación está desarrollada con Laravel. Para ejecutar localmente necesitas PHP, Composer, Node.js y una base de datos (MySQL/MariaDB).

Pasos rápidos:

1. Clona el repositorio.
2. Copia `.env.example` a `.env` y ajusta las variables.
3. Instala dependencias:

```bash
composer install
npm install
```

4. Genera la clave de la aplicación y ejecuta migraciones:

```bash
php artisan key:generate
php artisan migrate --seed
npm run dev
php artisan serve
```

## Seguridad y privacidad

Las cuentas demo tienen restricciones. No compres ni compartas credenciales reales. Si vas a probar con datos reales, crea una cuenta propia y revisa la política de privacidad.

---

Gracias por probar Microcursos Toros Rojos.