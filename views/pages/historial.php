<?php
if (!isset($_SESSION['usuario'])) {
    header('Location: index.php?page=login');
    exit;
}
?>

<!-- Contenido principal -->
<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">

  <h1 class="h3 mb-4">Historial</h1>

  <!-- Tabla de ejemplo -->
<table class="table table-striped">
  <thead>
    <tr>
      <th scope="col">Fecha</th>
      <th scope="col">Tipo Cita</th>
      <th scope="col">Medico</th>
      <th scope="col">Hora</th>
      <th scope="col">Estado</th>
      <th scope="col"></th>  
    </tr>
  </thead>
  <tbody>
  <tr>
    <td>5/10/2025</td>
    <td>Especializacion</td>
    <td>Dra.Maria Lopez</td>
    <td>3:00</td>
    <td class="bg-primary text blark">Agendado</td>
    <td>
      <a href="index.php?page=agenda_general" class="btn btn-primary btn-sm reprogramar-btn">Reprogramar</a> 
      <button class="btn btn-danger btn-sm cancelar-btn">Cancelar</button>
    </td>
  </tr>

  <tr>
    <td>5/10/2025</td>
    <td>Especializacion</td>
    <td>Dra.Maria Lopez</td>
    <td>3:00</td>
    <td class="bg-primary text blark">Agendado</td>
    <td>
      <a href="index.php?page=agenda_general" class="btn btn-primary btn-sm reprogramar-btn">Reprogramar</a> 
      <button class="btn btn-danger btn-sm cancelar-btn">Cancelar</button>
    </td>
  </tr>

  <tr>
  <td>15/10/2025</td>
  <td>General</td>
  <td>Dra.Maria Lopez</td>
  <td>3:00</td>
  <td class="bg-info text-blark">Reprogramada</td>
  <td>
    <a href="index.php?page=agenda_general" class="btn btn-primary btn-sm reprogramar-btn">Reprogramar</a> 
    <button class="btn btn-danger btn-sm cancelar-btn">Cancelar</button>
  </td>
</tr>

  <tr>
    <td>6/10/2025</td>
    <td>Especializacion</td>
    <td>Dra.Ana Tores</td>
    <td>7:40</td>
    <td class="bg-danger text-blark">Cancelada</td>
    <td></td>
  </tr>

  <tr>
    <td>8/10/2025</td>
    <td>General</td>
    <td>Dr. Carlos Ramirez</td>
    <td>10:30</td>
    <td class="bg-success text-blark">Completada</td>
    <td></td>
  </tr>
  </tbody>
</table>


