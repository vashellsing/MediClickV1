<?php
header('Content-Type: application/json');
require_once '../config/database.php';

session_start();

// Por ahora paciente_id = 1, en producción esto vendría de la sesión
$paciente_id = 1;

try {
    // Marcar TODAS las notificaciones del paciente como leídas
    $stmt = $conn->prepare("
        UPDATE notificacion 
        SET leida = 'si' 
        WHERE id_paciente = ? AND leida = 'no'
    ");
    $stmt->execute([$paciente_id]);
    
    $affectedRows = $stmt->rowCount();
    
    error_log("Notificaciones marcadas como leídas: " . $affectedRows . " para paciente " . $paciente_id);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Notificaciones marcadas como leídas',
        'affected_rows' => $affectedRows
    ]);
    
} catch (Exception $e) {
    error_log("Error en mark_notifications_read.php: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Error: ' . $e->getMessage()
    ]);
}