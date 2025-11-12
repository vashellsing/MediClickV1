<?php
header('Content-Type: application/json');
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$fecha = $_GET['fecha'] ?? '';
$especialidad = $_GET['especialidad'] ?? '';
$id_medico = $_GET['id_medico'] ?? '';

if (!$fecha) {
    echo json_encode(['error' => 'Fecha es requerida']);
    exit;
}

try {
    // CASO 1: Médico específico seleccionado
    if (!empty($id_medico) && $id_medico !== 'null' && $id_medico !== 'any') {
        // Verificar si ya existen horarios para este médico y fecha
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count 
            FROM horarios 
            WHERE id_medico = ? AND fecha = ?
        ");
        $stmt->execute([$id_medico, $fecha]);
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Si no existen horarios, generarlos
        if ($count == 0) {
            generarHorariosMedico($conn, $id_medico, $fecha);
        }
        
        // Obtener horarios disponibles
        $stmt = $conn->prepare("
            SELECT h.id_horario as id, h.id_medico, h.fecha, h.hora_inicio as hora, 
                   h.estado, m.nombre, m.apellido
            FROM horarios h
            JOIN medicos m ON h.id_medico = m.id_medico
            WHERE h.id_medico = ? AND h.fecha = ? AND h.estado = 'libre'
            ORDER BY h.hora_inicio
        ");
        $stmt->execute([$id_medico, $fecha]);
        $horarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    // CASO 2: Sin preferencia - buscar todos los médicos de la especialidad
    else if (!empty($especialidad)) {
        // Primero obtener todos los médicos de esta especialidad
        $stmt = $conn->prepare("
            SELECT m.id_medico 
            FROM medicos m 
            JOIN especialidad e ON m.id_especialidad = e.id_especialidad 
            WHERE e.nombre = ?
        ");
        $stmt->execute([$especialidad]);
        $medicos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Para cada médico, asegurar que tenga horarios generados
        foreach ($medicos as $medico) {
            $stmt = $conn->prepare("
                SELECT COUNT(*) as count 
                FROM horarios 
                WHERE id_medico = ? AND fecha = ?
            ");
            $stmt->execute([$medico['id_medico'], $fecha]);
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            if ($count == 0) {
                generarHorariosMedico($conn, $medico['id_medico'], $fecha);
            }
        }
        
        // Obtener todos los horarios disponibles de médicos de esta especialidad
        $stmt = $conn->prepare("
            SELECT h.id_horario as id, h.id_medico, h.fecha, h.hora_inicio as hora, 
                   h.estado, m.nombre, m.apellido, e.nombre as especialidad
            FROM horarios h
            JOIN medicos m ON h.id_medico = m.id_medico
            JOIN especialidad e ON m.id_especialidad = e.id_especialidad
            WHERE h.fecha = ? AND h.estado = 'libre' AND e.nombre = ?
            ORDER BY h.hora_inicio
        ");
        $stmt->execute([$fecha, $especialidad]);
        $horarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    else {
        $horarios = [];
    }

    echo json_encode($horarios);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

// Función para generar horarios de un médico
function generarHorariosMedico($conn, $id_medico, $fecha) {
    $bloques = [];
    
    // Horario de mañana: 07:00 - 11:40 (bloques de 20 minutos)
    $inicioManana = strtotime('07:00');
    $finManana = strtotime('11:40');
    
    for ($time = $inicioManana; $time < $finManana; $time += 20 * 60) {
        $hInicio = date('H:i:s', $time);
        $hFin = date('H:i:s', $time + 20 * 60);
        $bloques[] = [
            'id_medico' => $id_medico,
            'fecha' => $fecha,
            'hora_inicio' => $hInicio,
            'hora_fin' => $hFin,
            'estado' => 'libre'
        ];
    }
    
    // Horario de tarde: 14:00 - 19:40 (bloques de 20 minutos)
    $inicioTarde = strtotime('14:00');
    $finTarde = strtotime('19:40');
    
    for ($time = $inicioTarde; $time < $finTarde; $time += 20 * 60) {
        $hInicio = date('H:i:s', $time);
        $hFin = date('H:i:s', $time + 20 * 60);
        $bloques[] = [
            'id_medico' => $id_medico,
            'fecha' => $fecha,
            'hora_inicio' => $hInicio,
            'hora_fin' => $hFin,
            'estado' => 'libre'
        ];
    }
    
    // Insertar todos los bloques
    $insert = $conn->prepare("
        INSERT INTO horarios (id_medico, fecha, hora_inicio, hora_fin, estado) 
        VALUES (?, ?, ?, ?, ?)
    ");
    
    foreach ($bloques as $bloque) {
        $insert->execute([
            $bloque['id_medico'],
            $bloque['fecha'],
            $bloque['hora_inicio'],
            $bloque['hora_fin'],
            $bloque['estado']
        ]);
    }
}