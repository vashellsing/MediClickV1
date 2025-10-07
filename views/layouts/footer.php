<?php
// footer.php
// Footer simple, informativo y sticky (usa mt-auto para quedarse al final)
?>
  <!-- Footer -->
  <footer class="mt-auto bg-light text-muted border-top">
    <div class="container py-3">
      <div class="row align-items-center">
        <div class="col-md-5 text-center text-md-start mb-2 mb-md-0">
          <strong>MediClick</strong><br>
          <small>Tu sistema de agendamiento de citas médicas</small>
        </div>

        <div class="col-md-4 text-center mb-2 mb-md-0">
          <!-- Contactos -->
          <div class="small">
            <div>📞 <a href="tel:+571234567890" class="link-secondary text-decoration-none">+57 1 234 567 890</a></div>
            <div>✉️ <a href="mailto:soporte@mediclick.com" class="link-secondary text-decoration-none">soporte@mediclick.com</a></div>
          </div>
        </div>

        <div class="col-md-3 text-center text-md-end">
          <!-- Redes sociales (usa enlaces reales cuando las tengas) -->
          <a href="#" class="mx-1 text-decoration-none link-secondary" aria-label="Facebook">🔵 Facebook</a>
          <a href="#" class="mx-1 text-decoration-none link-secondary" aria-label="Instagram">📸 Instagram</a>
          <a href="#" class="mx-1 text-decoration-none link-secondary" aria-label="Twitter">🐦 Twitter</a>
        </div>
      </div>

      <div class="row">
        <div class="col-12 text-center small mt-3">
          &copy; <?php echo date('Y'); ?> MediClick. Todos los derechos reservados.
        </div>
      </div>
    </div>
  </footer>

  <!-- Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="public/js/main.js"></script>
  <script src="public/js/cancelar.js"></script>
  <script src="public/js/reprogramar.js"></script>
  <script src="public/js/historial.js"></script>
  <script src="public/js/login.js"></script>

</body>
</html>

