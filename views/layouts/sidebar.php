<?php
//Se supone deberia mostrar el link activo, pero no lo pude hacer funcionar, creo jaja no se que mefalta
$activeClass = function($name) use ($page) {
    return $page === $name ? 'nav-link active' : 'nav-link';
};

// el submenu siempre esta contraido, al darle click se abre las opciones
$agendaOpen = in_array($page, ['agenda', 'agenda_general', 'agenda_especializacion'], true);
$agendaShowClass = $agendaOpen ? 'show' : '';
$agendaAriaExpanded = $agendaOpen ? 'true' : 'false';
?>
<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
  <div class="position-sticky pt-3">
    <ul class="nav flex-column">

      <li class="nav-item">
        <a class="<?php echo $activeClass('dashboard'); ?>" aria-current="page" href="index.php?page=dashboard">
          🏠 Inicio
        </a>
      </li>

      <!-- Agendar cita: item con el submenu de bootstrap -->
      <li class="nav-item">
        <!-- bototn que controla el menu desplegable, la flehita hacia abajo xD -->
        <button class="nav-link btn btn-link text-start w-100 d-flex justify-content-between align-items-center"
                data-bs-toggle="collapse"
                data-bs-target="#agendaSubmenu"
                aria-expanded="<?php echo $agendaAriaExpanded; ?>"
                aria-controls="agendaSubmenu"
                type="button"
                style="text-decoration: none;">
          <span>📅 Agendar cita</span>
          <span class="small"><?php echo $agendaOpen ? '▾' : '▸'; ?></span>
        </button>

        <div class="collapse <?php echo $agendaShowClass; ?> ps-3" id="agendaSubmenu">
          <ul class="nav flex-column">
            <li class="nav-item">
              <a class="<?php echo $activeClass('agenda_general'); ?>" href="index.php?page=agenda_general">
                • General
              </a>
            </li>
            <li class="nav-item">
              <a class="<?php echo $activeClass('agenda_especializacion'); ?>" href="index.php?page=agenda_especializacion">
                • Especialización
              </a>
            </li>
          </ul>
        </div>
      </li>

      <li class="nav-item">
        <a class="<?php echo $activeClass('historial'); ?>" href="index.php?page=historial">
          📖 Historial
        </a>
      </li>
    </ul>
  </div>
</nav>
