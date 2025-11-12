<?php
header('Content-Type: application/json');
require_once '../config/database.php';

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

    // 1. Actualizar el estado de la cita a CANCELADA
    $stmt = $conn->prepare("UPDATE citas SET estado = 'CANCELADA' WHERE id_cita = ?");
    $stmt->execute([$cita_id]);

    // 2. Obtener información de la cita para la notificación
    $stmt = $conn->prepare("
        SELECT c.id_paciente, c.fecha_hora_cita, m.nombre as medico_nombre 
        FROM citas c 
        JOIN medicos m ON c.id_medico = m.id_medico 
        WHERE c.id_cita = ?
    ");
    $stmt->execute([$cita_id]);
    $cita = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($cita) {
        // 3. Crear notificación de cancelación - CON LAS NUEVAS COLUMNAS
        $fecha = date('d/m/Y', strtotime($cita['fecha_hora_cita']));
        $hora = date('H:i', strtotime($cita['fecha_hora_cita']));
        
        $mensaje = "Su cita con Dr. " . $cita['medico_nombre'] . " para el " . $fecha . " a las " . $hora . " ha sido cancelada.";
        
        $stmt = $conn->prepare("INSERT INTO notificacion (id_paciente, mensaje, leida) VALUES (?, ?, 'no')");
        $stmt->execute([$cita['id_paciente'], $mensaje]);
    }

    $conn->commit();

    echo json_encode([
        'success' => true, 
        'message' => 'Cita cancelada exitosamente'
    ]);
} catch (Exception $e) {
    $conn->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}