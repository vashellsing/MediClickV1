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
$tipo = isset($_GET['tipo']) ? $_GET['tipo'] : 'especializacion'; // 'especializacion' o 'general'

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
            generarHorariosMedico($conn, $id_medico, $fecha, $tipo);
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
    // CASO 2: Sin preferencia - buscar médicos según el tipo
    else {
        if ($tipo === 'general') {
            // Para medicina general: buscar médicos sin especialidad específica
            $stmt = $conn->prepare("
                SELECT m.id_medico 
                FROM medicos m 
                LEFT JOIN especialidad e ON m.id_especialidad = e.id_especialidad
                WHERE (e.nombre LIKE '%general%' OR m.id_especialidad IS NULL OR e.nombre IS NULL)
            ");
            $stmt->execute();
        } else {
            // Para especialización: buscar médicos de la especialidad específica
            $stmt = $conn->prepare("
                SELECT m.id_medico 
                FROM medicos m 
                JOIN especialidad e ON m.id_especialidad = e.id_especialidad 
                WHERE e.nombre = ?
            ");
            $stmt->execute([$especialidad]);
        }
        
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
                generarHorariosMedico($conn, $medico['id_medico'], $fecha, $tipo);
            }
        }
        
        // Obtener horarios disponibles según el tipo
        if ($tipo === 'general') {
            $stmt = $conn->prepare("
                SELECT h.id_horario as id, h.id_medico, h.fecha, h.hora_inicio as hora, 
                       h.estado, m.nombre, m.apellido, e.nombre as especialidad
                FROM horarios h
                JOIN medicos m ON h.id_medico = m.id_medico
                LEFT JOIN especialidad e ON m.id_especialidad = e.id_especialidad
                WHERE h.fecha = ? AND h.estado = 'libre' 
                AND (e.nombre LIKE '%general%' OR m.id_especialidad IS NULL OR e.nombre IS NULL)
                ORDER BY h.hora_inicio
            ");
            $stmt->execute([$fecha]);
        } else {
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
        }
        
        $horarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    echo json_encode($horarios);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

// Función para generar horarios de un médico (con horarios diferentes según tipo)
function generarHorariosMedico($conn, $id_medico, $fecha, $tipo = 'especializacion') {
    $bloques = [];
    
    if ($tipo === 'general') {
        // Horario para medicina general: 08:00 - 17:00 (horario de oficina)
        $inicioManana = strtotime('08:00');
        $finManana = strtotime('12:00');
        
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
        
        $inicioTarde = strtotime('14:00');
        $finTarde = strtotime('17:00');
        
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
    } else {
        // Horario para especialización: 07:00 - 19:40 (horario extendido)
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