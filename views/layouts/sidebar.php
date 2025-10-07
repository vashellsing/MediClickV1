<?php
// sidebar.php
// Verifica que la variable $page esté definida antes de cargar este archivo.
$page = $page ?? 'dashboard';
$usuario = $_SESSION['usuario'] ?? 'Usuario';

function isActive($name, $page) {
    return $page === $name ? 'nav-link active' : 'nav-link text-dark';
}

// Controla si el submenú de "Agendar cita" debe mostrarse abierto o cerrado
$agendaOpen = in_array($page, ['agenda', 'agenda_general', 'agenda_especializacion'], true);
$agendaShowClass = $agendaOpen ? 'show' : '';
$agendaAriaExpanded = $agendaOpen ? 'true' : 'false';

// Primera letra del nombre del usuario (para usar en el futuro si se desea mostrar un avatar)
$initial = mb_strtoupper(mb_substr(trim($usuario), 0, 1, 'UTF-8'));
?>

<!--
  Este bloque es el MENÚ LATERAL para CELULARES o PANTALLAS PEQUEÑAS.
  En los celulares no se muestra fijo al lado, sino que aparece como una ventana que se desliza desde la izquierda.
  (A eso se le llama "offcanvas", pero básicamente es un panel que aparece cuando el usuario lo abre).
-->
<div class="offcanvas offcanvas-start d-md-none" tabindex="-1" id="offcanvasSidebar" aria-labelledby="offcanvasSidebarLabel">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title" id="offcanvasSidebarLabel">MediClick</h5>
    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Cerrar"></button>
  </div>
  <div class="offcanvas-body">
    <div class="px-2">
      <!-- Aquí empieza el contenido del menú que aparece al abrir el panel en el celular -->

      <hr class="my-2">

      <ul class="nav nav-pills flex-column mb-2">
        <li class="nav-item mb-1">
          <!-- Botón para ir a la página de inicio -->
          <a class="<?php echo isActive('dashboard', $page); ?> d-flex align-items-center gap-2 px-3 py-2 rounded" href="index.php?page=dashboard">
            <span>🏠</span><span class="flex-grow-1">Inicio</span>
          </a>
        </li>

        <li class="nav-item mb-1">
          <!--
            Botón que despliega o esconde las opciones de "Agendar cita".
            En celulares, estas opciones se pueden abrir o cerrar dentro del panel.
          -->
          <button class="btn d-flex align-items-center gap-2 w-100 px-3 py-2 rounded text-start <?php echo $agendaOpen ? '' : 'collapsed'; ?>"
                  data-bs-toggle="collapse"
                  data-bs-target="#agendaSubmenuMobile"
                  aria-expanded="<?php echo $agendaAriaExpanded; ?>"
                  aria-controls="agendaSubmenuMobile"
                  type="button"
                  style="background: transparent; border: none;">
            <span>📅</span><span class="flex-grow-1 text-dark">Agendar cita</span>
            <span class="caret <?php echo $agendaOpen ? 'rotated' : ''; ?>">▾</span>
          </button>

          <!-- Opciones dentro de "Agendar cita" -->
          <div class="collapse <?php echo $agendaShowClass; ?> ps-3" id="agendaSubmenuMobile">
            <ul class="nav flex-column mt-2">
              <li class="nav-item mb-1">
                <a class="<?php echo isActive('agenda_general', $page); ?> px-3 py-2 rounded" href="index.php?page=agenda_general">• General</a>
              </li>
              <li class="nav-item mb-1">
                <a class="<?php echo isActive('agenda_especializacion', $page); ?> px-3 py-2 rounded" href="index.php?page=agenda_especializacion">• Especialización</a>
              </li>
            </ul>
          </div>
        </li>

        <li class="nav-item mb-1">
          <!-- Botón para ir al historial -->
          <a class="<?php echo isActive('historial', $page); ?> d-flex align-items-center gap-2 px-3 py-2 rounded" href="index.php?page=historial">
            <span>📖</span><span class="flex-grow-1">Historial</span>
          </a>
        </li>
      </ul>

      <hr class="my-2">

      <!-- Aquí termina el contenido del menú para celulares -->
    </div>
  </div>
</div>


<!--
  Este bloque es el MENÚ LATERAL FIJO para COMPUTADORES o PANTALLAS GRANDES.
  En lugar de aparecer como un panel que se abre, este menú siempre se muestra fijo al lado izquierdo.
-->
<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-none d-md-block bg-light sidebar">
  <div class="position-sticky pt-3 px-2">
    <!-- Aquí empieza el contenido del menú fijo en pantallas grandes -->

    <hr class="my-2">

    <ul class="nav nav-pills flex-column mb-2">
      <li class="nav-item mb-1">
        <!-- Botón de inicio -->
        <a class="<?php echo isActive('dashboard', $page); ?> d-flex align-items-center gap-2 px-3 py-2 rounded" href="index.php?page=dashboard">
          <span>🏠</span><span class="flex-grow-1">Inicio</span>
        </a>
      </li>

      <li class="nav-item mb-1">
        <!-- Botón que muestra las opciones de "Agendar cita" -->
        <button class="btn d-flex align-items-center gap-2 w-100 px-3 py-2 rounded text-start <?php echo $agendaOpen ? '' : 'collapsed'; ?>"
                data-bs-toggle="collapse"
                data-bs-target="#agendaSubmenu"
                aria-expanded="<?php echo $agendaAriaExpanded; ?>"
                aria-controls="agendaSubmenu"
                type="button"
                style="background: transparent; border: none;">
          <span>📅</span><span class="flex-grow-1 text-dark">Agendar cita</span>
          <span class="caret <?php echo $agendaOpen ? 'rotated' : ''; ?>">▾</span>
        </button>

        <!-- Submenú dentro de "Agendar cita" -->
        <div class="collapse <?php echo $agendaShowClass; ?> ps-3" id="agendaSubmenu">
          <ul class="nav flex-column mt-2">
            <li class="nav-item mb-1">
              <a class="<?php echo isActive('agenda_general', $page); ?> px-3 py-2 rounded" href="index.php?page=agenda_general">• General</a>
            </li>
            <li class="nav-item mb-1">
              <a class="<?php echo isActive('agenda_especializacion', $page); ?> px-3 py-2 rounded" href="index.php?page=agenda_especializacion">• Especialización</a>
            </li>
          </ul>
        </div>
      </li>

      <li class="nav-item mb-1">
        <!-- Botón para ir al historial -->
        <a class="<?php echo isActive('historial', $page); ?> d-flex align-items-center gap-2 px-3 py-2 rounded" href="index.php?page=historial">
          <span>📖</span><span class="flex-grow-1">Historial</span>
        </a>
      </li>
    </ul>

    <hr class="my-2">

    <!-- Aquí termina el menú fijo para pantallas grandes -->
  </div>
</nav>
