document.addEventListener("DOMContentLoaded", () => {
  const botonesReprogramar = document.querySelectorAll(".reprogramar-btn");

  botonesReprogramar.forEach(boton => {
    boton.addEventListener("click", (e) => {
      e.preventDefault();
      window.location.href = "index.php?page=agenda_general"; // redirige correctamente
    });
  });
});
