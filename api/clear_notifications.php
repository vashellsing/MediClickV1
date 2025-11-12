<?php
header('Content-Type: application/json');
require_once '../config/database.php';

try {
    // Eliminar TODAS las notificaciones existentes
    $stmt = $conn->prepare("DELETE FROM notificacion");
    $stmt->execute();
    
    echo json_encode(['success' => true, 'message' => 'Todas las notificaciones han sido eliminadas']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}