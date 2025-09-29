<?php
//esto simula la sesion con php, sigue siendo front
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$page = $_GET['page'] ?? 'login';

// ayuda a cerrar sesion
if ($page === 'logout') {
    // Limpia variables de sesión
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
    header("Location: index.php?page=login");
    exit;
}

// Inicializa variable de error, para mostrar errores en validacion, se puede reutilizar en cualquier formulario
$error = '';

// Procesar login (POST desde index.php?page=login)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $page === 'login') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // Comprobación simulada (mas adelante reemplazar por BD)
    if ($email === 'admin@mediclick.com' && $password === '12345678') {
        $_SESSION['usuario'] = 'Juan Pérez';
        header("Location: index.php?page=dashboard");
        exit;
    } else {
        $error = "Credenciales incorrectas";
    }
}

// Incluye header y navbars
include __DIR__ . '/views/layouts/header.php';
include __DIR__ . '/views/layouts/navbar.php';

// Abre contenedor principal (layout): el sidebar y el contenido usarán esta fila/columnas
echo '<div class="container-fluid"><div class="row">';

// esto hace que el sidevar solo aparezca si hay sesion activa... o si no aparece en el login xd y pos no
if (!empty($_SESSION['usuario'])) {
    include __DIR__ . '/views/layouts/sidebar.php';
}

// esto es lo que sirve oara redirigir, en caso que redirija a una pagina que no exista o mal puesta, dice que no la encuentra
$viewFile = __DIR__ . "/views/pages/{$page}.php";
if (file_exists($viewFile)) {
    include $viewFile;
} else {
    echo "<main class='col-12'><h2>Página no encontrada</h2></main>";
}

// Cierra contenedor principal
echo '</div></div>';

// Incluye footer
include __DIR__ . '/views/layouts/footer.php';
