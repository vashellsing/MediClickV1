<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
  <div class="text-center my-5">
    <h1 class="fw-bold text-primary mb-3">🩺 Tu salud a un click</h1>
    <p class="lead mb-4">Bienvenido al sistema de agendamiento de citas médicas <strong>MediClick</strong>.</p>

    <?php if (!empty($_SESSION['usuario'])): ?>
      <h4 class="mb-3">Hola, <strong><?php echo htmlspecialchars($_SESSION['usuario']); ?></strong> 👋</h4>
    <?php endif; ?>

    <p class="text-muted">Desde aquí podrás agendar tus citas, revisar tu historial y gestionar tu perfil de usuario.</p>

    <div class="text-center mt-5">
      <img src="public/img/Portada.png" alt="Salud" class="img-fluid" style="max-width: 360px; opacity: 0.9;">
    </div>
  </div>
</main>
