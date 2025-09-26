
<div class="container d-flex justify-content-center align-items-center" style="min-height: 80vh;">
  <div class="card shadow-sm p-4" style="max-width: 400px; width: 100%;">
    <h3 class="text-center mb-4">Inicia sesión</h3>

    <form id="loginForm" method="POST" action="index.php?page=login" novalidate>
      <!-- Correo -->
      <div class="mb-3">
        <label for="email" class="form-label">Correo</label>
        <input
          type="email"
          class="form-control"
          id="email"
          name="email"
          placeholder="ejemplo@correo.com"
          required
          value="<?php echo htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES); ?>"
        >
      </div>

      <!-- Contraseña, se podrian agregar mas validaciones sobre solo numeros ... mensajes o alertas... -->
      <div class="mb-3">
        <label for="password" class="form-label">Contraseña (Número de cédula)</label>
        <div class="input-group">
          <input
            type="password"
            class="form-control"
            id="password"
            name="password"
            minlength="8"
            required
            aria-describedby="togglePasswordBtn"
          >
          <button
            class="btn btn-outline-secondary"
            type="button"
            id="togglePasswordBtn"
            aria-pressed="false"
            aria-label="Mostrar contraseña"
          >👁</button>
        </div>
      </div>

      <!-- Mensaje de error (server-side o para JS) -->
      <?php if (!empty($error)): ?>
        <div id="errorMsg" class="text-danger mb-3">
          <?php echo htmlspecialchars($error, ENT_QUOTES); ?>
        </div>
      <?php else: ?>
        <div id="errorMsg" class="text-danger mb-3" style="display:none;"></div>
      <?php endif; ?>

      <!-- Botón -->
      <div class="d-grid">
        <button type="submit" class="btn btn-primary">Iniciar</button>
      </div>
    </form>

    <!-- Links -->
    <div class="text-center mt-3">
      <a href="#">Términos de uso</a> | <a href="#">Política de privacidad</a>
    </div>
  </div>
</div>

<script src="public/js/login.js"></script>
