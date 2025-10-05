// Script básico (versión sin PWA)
console.log('Microcursos script cargado');

// Comportamiento simple para formularios que muestran un popup de éxito.
document.addEventListener('DOMContentLoaded', function () {
  // Sólo adjuntar el comportamiento a formularios que tengan la clase indicada
  const forms = document.querySelectorAll('form.js-show-success-popup');
  forms.forEach(function (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      showSuccessPopup();
      form.reset();
    });
  });

  function showSuccessPopup() {
    // Crear el popup
    const popup = document.createElement('div');
    popup.className = 'success-popup';
    popup.innerHTML = `
      <div class="success-popup-content">
        <span class="bs-check-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="currentColor" class="bi bi-check-circle-fill" viewBox="0 0 16 16">
            <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM6.97 11.03a.75.75 0 0 0 1.07.02l3.992-3.99a.75.75 0 1 0-1.06-1.06L7.525 9.475 5.53 7.47a.75.75 0 0 0-1.06 1.06l2 2z"/>
          </svg>
        </span>
        <h2 class="success-title">¡Registro exitoso!</h2>
        <p class="success-message">Gracias por la información.<br>Esperamos contactarte pronto.</p>
        <button id="close-popup" class="success-btn">Cerrar</button>
      </div>
    `;
    document.body.appendChild(popup);

    // Cerrar el popup
    document.getElementById('close-popup').onclick = function () {
      popup.remove();
    };
  }
});

// Nota: la lógica PWA (registro de service worker, handlers de instalación, etc.) se mantiene fuera de este script.

// Intento de desregistrar cualquier service worker activo en el cliente.
// Ayuda cuando un SW antiguo sigue controlando la página y sirve scripts cacheados
// (por ejemplo, mostrando logs de PWA o errores provenientes de `sw.js`).
if ('serviceWorker' in navigator) {
  // Ejecutar en la siguiente microtarea para no interferir con la carga principal
  Promise.resolve().then(() => {
    navigator.serviceWorker
      .getRegistrations()
      .then((regs) => {
        if (!regs || !regs.length) return;
        console.log('Se encontraron', regs.length, 'registro(s) de service worker. Intentando desregistrar...');
        regs.forEach((reg) => {
          try {
            reg.unregister().then((ok) => console.log('Service worker desregistrado:', ok, reg.scope));
          } catch (e) {
            console.warn('Fallo al desregistrar SW:', e);
          }
        });
      })
      .catch((err) => {
        console.warn('Error al enumerar service workers:', err);
      });
  });
}
