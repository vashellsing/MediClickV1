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
          <tbody class="text-center">
            <tr>
              <td>30/10/2025</td>
              <td>General</td>
              <td>Dra. María López</td>
              <td>15:00</td>
              <td><span class="badge bg-primary badge-fixed">Agendada</span></td>
              <td>
                <a href="index.php?page=agenda_general" class="btn btn-outline-primary btn-sm">Reprogramar</a> 
                <!-- type="button" agregado -->
                <button type="button" class="btn btn-outline-danger btn-sm cancelar-btn">Cancelar</button>
              </td>
            </tr>

            <tr>
              <td>30/10/2025</td>
              <td>Pediatria</td>
              <td>Dra. Juana Campos</td>
              <td>16:00</td>
              <td><span class="badge bg-primary badge-fixed">Agendada</span></td>
              <td>
                <a href="index.php?page=agenda_general" class="btn btn-outline-primary btn-sm">Reprogramar</a> 
                <button type="button" class="btn btn-outline-danger btn-sm cancelar-btn">Cancelar</button>
              </td>
            </tr>

            <tr>
              <td>15/10/2025</td>
              <td>General</td>
              <td>Dra. María López</td>
              <td>15:00</td>
              <td><span class="badge bg-info text-dark badge-fixed">Reprogramada</span></td>
              <td>—</td>
            </tr>

            <tr>
              <td>06/10/2025</td>
              <td>Odontología</td>
              <td>Dra. Ana Torres</td>
              <td>07:40</td>
              <td><span class="badge bg-danger badge-fixed">Cancelada</span></td>
              <td>—</td>
            </tr>

            <tr>
              <td>08/10/2025</td>
              <td>General</td>
              <td>Dr. Carlos Ramírez</td>
              <td>10:30</td>
              <td><span class="badge bg-success badge-fixed">Completada</span></td>
              <td>—</td>
            </tr>
          </tbody>
        </table>
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
        <p class="small text-muted mb-0">Al confirmar, la cita pasará a estado <em>Cancelada</em> (simulación).</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No</button>
        <!-- type="button" por seguridad -->
        <button type="button" class="btn btn-danger" id="confirmarCancelar">Sí, cancelar</button>
      </div>
    </div>
  </div>
</div>