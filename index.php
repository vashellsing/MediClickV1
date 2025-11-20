<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/vendor/autoload.php';

$page = $_GET['page'] ?? 'login';
$error = '';

/* ===========================
   🚪 CERRAR SESIÓN
=========================== */
if ($page === 'logout') {
    session_unset();
    session_destroy();
    header("Location: index.php?page=login");
    exit;
}

/* ===========================
   🔑 PROCESAR LOGIN (SOLO PACIENTES)
=========================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $page === 'login') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    try {
        // Buscar paciente por correo
        $stmt = $conn->prepare("
            SELECT p.*, r.rol AS nombre_rol 
            FROM pacientes p
            JOIN roles r ON p.id_rol = r.id_rol
            WHERE p.correo = :email
            LIMIT 1
        ");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $paciente = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verificar si encontró paciente
        if ($paciente) {
            $cedulaBD = trim($paciente['cedula']);
            $passwordInput = trim($password);

            if ($passwordInput === $cedulaBD) {
                $_SESSION['usuario'] = $paciente['nombre'];
                $_SESSION['rol'] = $paciente['nombre_rol'];
                $_SESSION['id'] = $paciente['id_paciente'];

                header("Location: index.php?page=dashboard");
                exit;
            } else {
                $error = "Contraseña incorrecta. Usa tu número de cédula exactamente como está registrado.";
            }
        } else {
            $error = "No se encontró un usuario con ese correo electrónico.";
        }
    } catch (PDOException $e) {
        $error = "Error al conectar con la base de datos: " . $e->getMessage();
    }
}


/* ===========================
   🧱 LAYOUT GENERAL
=========================== */
include __DIR__ . '/views/layouts/header.php';
include __DIR__ . '/views/layouts/navbar.php';

echo '<div class="container-fluid"><div class="row">';

// Mostrar sidebar solo si hay sesión iniciada
if (!empty($_SESSION['usuario'])) {
    include __DIR__ . '/views/layouts/sidebar.php';
}

// Cargar página según parámetro
$viewFile = __DIR__ . "/views/pages/{$page}.php";
if (file_exists($viewFile)) {
    include $viewFile;
} else {
    echo "<main class='col-12'><h2 class='text-center mt-5 text-danger'>Página no encontrada</h2></main>";
}

echo '</div></div>';

include __DIR__ . '/views/layouts/footer.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);