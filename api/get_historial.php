<?php
header('Content-Type: application/json');
require_once '../config/database.php';

// Por ahora paciente_id = 1 para testing
$paciente_id = 1;

try {
    $stmt = $conn->prepare("
        SELECT 
            c.id_cita,
            c.fecha_hora_cita,
            c.estado,
            c.tipo_cita,
            m.nombre as medico_nombre,
            m.apellido as medico_apellido,
            CONCAT('Dr. ', m.nombre, ' ', m.apellido) as medico_completo,
            e.nombre as especialidad
        FROM citas c
        JOIN medicos m ON c.id_medico = m.id_medico
        LEFT JOIN especialidad e ON m.id_especialidad = e.id_especialidad
        WHERE c.id_paciente = ?
        ORDER BY c.fecha_hora_cita DESC
    ");
    $stmt->execute([$paciente_id]);
    $citas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Debug: ver qué datos estamos obteniendo
    error_log("Citas encontradas: " . count($citas));
    
    if (count($citas) > 0) {
        error_log("Primera cita: " . print_r($citas[0], true));
    }

    echo json_encode($citas);
    
} catch (Exception $e) {
    error_log("Error en get_historial.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error al obtener historial: ' . $e->getMessage()]);
}