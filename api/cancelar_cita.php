<?php
header('Content-Type: application/json');
require_once '../config/database.php';

// Carga automática de clases (para EmailSender)
require_once __DIR__ . '/../vendor/autoload.php';
use MediClick\EmailSender;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$cita_id = $input['cita_id'] ?? null;

if (!$cita_id) {
    echo json_encode(['success' => false, 'message' => 'ID de cita requerido']);
    exit;
}

try {
    $conn->beginTransaction();

    // 1. Obtener información completa de la cita, paciente y médico
    // Hacemos JOINs para obtener todo en una sola consulta eficiente
    $stmt = $conn->prepare("
        SELECT 
            c.id_cita, 
            c.fecha_hora_cita, 
            c.tipo_cita,
            c.id_medico,
            p.id_paciente, 
            p.nombre AS paciente_nombre, 
            p.correo AS paciente_correo,
            m.nombre AS medico_nombre, 
            m.apellido AS medico_apellido
        FROM citas c 
        JOIN pacientes p ON c.id_paciente = p.id_paciente
        JOIN medicos m ON c.id_medico = m.id_medico 
        WHERE c.id_cita = ?
    ");
    $stmt->execute([$cita_id]);
    $cita = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cita) {
        throw new Exception("Cita no encontrada");
    }

    // 2. Extraer fecha y hora de la cita para lógica de negocio y correo
    $fecha_hora = new DateTime($cita['fecha_hora_cita']);
    $fecha_bd = $fecha_hora->format('Y-m-d'); // Para la query de update
    $hora_bd  = $fecha_hora->format('H:i:s'); // Para la query de update
    
    $fecha_formato_email = $fecha_hora->format('d/m/Y'); // Para visualización
    $hora_formato_email  = $fecha_hora->format('H:i');   // Para visualización

    // 3. LIBERAR el horario del médico
    $stmt = $conn->prepare("
        UPDATE horarios 
        SET estado = 'libre' 
        WHERE id_medico = ? 
        AND fecha = ? 
        AND hora_inicio = ? 
        AND estado = 'ocupado'
    ");
    $stmt->execute([
        $cita['id_medico'],
        $fecha_bd,
        $hora_bd
    ]);

    $horarios_afectados = $stmt->rowCount();

    // 4. Actualizar el estado de la cita a CANCELADA
    $stmt = $conn->prepare("UPDATE citas SET estado = 'CANCELADA' WHERE id_cita = ?");
    $stmt->execute([$cita_id]);

    // 5. Crear notificación interna
    $mensaje = "Su cita con Dr. " . $cita['medico_nombre'] . " " . $cita['medico_apellido'] . 
               " para el " . $fecha_formato_email . " a las " . $hora_formato_email . 
               " ha sido cancelada.";
    
    $stmt = $conn->prepare("INSERT INTO notificacion (id_paciente, mensaje, leida) VALUES (?, ?, 'no')");
    $stmt->execute([$cita['id_paciente'], $mensaje]);

    // 6. CONFIRMAR TRANSACCIÓN (Guardar cambios en BD)
    $conn->commit();

    // --- FASE DE NOTIFICACIÓN EMAIL ---
    $emailEnviado = false;
    try {
        $emailSender = new EmailSender();
        
        $citaData = [
            'paciente_nombre' => $cita['paciente_nombre'],
            'medico_nombre'   => $cita['medico_nombre'] . ' ' . $cita['medico_apellido'],
            'tipo_cita'       => $cita['tipo_cita'],
            'fecha'           => $fecha_formato_email,
            'hora'            => $hora_formato_email
        ];
        
        // NOTA: Aquí cambiamos el tipo a 'cancelada'
        $emailEnviado = $emailSender->enviarNotificacionCita(
            $cita['paciente_correo'],
            $cita['paciente_nombre'],
            'cancelada',
            $citaData
        );

    } catch (Exception $emailEx) {
        error_log("Cita cancelada pero falló envío de email: " . $emailEx->getMessage());
    }

    // 7. Responder al cliente
    // Header para actualizar notificaciones en front si es necesario
    header('X-Notification-Update: true');

    echo json_encode([
        'success' => true, 
        'message' => 'Cita cancelada exitosamente',
        'notification_created' => true,
        'horarios_liberados' => $horarios_afectados,
        'email_enviado' => $emailEnviado
    ]);

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}