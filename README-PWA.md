Microcursos - PWA integration

Qué se agregó:
- Nueva vista raíz `resources/views/index.blade.php` (Bootstrap-based)
- Vistas adicionales: `contacto` y `solicitudes`
- `public/manifest.json` (web app manifest)
- `public/sw.js` (service worker, simple cache-first strategy)
- `public/css/style.css` y `public/js/script.js`
- `public/images/toro-rojo.png` (placeholder - copy the real file from your attachment into this path)

Instrucciones para probar en Windows (PowerShell):
1. Copia la imagen `toro-rojo.png` desde `c:\Users\jovan\Desktop\Aplicaciones Web\Examen Diagnostico - 202321940\img\toro-rojo.png` a `c:\xampp\htdocs\Microcursos Local\public\images\toro-rojo.png`.
2. Inicia Apache/XAMPP y abre: http://localhost/ (o la URL donde sirves el proyecto). Deberías ver la nueva vista.
3. Abre DevTools > Application (Chrome) y verifica que `manifest.json` carga, y que el `Service Worker` aparece en `sw.js`.
4. Para forzar recarga del service worker: unregister en DevTools > Application > Service Workers y luego recarga la página.

Notas:
- El bootstrap se carga desde CDN. Si quieres todo offline, descarga los archivos y colócalos en `public/vendor/bootstrap/`.
- Si necesitas precache más rutas dinámicas usa workbox o ajusta `sw.js`.

Siguientes pasos recomendados:
- Mover assets a versiones con hash y actualizar `sw.js` para cache busting.
- Añadir iconos PNG/512 y splash screens para iOS/Android.
- Implementar prompt UI para `beforeinstallprompt`.
