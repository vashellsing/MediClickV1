<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['id']) || $_SESSION['rol'] != 'PACIENTE') {
    exit;
}

$paciente_id = $_SESSION['id'];  

try {
    // Eliminar notificaciones leídas con más de 7 días
    $stmt = $conn->prepare("
        DELETE FROM notificacion 
        WHERE id_paciente = ? 
        AND leida = 'si' 
        AND fecha_creacion < DATE_SUB(NOW(), INTERVAL 7 DAY)
    ");
    $stmt->execute([$paciente_id]);
    
    // O mantener solo las últimas 20 notificaciones por usuario
    $stmt = $conn->prepare("
        DELETE FROM notificacion
        WHERE id_paciente = ? 
        AND id_notificacion NOT IN (
            SELECT id_notificacion FROM (
                SELECT id_notificacion 
                FROM notificacion 
                WHERE id_paciente = ? 
                ORDER BY fecha_creacion DESC 
                LIMIT 20
            ) AS temp
        )
    ");
    $stmt->execute([$paciente_id, $paciente_id]);
    
} catch (Exception $e) {
    error_log("Error limpiando notificaciones antiguas: " . $e->getMessage());
}