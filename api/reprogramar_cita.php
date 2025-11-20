<?php
session_start();
header('Content-Type: application/json');
require_once '../config/database.php';
require_once __DIR__ . '/../vendor/autoload.php';
use MediClick\EmailSender;

// Verificación de sesión
if (!isset($_SESSION['id']) || $_SESSION['rol'] != 'PACIENTE') {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

// VALIDACIÓN: Necesitamos ID de cita vieja y ID del nuevo horario seleccionado
$cita_id = $data['cita_id'] ?? null;
$nuevo_horario_id = $data['nuevo_horario_id'] ?? null;

if (!$cita_id || !$nuevo_horario_id) {
    echo json_encode(['success' => false, 'message' => 'Faltan datos para reprogramar.']);
    exit;
}

try {
    $conn->beginTransaction();
    
    // 1. OBTENER DATOS DE LA CITA ORIGINAL (VIEJA)
    $stmt = $conn->prepare("
        SELECT 
            c.id_cita, c.id_medico, c.id_paciente, c.tipo_cita,
            c.fecha_hora_cita,
            p.nombre as paciente_nombre, p.correo as paciente_correo,
            m.nombre as medico_nombre, m.apellido as medico_apellido
        FROM citas c 
        JOIN pacientes p ON c.id_paciente = p.id_paciente
        JOIN medicos m ON c.id_medico = m.id_medico
        WHERE c.id_cita = ?
    ");
    $stmt->execute([$cita_id]);
    $cita_original = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$cita_original) {
        throw new Exception("Cita original no encontrada");
    }

    // Datos para el correo (historial)
    $dt_anterior = new DateTime($cita_original['fecha_hora_cita']);
    $fecha_anterior_bd = $dt_anterior->format('Y-m-d');
    $hora_anterior_bd  = $dt_anterior->format('H:i:s');

    // 2. OBTENER DATOS DEL NUEVO HORARIO (Incluyendo el ID del médico dueño de ese horario)
    $stmt = $conn->prepare("SELECT id_horario, fecha, hora_inicio, id_medico FROM horarios WHERE id_horario = ? AND estado = 'libre'");
    $stmt->execute([$nuevo_horario_id]);
    $nuevo_horario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$nuevo_horario) {
        throw new Exception("El nuevo horario seleccionado ya no está disponible.");
    }

    // 3. LOGICA DE BASE DE DATOS (Swap)
    
    // A. Liberar horario antiguo (del médico original)
    $stmt = $conn->prepare("UPDATE horarios SET estado = 'libre' WHERE id_medico = ? AND fecha = ? AND hora_inicio = ?");
    $stmt->execute([$cita_original['id_medico'], $fecha_anterior_bd, $hora_anterior_bd]);

    // B. Ocupar horario nuevo
    $stmt = $conn->prepare("UPDATE horarios SET estado = 'ocupado' WHERE id_horario = ?");
    $stmt->execute([$nuevo_horario_id]);

    // C. Marcar cita vieja como REPROGRAMADA
    $stmt = $conn->prepare("UPDATE citas SET estado = 'REPROGRAMADA' WHERE id_cita = ?");
    $stmt->execute([$cita_id]);

    // D. CREAR LA NUEVA CITA
    // Usamos el id_medico del NUEVO horario (por si cambió de doctor)
    $nueva_fecha_hora = $nuevo_horario['fecha'] . ' ' . $nuevo_horario['hora_inicio'];
    
    $stmt = $conn->prepare("
        INSERT INTO citas (id_paciente, id_medico, fecha_hora_cita, estado, tipo_cita, fecha_creacion) 
        VALUES (?, ?, ?, 'CONFIRMADA', ?, NOW())
    ");
    $stmt->execute([
        $cita_original['id_paciente'],
        $nuevo_horario['id_medico'], // <--- Importante: Médico del nuevo horario
        $nueva_fecha_hora,
        $cita_original['tipo_cita']
    ]);
    
    $nueva_cita_id = $conn->lastInsertId();

    // E. Notificación Interna (Navbar)
    $mensaje = "Cita reprogramada exitosamente. Nueva fecha: " . date('d/m/Y', strtotime($nuevo_horario['fecha'])) . 
               " a las " . substr($nuevo_horario['hora_inicio'], 0, 5);
    
    $stmt = $conn->prepare("INSERT INTO notificacion (id_paciente, mensaje, leida) VALUES (?, ?, 'no')");
    $stmt->execute([$cita_original['id_paciente'], $mensaje]);

    // 4. FINALIZAR TRANSACCIÓN BD
    $conn->commit();

    // 5. ENVÍO DE CORREO
    $emailEnviado = false;
    try {
        $emailSender = new EmailSender();
        
        // Obtenemos nombre del NUEVO médico para el correo
        if ($cita_original['id_medico'] != $nuevo_horario['id_medico']) {
            $stmtM = $conn->prepare("SELECT nombre, apellido FROM medicos WHERE id_medico = ?");
            $stmtM->execute([$nuevo_horario['id_medico']]);
            $nuevoMedico = $stmtM->fetch(PDO::FETCH_ASSOC);
            $nombreMedicoFinal = $nuevoMedico['nombre'] . ' ' . $nuevoMedico['apellido'];
        } else {
            $nombreMedicoFinal = $cita_original['medico_nombre'] . ' ' . $cita_original['medico_apellido'];
        }

        $citaData = [
            'paciente_nombre' => $cita_original['paciente_nombre'],
            'medico_nombre'   => $nombreMedicoFinal,
            'tipo_cita'       => $cita_original['tipo_cita'] === 'general' ? 'Medicina General' : 'Especialización',
            'nueva_fecha'     => date('d/m/Y', strtotime($nuevo_horario['fecha'])),
            'nueva_hora'      => substr($nuevo_horario['hora_inicio'], 0, 5),
            'fecha_anterior'  => $dt_anterior->format('d/m/Y'),
            'hora_anterior'   => $dt_anterior->format('H:i')
        ];
        
        $emailEnviado = $emailSender->enviarNotificacionCita(
            $cita_original['paciente_correo'],
            $cita_original['paciente_nombre'],
            'reprogramada',
            $citaData
        );

    } catch (Exception $emailEx) {
        error_log("Error enviando email reprogramación: " . $emailEx->getMessage());
    }
    
    header('X-Notification-Update: true');
    
    echo json_encode([
        'success' => true, 
        'message' => 'Cita reprogramada exitosamente',
        'nueva_cita_id' => $nueva_cita_id,
        'email_enviado' => $emailEnviado
    ]);
    
} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>