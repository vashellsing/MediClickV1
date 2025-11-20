document.addEventListener("DOMContentLoaded", () => {
  const historialBody = document.getElementById("historialBody");
  const loadingHistorial = document.getElementById("loadingHistorial");
  const historialContent = document.getElementById("historialContent");
  const emptyHistorial = document.getElementById("emptyHistorial");
  
  const confirmarBtn = document.getElementById("confirmarCancelar");
  let citaSeleccionada = null;
  let currentCitas = []; // Para comparar cambios

  // Cargar historial al iniciar
  loadHistorial();

  async function loadHistorial() {
    try {
      console.log('Cargando historial...');
      const response = await fetch('api/get_historial.php');
      
      if (!response.ok) {
        throw new Error(`Error HTTP: ${response.status}`);
      }
      
      const citas = await response.json();
      console.log('Datos recibidos:', citas);

      // Ocultar loading
      if (loadingHistorial) loadingHistorial.style.display = 'none';
      if (historialContent) historialContent.style.display = 'block';
      
      if (Array.isArray(citas) && citas.length > 0) {
        // Solo renderizar si hay cambios reales
        if (JSON.stringify(citas) !== JSON.stringify(currentCitas)) {
          currentCitas = citas;
          renderHistorial(citas);
        }
        if (emptyHistorial) emptyHistorial.style.display = 'none';
      } else {
        if (historialBody) historialBody.innerHTML = '';
        currentCitas = [];
        if (emptyHistorial) emptyHistorial.style.display = 'block';
        console.log('No hay citas para mostrar');
      }
    } catch (error) {
      console.error('Error cargando historial:', error);
      if (loadingHistorial) {
        loadingHistorial.innerHTML = `
          <div class="alert alert-danger">
            <p>Error al cargar el historial: ${error.message}</p>
            <button class="btn btn-sm btn-outline-secondary" onclick="location.reload()">Reintentar</button>
          </div>
        `;
      }
    }
  }

  function renderHistorial(citas) {
    if (!historialBody) return;
    
    historialBody.innerHTML = '';

    citas.forEach(cita => {
      const row = document.createElement('tr');
      
      // Determinar clase y texto según estado
      let estadoClass = '';
      let estadoText = '';
      // En renderHistorial, agregar el estado REPROGRAMADA:
switch(cita.estado) {
    case 'CONFIRMADA':
        estadoClass = 'bg-primary';
        estadoText = 'Agendada';
        break;
    case 'PENDIENTE':
        estadoClass = 'bg-warning text-dark';
        estadoText = 'Pendiente';
        break;
    case 'CANCELADA':
        estadoClass = 'bg-danger';
        estadoText = 'Cancelada';
        break;
    case 'REPROGRAMADA':
        estadoClass = 'bg-info';
        estadoText = 'Reprogramada';
        break;
    case 'REALIZADA':
        estadoClass = 'bg-success';
        estadoText = 'Completada';
        break;
    default:
        estadoClass = 'bg-secondary';
        estadoText = cita.estado || 'Desconocido';
}

// En mostrarAcciones, excluir REPROGRAMADA:
const mostrarAcciones = (cita.estado === 'CONFIRMADA' || cita.estado === 'PENDIENTE');

      // Formatear fecha y hora de manera segura
      let fecha = 'Fecha no disponible';
      let hora = 'Hora no disponible';
      
      try {
        if (cita.fecha_hora_cita) {
          const fechaHora = new Date(cita.fecha_hora_cita);
          if (!isNaN(fechaHora.getTime())) {
            fecha = fechaHora.toLocaleDateString('es-ES');
            hora = fechaHora.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
          }
        }
      } catch (e) {
        console.error('Error formateando fecha:', e);
      }
           row.innerHTML = `
          <td>${fecha}</td>
          <td>${cita.tipo_cita || 'Especialización'}</td>
          <td>${cita.medico_completo || cita.medico_nombre || 'Médico no disponible'}</td>
          <td>${hora}</td>
          <td><span class="badge ${estadoClass} badge-fixed">${estadoText}</span></td>
          <td>
              ${mostrarAcciones ? `
                  <button type="button" 
                          class="btn btn-outline-primary btn-sm reprogramar-btn" 
                          data-cita-id="${cita.id_cita}"
                          data-tipo-cita="${cita.tipo_cita}"
                          data-medico-id="${cita.id_medico}"
                          data-fecha-cita="${cita.fecha_hora_cita}"
                          data-medico-nombre="${cita.medico_completo}">
                      Reprogramar
                  </button>
                  <button type="button" class="btn btn-outline-danger btn-sm cancelar-btn" data-cita-id="${cita.id_cita}">
                      Cancelar
                  </button>
              ` : '—'}
          </td>
      `;

      historialBody.appendChild(row);
    });

    // Re-asignar eventos a los botones de cancelar
    assignCancelEvents();
    assignReprogramarEvents();
  }

  function assignCancelEvents() {
  const cancelarBtns = document.querySelectorAll(".cancelar-btn");
  
  cancelarBtns.forEach(btn => {
    btn.addEventListener("click", (e) => {
      e.preventDefault();
      e.stopPropagation();

      citaSeleccionada = btn.closest("tr");
      const citaIdInput = document.getElementById('citaIdToCancel');
      if (citaIdInput) {
        citaIdInput.value = btn.dataset.citaId;
      }
      
      const modal = new bootstrap.Modal(document.getElementById("cancelarModal"));
      modal.show();
    });
  });
}

function assignReprogramarEvents() {
  const reprogramarBtns = document.querySelectorAll(".reprogramar-btn");
  
  reprogramarBtns.forEach(btn => {
    btn.addEventListener("click", (e) => {
      e.preventDefault();
      e.stopPropagation();
      
      // Obtener datos de la cita
      const citaId = btn.dataset.citaId;
      const tipoCita = btn.dataset.tipoCita;
      const medicoId = btn.dataset.medicoId;
      const fechaCita = btn.dataset.fechaCita;
      const medicoNombre = btn.dataset.medicoNombre;

      console.log('Reprogramando cita:', { citaId, tipoCita, medicoId, fechaCita, medicoNombre });

      // Guardar datos en sessionStorage para usar en la página de destino
      sessionStorage.setItem('citaReprogramar', JSON.stringify({
        id: citaId,
        tipo: tipoCita,
        medicoId: medicoId,
        fechaOriginal: fechaCita,
        medicoNombre: medicoNombre
      }));

      // Redirigir según el tipo de cita
      if (tipoCita === 'General') {
        window.location.href = "index.php?page=agenda_general";
      } else {
        window.location.href = "index.php?page=agenda_especializacion";
      }
    });
  });
}

  // Solo agregar el event listener si el botón existe
  if (confirmarBtn) {
    confirmarBtn.addEventListener("click", async (e) => {
      e.preventDefault();
      e.stopPropagation();

      if (citaSeleccionada) {
        const citaIdInput = document.getElementById('citaIdToCancel');
        const citaId = citaIdInput ? citaIdInput.value : null;
        
        if (!citaId) {
          alert('No se pudo obtener el ID de la cita');
          return;
        }

        try {
          // Llamar a la API para cancelar la cita
          const response = await fetch('api/cancelar_cita.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
            },
            body: JSON.stringify({ cita_id: citaId })
          });

          const result = await response.json();

          if (result.success) {
            // Recargar el historial completo para ver los cambios
            await loadHistorial();
            
            // ✅ NOTIFICAR AL NAVBAR QUE HAY NUEVAS NOTIFICACIONES
            if (window.notifyNewAppointment) {
              window.notifyNewAppointment();
            }
            
            // Mostrar notificación toast
            if (window.notificationManager) {
              window.notificationManager.showToast('Cita cancelada exitosamente', 'info');
            }
          } else {
            alert('Error al cancelar la cita: ' + (result.message || 'Error desconocido'));
          }
        } catch (error) {
          console.error('Error:', error);
          alert('Error al conectar con el servidor');
        }

        // Cerrar modal
        const modalEl = document.getElementById("cancelarModal");
        const modalInstance = bootstrap.Modal.getInstance(modalEl);
        if (modalInstance) modalInstance.hide();
      }
    });
  }
  // Recargar historial cada 20 segundos
  setInterval(loadHistorial, 20000); 
});