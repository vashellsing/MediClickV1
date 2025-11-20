<?php
$usuario = $_SESSION['usuario'] ?? null;

// Obtener notificaciones directamente desde PHP
$notificaciones = [];
$totalNoLeidas = 0;

if ($usuario) {
    try {
        require_once 'config/database.php';
        
        // Por ahora usamos paciente_id = 1, luego lo cambiarás por el ID real del usuario logueado
        $paciente_id = 1;
        
        $stmt = $conn->prepare("
            SELECT id_notificacion, mensaje, fecha_creacion, leida
            FROM notificacion 
            WHERE id_paciente = ? 
            ORDER BY fecha_creacion DESC, id_notificacion DESC
            LIMIT 10
        ");
        $stmt->execute([$paciente_id]);
        $notificaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Contar notificaciones no leídas
        $stmt = $conn->prepare("
            SELECT COUNT(*) as total 
            FROM notificacion 
            WHERE id_paciente = ? AND leida = 'no'
        ");
        $stmt->execute([$paciente_id]);
        $totalNoLeidas = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
    } catch (Exception $e) {
        error_log("Error cargando notificaciones: " . $e->getMessage());
    }
}
?>

<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom shadow-sm">
  <div class="container-fluid">

    <!-- Botón del menú lateral en pantallas pequeñas -->
    <button class="btn btn-sm btn-outline-secondary me-2 d-md-none" type="button"
      data-bs-toggle="offcanvas" data-bs-target="#offcanvasSidebar" aria-controls="offcanvasSidebar">
      ☰
    </button>

    <!-- Logo e ícono del proyecto -->
    <a class="navbar-brand d-flex align-items-center" href="index.php?page=dashboard">
      <img src="public/img/icono.png" alt="MediClick" width="32" height="32" class="me-2">
      <span class="fw-semibold">MediClick</span>
    </a>

    <div class="d-flex align-items-center">

      <?php if ($usuario): ?>

        <!-- 🔔 Campana de notificaciones -->
        <div class="dropdown me-3">
          <button class="btn btn-outline-secondary position-relative" id="notificationsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
            🔔
            <?php if ($totalNoLeidas > 0): ?>
            <span id="notificationCount" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
              <?php echo $totalNoLeidas; ?>
            </span>
            <?php endif; ?>
          </button>

          <!-- Ventana desplegable de notificaciones -->
          <ul class="dropdown-menu dropdown-menu-end p-2 shadow" aria-labelledby="notificationsDropdown"
              style="min-width: 320px; max-height: 400px; overflow-y:auto; border-radius: 0.6rem;">
            <li class="dropdown-header fw-bold text-primary">Notificaciones</li>
            <li><hr class="dropdown-divider"></li>
            
            <?php if (empty($notificaciones)): ?>
              <li class="dropdown-item text-center text-muted">
                <small>No hay notificaciones</small>
              </li>
            <?php else: ?>
              <?php foreach ($notificaciones as $notif): ?>
                <?php
                // Formatear fecha
                $fecha_formateada = '';
                if (!empty($notif['fecha_creacion'])) {
                    $fecha = new DateTime($notif['fecha_creacion']);
                    $ahora = new DateTime();
                    $diferencia = $ahora->diff($fecha);
                    
                    if ($diferencia->d == 0) {
                        if ($diferencia->h == 0) {
                            if ($diferencia->i == 0) {
                                $fecha_formateada = 'Ahora mismo';
                            } else {
                                $fecha_formateada = 'Hace ' . $diferencia->i . ' min';
                            }
                        } else {
                            $fecha_formateada = 'Hace ' . $diferencia->h . ' h';
                        }
                    } else if ($diferencia->d == 1) {
                        $fecha_formateada = 'Ayer ' . $fecha->format('H:i');
                    } else {
                        $fecha_formateada = $fecha->format('d/m/Y');
                    }
                }
                
                // Determinar ícono
                $icono = '📄';
                if (strpos($notif['mensaje'], 'agendada') !== false || strpos($notif['mensaje'], 'exitosa') !== false) {
                    $icono = '✅';
                } else if (strpos($notif['mensaje'], 'cancelada') !== false) {
                    $icono = '❌';
                } else if (strpos($notif['mensaje'], 'reprogramada') !== false) {
                    $icono = '🔄';
                }
                
                // Clase para no leídas
                $clase_no_leida = $notif['leida'] === 'no' ? 'fw-bold' : '';
                ?>
                <li>
                  <a href="index.php?page=historial" class="dropdown-item <?php echo $clase_no_leida; ?>">
                    <div class="d-flex align-items-start">
                      <span class="me-2"><?php echo $icono; ?></span>
                      <div class="flex-grow-1">
                        <div class="small"><?php echo htmlspecialchars($notif['mensaje']); ?></div>
                        <small class="text-muted"><?php echo $fecha_formateada; ?></small>
                      </div>
                      <?php if ($notif['leida'] === 'no'): ?>
                        <span class="badge bg-danger ms-2">Nueva</span>
                      <?php endif; ?>
                    </div>
                  </a>
                </li>
              <?php endforeach; ?>
            <?php endif; ?>
            
            <li><hr class="dropdown-divider"></li>
            <li>
              <a class="dropdown-item text-center small" href="index.php?page=historial">
                Ver historial completo
              </a>
            </li>
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

<!-- JS del navbar para actualizaciones en tiempo real -->
<script src="public/js/navbar.js"></script>