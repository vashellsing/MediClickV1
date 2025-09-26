<?php

//  luego de que el index verifique si hay o no sesion, esto hace que en el nav aparezca el nombre  quien inicio sesion... si no hay activo no muestra nada
$usuario = $_SESSION['usuario'] ?? null;
?>
<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
  <div class="container-fluid">
    <a class="navbar-brand" href="index.php">MediClick</a>
    <div class="d-flex">
      <?php if ($usuario): ?>
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
