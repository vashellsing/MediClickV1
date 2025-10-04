document.addEventListener("DOMContentLoaded", () => {
  const botonesCancelar = document.querySelectorAll(".cancelar-btn");

  botonesCancelar.forEach(boton => {
    boton.addEventListener("click", () => {
      if (confirm("¿Estás seguro que deseas cancelar la cita?")) {
        const fila = boton.closest("tr");

        // Estado = 5ª columna (índice 4 en array)
        const celdaEstado = fila.cells[4]; 
        celdaEstado.textContent = "Cancelada";
        celdaEstado.classList.add("bg-danger", "text-black");
        // Acciones = última columna
        const celdaAcciones = fila.cells[5];
        celdaAcciones.innerHTML = "";
      }
    });
  });
});
