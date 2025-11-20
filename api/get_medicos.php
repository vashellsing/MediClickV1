<?php
header('Content-Type: application/json');
require_once '../config/database.php';

try {
    $id_especialidad = isset($_GET['id_especialidad']) && $_GET['id_especialidad'] !== '' ? intval($_GET['id_especialidad']) : 0;
    $filter = isset($_GET['filter']) ? $_GET['filter'] : '';
    $tipo = isset($_GET['tipo']) ? $_GET['tipo'] : 'especializacion'; // 'especializacion' o 'general'

    if ($tipo === 'general') {
        // Para medicina general: médicos sin especialidad específica o con especialidad "Medicina General"
        $sql = "
            SELECT m.id_medico, m.nombre, m.apellido, m.correo, m.id_especialidad,
                   e.nombre as nombre_especialidad
            FROM medicos m
            LEFT JOIN especialidad e ON m.id_especialidad = e.id_especialidad
            WHERE (e.nombre LIKE '%general%' OR m.id_especialidad IS NULL OR e.nombre IS NULL)
        ";
        
        if (!empty($filter)) {
            $sql .= " AND (m.nombre LIKE ? OR m.apellido LIKE ?)";
            $stmt = $conn->prepare($sql);
            $searchTerm = "%$filter%";
            $stmt->execute([$searchTerm, $searchTerm]);
        } else {
            $stmt = $conn->prepare($sql);
            $stmt->execute();
        }
    } else {
        // Para especialización: filtro normal por id_especialidad
        if ($id_especialidad > 0) {
            $sql = "
                SELECT m.id_medico, m.nombre, m.apellido, m.correo, m.id_especialidad,
                       e.nombre as nombre_especialidad
                FROM medicos m
                LEFT JOIN especialidad e ON m.id_especialidad = e.id_especialidad
                WHERE m.id_especialidad = ?
            ";
            
            if (!empty($filter)) {
                $sql .= " AND (m.nombre LIKE ? OR m.apellido LIKE ?)";
                $stmt = $conn->prepare($sql);
                $searchTerm = "%$filter%";
                $stmt->execute([$id_especialidad, $searchTerm, $searchTerm]);
            } else {
                $stmt = $conn->prepare($sql);
                $stmt->execute([$id_especialidad]);
            }
        } else {
            $sql = "
                SELECT m.id_medico, m.nombre, m.apellido, m.correo, m.id_especialidad,
                       e.nombre as nombre_especialidad
                FROM medicos m
                LEFT JOIN especialidad e ON m.id_especialidad = e.id_especialidad
                WHERE 1=1
            ";
            
            if (!empty($filter)) {
                $sql .= " AND (m.nombre LIKE ? OR m.apellido LIKE ?)";
                $stmt = $conn->prepare($sql);
                $searchTerm = "%$filter%";
                $stmt->execute([$searchTerm, $searchTerm]);
            } else {
                $stmt = $conn->prepare($sql);
                $stmt->execute();
            }
        }
    }

    $medicos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $resultado = [];
    foreach ($medicos as $m) {
        $resultado[] = [
            'id_medico' => (int)$m['id_medico'],
            'nombre_medico' => trim(($m['nombre'] ?? '') . ' ' . ($m['apellido'] ?? '')),
            'nombre' => $m['nombre'] ?? '',
            'apellido' => $m['apellido'] ?? '',
            'correo' => $m['correo'] ?? '',
            'id_especialidad' => isset($m['id_especialidad']) ? (int)$m['id_especialidad'] : null,
            'nombre_especialidad' => $m['nombre_especialidad'] ?? ($tipo === 'general' ? 'Medicina General' : '')
        ];
    }

    echo json_encode($resultado);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al obtener médicos: ' . $e->getMessage()]);
}