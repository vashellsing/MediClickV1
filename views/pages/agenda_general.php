<?php
// Si no hay sesión activa, redirige al inicio de sesión
if (!isset($_SESSION['usuario'])) {
    header('Location: index.php?page=login');
    exit;
}
?>

<!-- Contenido principal -->
<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">

  <h1 class="h3 mb-4">Agendar cita - General</h1>

  <!-- Pestañas -->
  <ul class="nav nav-tabs mb-4" id="appointmentTabs" role="tablist">
    <li class="nav-item" role="presentation">
      <button class="nav-link active" id="step1-tab" data-bs-toggle="tab" data-bs-target="#step1" type="button" role="tab">
        Horario disponible
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="step2-tab" data-bs-toggle="tab" data-bs-target="#step2" type="button" role="tab">
        Confirmar reserva
      </button>
    </li>
  </ul>

  <div class="tab-content" id="appointmentTabsContent">
    <!-- Paso 1 -->
    <div class="tab-pane fade show active" id="step1" role="tabpanel">
      <div class="row">
        
        <!-- 🔹 INSTRUCCIONES -->
        <!-- En pantallas pequeñas aparecerá primero (order-1), 
             y en pantallas grandes se mueve a la derecha (order-lg-2) -->
        <div class="col-lg-5 order-1 order-lg-2 mb-3 mb-lg-0">
          <div class="card">
            <div class="card-body text-center">
              <img src="public/img/General.png?height=300&width=300" 
                   alt="Doctor y paciente" 
                   class="img-fluid mb-3" 
                   style="max-height:220px;">
              <h6 class="mb-1">Instrucciones</h6>
              <p class="small text-muted">
                Elige un día del calendario, selecciona un profesional (opcional) 
                y luego un horario. Haz clic en "Continuar" para confirmar.
              </p>
            </div>
          </div>
        </div>

        <!-- 🔹 CONTENIDO PRINCIPAL -->
        <!-- En pantallas pequeñas aparecerá después (order-2), 
             y en pantallas grandes estará a la izquierda (order-lg-1) -->
        <div class="col-lg-7 order-2 order-lg-1">
          <h2 class="h5 mb-3">Seleccione día y hora deseada</h2>

          <!-- Calendario -->
          <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
              <div>
                <button class="btn btn-outline-secondary btn-sm me-2" id="prevMonth">&laquo;</button>
                <button class="btn btn-outline-secondary btn-sm" id="nextMonth">&raquo;</button>
              </div>
              <h5 class="mb-0" id="currentMonth"></h5>
              <div></div>
            </div>
            <div class="card-body">
              <!-- Días de la semana -->
              <div class="row row-cols-7 text-center small mb-2">
                <div class="col">Lun</div><div class="col">Mar</div><div class="col">Mié</div>
                <div class="col">Jue</div><div class="col">Vie</div><div class="col">Sáb</div>
                <div class="col">Dom</div>
              </div>
              <div id="calendarGrid" class="row row-cols-7 g-2"></div>
            </div>
          </div>

          <!-- Selección de médico -->
          <div class="card mb-4">
            <div class="card-header">Seleccionar profesional</div>
            <div class="card-body">
              <div class="input-group mb-3">
                <input type="text" id="doctorSearch" placeholder="Buscar médico..." class="form-control" aria-label="Buscar médico">
                <button class="btn btn-outline-secondary" id="btnDoctorSearch" type="button">Buscar</button>
              </div>
              <div id="doctorList" class="list-group mb-3" style="max-height:220px; overflow:auto;"></div>
              <button class="btn btn-outline-warning w-100" id="omitDoctor">
                Omitir (buscar cualquier profesional)
              </button>
            </div>
          </div>

          <!-- Texto adicional -->
  
          <!-- Horarios disponibles -->
          <div class="card mb-4">
            <div class="card-header">Horarios disponibles</div>
            <div class="card-body">
              <p class="mb-2">
                <span class="badge bg-success">Disponible</span>
                <span class="badge bg-danger">Ocupado</span>
                <span class="badge bg-primary">Seleccionado</span>
              </p>
              <div id="scheduleGrid" class="d-flex flex-wrap gap-2"></div>
            </div>
          </div>

          <!-- Botones -->
          <div class="d-flex justify-content-between">
            <a href="index.php?page=dashboard" class="btn btn-secondary">Volver</a>
            <button class="btn btn-primary" id="continueBtn">Continuar</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Paso 2 -->
    <div class="tab-pane fade" id="step2" role="tabpanel">
      <div class="row">

        <!-- 🔹 En este paso, también se puede controlar el orden de los elementos 
             para que la imagen salga primero en pantallas pequeñas -->
        <div class="col-lg-5 order-1 order-lg-2 mb-3 mb-lg-0 text-center">
          <img src="public/img/General.png?height=400&width=400" 
               alt="Doctor y paciente" 
               class="img-fluid">
        </div>

        <div class="col-lg-7 order-2 order-lg-1">
          <h2 class="h5 mb-3">Por favor, confirme la cita</h2>

          <div class="card mb-4">
            <div class="card-body">
              <p><strong>Nombre médico:</strong> <span id="confirmDoctor">__________</span></p>
              <p><strong>Tipo de cita:</strong> <span id="confirmType">General</span></p>
              <p><strong>Horario seleccionado:</strong> <span id="confirmSchedule">__________</span></p>
              <input type="hidden" id="confirmDate" value="">
              <input type="hidden" id="confirmDoctorId" value="">
            </div>
          </div>

          <div class="d-flex justify-content-between">
            <button class="btn btn-secondary" id="backBtn">Volver</button>
            <button class="btn btn-success" id="confirmBtn">Confirmar</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>

<!-- Modal -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content text-center p-4">
      <div class="mb-3 text-success">
        <i class="fas fa-check-circle fa-3x"></i>
      </div>
      <h5 class="modal-title mb-2">¡Cita agendada exitosamente!</h5>
      <p class="mb-4">Su cita ha sido registrada correctamente (simulado en front).</p>
      <a href="index.php?page=dashboard" class="btn btn-primary">Aceptar</a>
    </div>
  </div>
</div>

<!-- Script de funcionamiento -->
<script src="public/js/agendar-general.js"></script>
