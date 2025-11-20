<?php
session_start();
header('Content-Type: application/json');
require_once '../config/database.php';

// Verificar sesión con los nombres CORRECTOS
if (!isset($_SESSION['id']) || $_SESSION['rol'] != 'PACIENTE') {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado - Sesión inválida']);
    exit;
}

$paciente_id = $_SESSION['id'];

try {
    $stmt = $conn->prepare("
        SELECT 
            c.id_cita,
            c.fecha_hora_cita,
            c.estado,
            c.id_medico,  -- ¡IMPORTANTE: agregar este campo!
            m.nombre as medico_nombre,
            m.apellido as medico_apellido,
            m.id_especialidad,
            CONCAT('Dr. ', m.nombre, ' ', m.apellido) as medico_completo,
            e.nombre as especialidad,
            -- Determinar tipo_cita basado en la especialidad del médico
            CASE 
                WHEN m.id_especialidad = 1 THEN 'General'
                ELSE 'Especialización'
            END as tipo_cita
        FROM citas c
        JOIN medicos m ON c.id_medico = m.id_medico
        LEFT JOIN especialidad e ON m.id_especialidad = e.id_especialidad
        WHERE c.id_paciente = ?
        ORDER BY c.fecha_creacion DESC 
    ");
    $stmt->execute([$paciente_id]);
    $citas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($citas);
    
} catch (Exception $e) {
    error_log("Error en get_historial.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error al obtener historial: ' . $e->getMessage()]);
}