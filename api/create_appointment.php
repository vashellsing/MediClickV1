<?php
header('Content-Type: application/json');
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$paciente_id = $input['paciente_id'] ?? null;
$medico_id = $input['medico_id'] ?? null;
$horario_id = $input['horario_id'] ?? null;
$tipo_cita = $input['tipo_cita'] ?? 'especializacion';

if (!$paciente_id || !$medico_id || !$horario_id) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

try {
    $conn->beginTransaction();

    // 1. Obtener información del horario para la fecha y hora
    $stmt = $conn->prepare("
        SELECT fecha, hora_inicio, id_medico 
        FROM horarios 
        WHERE id_horario = ?
    ");
    $stmt->execute([$horario_id]);
    $horario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$horario) {
        throw new Exception("Horario no encontrado");
    }

    // 2. Actualizar el horario a "ocupado"
    $stmt = $conn->prepare("UPDATE horarios SET estado = 'ocupado' WHERE id_horario = ?");
    $stmt->execute([$horario_id]);

    // 3. Crear la cita
    $fecha_hora_cita = $horario['fecha'] . ' ' . $horario['hora_inicio'];
    
    $stmt = $conn->prepare("
        INSERT INTO citas (id_paciente, id_medico, fecha_hora_cita, estado, tipo_cita) 
        VALUES (?, ?, ?, 'CONFIRMADA', ?)
    ");
    $stmt->execute([$paciente_id, $medico_id, $fecha_hora_cita, $tipo_cita]);
    $cita_id = $conn->lastInsertId();

    // 4. Crear notificación - CON LAS NUEVAS COLUMNAS
    $mensaje = "Su cita de " . $tipo_cita . " ha sido agendada exitosamente para el " . 
               date('d/m/Y', strtotime($horario['fecha'])) . " a las " . 
               substr($horario['hora_inicio'], 0, 5) . " hrs.";
    
    $stmt = $conn->prepare("
        INSERT INTO notificacion (id_paciente, mensaje, leida) 
        VALUES (?, ?, 'no')
    ");
    $stmt->execute([$paciente_id, $mensaje]);

    $conn->commit();

    echo json_encode([
        'success' => true, 
        'message' => 'Cita agendada exitosamente',
        'cita_id' => $cita_id
    ]);
} catch (Exception $e) {
    $conn->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}