<?php
header('Content-Type: application/json');
require_once '../config/database.php';

session_start();

// Por ahora paciente_id = 1
$paciente_id = 1;

try {
    $stmt = $conn->prepare("
        SELECT COUNT(*) as unread_count 
        FROM notificacion 
        WHERE id_paciente = ? AND leida = 'no'
    ");
    $stmt->execute([$paciente_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $unreadCount = (int)$result['unread_count'];
    
    error_log("Notificaciones no leídas: " . $unreadCount . " para paciente " . $paciente_id);
    
    echo json_encode([
        'unread_count' => $unreadCount
    ]);
    
} catch (Exception $e) {
    error_log("Error en get_notification_count.php: " . $e->getMessage());
    echo json_encode(['unread_count' => 0]);
}