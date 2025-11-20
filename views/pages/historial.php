<?php
if (!isset($_SESSION['usuario'])) {
    header('Location: index.php?page=login');
    exit;
}
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">

  <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center border-bottom mb-4">
    <h1 class="h3">📖 Historial de Citas</h1>
  </div>

  <div class="card shadow-sm border-0">
    <div class="card-body">
      <p class="text-muted mb-4">Aquí puedes ver tus citas pasadas y futuras, con la posibilidad de reprogramarlas o cancelarlas si están pendientes.</p>
      
      <!-- Loading state -->
      <div id="loadingHistorial" class="text-center py-4">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Cargando...</span>
        </div>
        <p class="mt-2">Cargando historial de citas...</p>
      </div>

      <div id="historialContent" style="display: none;">
        <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead class="table-primary text-center">
              <tr>
                <th scope="col">Fecha</th>
                <th scope="col">Tipo de Cita</th>
                <th scope="col">Médico</th>
                <th scope="col">Hora</th>
                <th scope="col">Estado</th>
                <th scope="col">Acciones</th>  
              </tr>
            </thead>
            <tbody class="text-center" id="historialBody">
              <!-- Los datos se cargarán via JavaScript -->
            </tbody>
          </table>
        </div>
        
        <!-- Empty state -->
        <div id="emptyHistorial" class="text-center py-4" style="display: none;">
          <p class="text-muted mb-3">No tienes citas agendadas.</p>
          <a href="index.php?page=agenda_especializacion" class="btn btn-primary">Agendar primera cita</a>
        </div>
      </div>
    </div>
  </div>

</main>

<!-- Modal de confirmación de cancelación -->
<div class="modal fade" id="cancelarModal" tabindex="-1" aria-labelledby="cancelarModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="cancelarModalLabel">Cancelar cita</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <p class="mb-1">¿Estás seguro de que deseas <strong>cancelar esta cita médica</strong>?</p>
        <p class="small text-muted mb-0">Al confirmar, la cita pasará a estado <em>Cancelada</em>.</p>
        <input type="hidden" id="citaIdToCancel">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No</button>
        <button type="button" class="btn btn-danger" id="confirmarCancelar">Sí, cancelar</button>
      </div>
    </div>
  </div>
</div>

<script src="public/js/historial.js"></script>