<?php
$usuario = $_SESSION['usuario'] ?? null;
?>
<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
  <div class="container-fluid">
    <button class="btn btn-sm btn-outline-secondary me-2 d-md-none" type="button"
      data-bs-toggle="offcanvas" data-bs-target="#offcanvasSidebar" aria-controls="offcanvasSidebar">
      ☰
    </button>
    <a class="navbar-brand" href="#">MediClick</a>

    <div class="d-flex align-items-center">
      <?php if ($usuario): ?>
        <!-- Campana de notificaciones -->
        <!-- Campana de notificaciones -->
        <div class="dropdown me-3">
          <button class="btn btn-outline-secondary position-relative" id="notificationsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
            🔔
            <span id="notificationCount" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
              0
            </span>
          </button>
          <ul class="dropdown-menu dropdown-menu-end p-2" aria-labelledby="notificationsDropdown" id="notificationsList" style="min-width: 300px; max-height: 350px; overflow-y:auto; border-radius: 0.6rem;">
            <li class="dropdown-header fw-bold">Notificaciones</li>
            <li>
              <hr class="dropdown-divider">
            </li>
            <!-- JS llenará las notificaciones aquí -->
          </ul>
        </div>


        <span class="navbar-text me-3">
          👤 <?php echo htmlspecialchars($usuario, ENT_QUOTES); ?>
        </span>
        <a href="index.php?page=logout" class="btn btn-outline-danger btn-sm">Salir</a>
      <?php else: ?>
        <a href="index.php?page=login" class="btn btn-outline-primary btn-sm">Iniciar sesión</a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<!-- Vincular JS del navbar -->
<script src="public/js/navbar.js"></script>