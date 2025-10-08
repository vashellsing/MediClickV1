<?php
$usuario = $_SESSION['usuario'] ?? null;
?>
<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom shadow-sm">
  <div class="container-fluid">

    <!-- Botón del menú lateral en pantallas pequeñas -->
    <button class="btn btn-sm btn-outline-secondary me-2 d-md-none" type="button"
      data-bs-toggle="offcanvas" data-bs-target="#offcanvasSidebar" aria-controls="offcanvasSidebar">
      ☰
    </button>

    <!-- Logo e ícono del proyecto -->
    <a class="navbar-brand d-flex align-items-center" href="#">
      <img src="public/img/icono.png" alt="MediClick" width="32" height="32" class="me-2">
      <span class="fw-semibold">MediClick</span>
    </a>

    <div class="d-flex align-items-center">

      <?php if ($usuario): ?>

        <!-- 🔔 Campana de notificaciones -->
        <div class="dropdown me-3">
          <button class="btn btn-outline-secondary position-relative" id="notificationsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
            🔔
            <span id="notificationCount" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
              0
            </span>
          </button>

          <!-- Ventana desplegable de notificaciones -->
          <ul class="dropdown-menu dropdown-menu-end p-2 shadow" aria-labelledby="notificationsDropdown"
              id="notificationsList"
              style="min-width: 300px; max-height: 350px; overflow-y:auto; border-radius: 0.6rem;">
            <li class="dropdown-header fw-bold text-primary">Notificaciones</li>
            <li><hr class="dropdown-divider"></li>
            <!-- JS llenará las notificaciones aquí -->
          </ul>
        </div>

        <!-- 👤 Usuario y botón de salida -->
        <span class="navbar-text me-3 text-secondary">
          👤 <?php echo htmlspecialchars($usuario, ENT_QUOTES); ?>
        </span>
        <a href="index.php?page=logout" class="btn btn-outline-danger btn-sm">Salir</a>

      <?php else: ?>
        <a href="index.php?page=login" class="btn btn-outline-primary btn-sm">Iniciar sesión</a>
      <?php endif; ?>

    </div>
  </div>
</nav>

<!-- JS del navbar -->
<script src="public/js/navbar.js"></script>
