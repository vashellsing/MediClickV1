document.addEventListener("DOMContentLoaded", () => {
    const botonesReprogramar = document.querySelectorAll(".reprogramar-btn");

    botonesReprogramar.forEach(boton => {
        boton.addEventListener("click", (e) => {
            e.preventDefault();
            
            // Obtener datos de la cita
            const citaId = boton.dataset.citaId;
            const tipoCita = boton.dataset.tipoCita;
            const medicoId = boton.dataset.medicoId;
            const fechaCita = boton.dataset.fechaCita;
            const medicoNombre = boton.dataset.medicoNombre;

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
});