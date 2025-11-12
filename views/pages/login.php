<?php
// Evita avisos si las variables aún no existen
$error = isset($error) ? $error : '';
$oldEmail = isset($_POST['email']) ? htmlspecialchars($_POST['email'], ENT_QUOTES) : '';
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
  <div class="container d-flex justify-content-center align-items-center" style="min-height: 75vh;">
    <div class="card login-card shadow-sm p-4" style="max-width:420px; width:100%;">
      <!-- Logotipo -->
      <div class="text-center mb-3">
        <img src="public/img/Icono.png" alt="MediClick" class="img-fluid" style="max-height:72px; object-fit:contain;">
        <h1><span class="fw-semibold">MediClick</span></h1>
      </div>

      <h4 class="text-center fw-bold mb-3">Iniciar sesión</h4>
      <p class="text-center text-muted small mb-4">
        Ingresa con tu correo y número de cédula como contraseña.
      </p>

      <!-- Formulario -->
      <form id="loginForm" method="POST" action="index.php?page=login" novalidate>
        <!-- Email -->
        <div class="mb-3">
          <label for="email" class="form-label">Correo</label>
          <input
            type="email"
            class="form-control"
            id="email"
            name="email"
            placeholder="ejemplo@correo.com"
            required
            value="<?php echo $oldEmail; ?>"
            aria-describedby="emailHelp"
          >
        </div>

        <!-- Contraseña -->
        <div class="mb-3">
          <label for="password" class="form-label">Contraseña (Número de cédula)</label>
          <div class="input-group">
            <input
              type="password"
              class="form-control"
              id="password"
              name="password"
              required
              aria-describedby="togglePasswordBtn"
              placeholder="********"
            >
            <button
              class="btn btn-outline-secondary"
              type="button"
              id="togglePasswordBtn"
              aria-pressed="false"
              aria-label="Mostrar u ocultar contraseña"
              title="Mostrar contraseña"
            >👁</button>
          </div>
        </div>

        <!-- Error (validación servidor) -->
        <?php if (!empty($error)): ?>
          <div id="errorMsg" class="alert alert-danger mt-3" role="alert">
            <?php echo htmlspecialchars($error, ENT_QUOTES); ?>
          </div>
        <?php else: ?>
          <div id="errorMsg" class="alert alert-danger mt-3" role="alert" style="display:none;"></div>
        <?php endif; ?>

        <!-- Botón de enviar -->
        <div class="d-grid mb-3 mt-4">
          <button type="submit" class="btn btn-primary btn-lg" id="loginSubmit">Iniciar sesión</button>
        </div>

        <div class="text-center">
          <a href="#" class="small">¿Olvidaste tu contraseña?</a>
        </div>
      </form>

      <!-- Texto inferior -->
      <div class="text-center mt-3 small text-muted">
        Al usar MediClick aceptas nuestros <a href="#">Términos</a> y la <a href="#">Política de privacidad</a>.
      </div>
    </div>
  </div>
</main>

<!-- Script para mostrar/ocultar contraseña -->
<script>
document.getElementById('togglePasswordBtn').addEventListener('click', function() {
  const passwordInput = document.getElementById('password');
  const isHidden = passwordInput.getAttribute('type') === 'password';
  passwordInput.setAttribute('type', isHidden ? 'text' : 'password');
  this.textContent = isHidden ? '🙈' : '👁';
});
</script>
