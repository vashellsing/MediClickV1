# MediClickV1

Hola gente, si leen esto, pregunten cosas que no entiendan, para ir acomodando pero en general la base esta hecha. Exitos



si en un futuro se necesita funcional de los dos usuarios 
/* ===========================
   🔑 PROCESAR LOGIN REAL
=========================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $page === 'login') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    try {
        // Buscar primero en pacientes
        $stmt = $conn->prepare("
            SELECT p.*, r.rol AS nombre_rol 
            FROM pacientes p
            JOIN roles r ON p.id_rol = r.id_rol
            WHERE p.correo = :email
            LIMIT 1
        ");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        // Si no existe en pacientes, buscar en médicos
        if (!$usuario) {
            $stmt = $conn->prepare("
                SELECT m.*, r.rol AS nombre_rol 
                FROM medicos m
                JOIN roles r ON m.id_rol = r.id_rol
                WHERE m.correo = :email
                LIMIT 1
            ");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        // Verificar si encontró usuario
        if ($usuario) {
            $cedulaBD = trim($usuario['cedula']);
            $passwordInput = trim($password);

            if ($passwordInput === $cedulaBD) {
                $_SESSION['usuario'] = $usuario['nombre'];
                $_SESSION['rol'] = $usuario['nombre_rol'];
                $_SESSION['id'] = $usuario['id_paciente'] ?? $usuario['id_medico'];
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