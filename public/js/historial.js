document.addEventListener("DOMContentLoaded", () => {
  const cancelarBtns = document.querySelectorAll(".cancelar-btn");
  const confirmarBtn = document.getElementById("confirmarCancelar");
  let citaSeleccionada = null;

  cancelarBtns.forEach(btn => {
    btn.addEventListener("click", (e) => {
      // Previene cualquier comportamiento por defecto (submit, confirm nativo, etc.)
      e.preventDefault();
      e.stopPropagation();

      // En caso de que hubiera un atributo onclick con confirm() en el HTML, lo neutralizamos:
      try {
        if (btn.hasAttribute('onclick')) {
          btn.removeAttribute('onclick');
        }
      } catch (err) {
        // no hacer nada si falla
      }

      citaSeleccionada = btn.closest("tr");
      const modal = new bootstrap.Modal(document.getElementById("cancelarModal"));
      modal.show();
    });
  });

  confirmarBtn.addEventListener("click", (e) => {
    e.preventDefault();
    e.stopPropagation();

    if (citaSeleccionada) {
      // Simulación: cambiar badge y quitar botones de la fila
      const badge = citaSeleccionada.querySelector(".badge");
      if (badge) {
        badge.className = "badge bg-danger badge-fixed";
        badge.textContent = "Cancelada";
      }

      // quitar botones de acción (reprogramar y cancelar)
      const reprogramarLink = citaSeleccionada.querySelector("a.btn");
      const cancelarBtn = citaSeleccionada.querySelector(".cancelar-btn");
      if (reprogramarLink) reprogramarLink.remove();
      if (cancelarBtn) cancelBtn = cancelarBtn.remove?.call(cancelarBtn);

      // cerrar modal
      const modalEl = document.getElementById("cancelarModal");
      const modalInstance = bootstrap.Modal.getInstance(modalEl);
      if (modalInstance) modalInstance.hide();
    }
  });
});
