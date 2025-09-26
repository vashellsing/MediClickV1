// public/js/login.js
(() => {
  const form = document.getElementById('loginForm');
  if (!form) return;

  const emailInput = document.getElementById('email');
  const passwordInput = document.getElementById('password');
  const toggleBtn = document.getElementById('togglePasswordBtn');
  const errorMsg = document.getElementById('errorMsg');

  // Toggle password (añadimos listener al botón)
  if (toggleBtn && passwordInput) {
    toggleBtn.addEventListener('click', () => {
      if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleBtn.setAttribute('aria-pressed', 'true');
      } else {
        passwordInput.type = 'password';
        toggleBtn.setAttribute('aria-pressed', 'false');
      }
    });
  }

  // Si el servidor dejó un mensaje de error en el DOM, marcarlo visible
  if (errorMsg && errorMsg.textContent.trim().length > 0) {
    errorMsg.style.display = 'block';
  }

  // Ocultar mensaje de error cuando el usuario edite campos
  [emailInput, passwordInput].forEach((el) => {
    if (!el) return;
    el.addEventListener('input', () => {
      if (!errorMsg) return;
      // Ocultamos para mejorar UX; el texto se conservará si el servidor lo volvió a imprimir
      errorMsg.style.display = 'none';
    });
  });

  // Validación en submit: si el formulario falla validación HTML5, prevenimos submit y mostramos feedback
  form.addEventListener('submit', function (e) {
    if (!form.checkValidity()) {
      e.preventDefault();
      // Dejar que el navegador muestre mensajes nativos
      form.reportValidity();
      return;
    }
    // Si pasa la validación cliente, dejamos que el formulario se envíe al servidor
    // No hacemos preventDefault aquí: el servidor validará las credenciales reales
  });
})();
