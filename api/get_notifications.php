<?php
header('Content-Type: application/json');
require_once '../config/database.php';

$paciente_id = 1;

try {
    $stmt = $conn->prepare("
        SELECT id_notificacion, mensaje, fecha_creacion, leida
        FROM notificacion 
        WHERE id_paciente = ? 
        ORDER BY fecha_creacion DESC, id_notificacion DESC
        LIMIT 10
    ");
    $stmt->execute([$paciente_id]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Formatear fechas de manera más amigable
    foreach ($notifications as &$notif) {
        $fecha = new DateTime($notif['fecha_creacion']);
        $ahora = new DateTime();
        $diferencia = $ahora->diff($fecha);
        
        if ($diferencia->d == 0) {
            // Hoy
            if ($diferencia->h == 0) {
                if ($diferencia->i == 0) {
                    $notif['fecha_formateada'] = 'Ahora mismo';
                } else {
                    $notif['fecha_formateada'] = 'Hace ' . $diferencia->i . ' min';
                }
            } else {
                $notif['fecha_formateada'] = 'Hace ' . $diferencia->h . ' h';
            }
        } else if ($diferencia->d == 1) {
            $notif['fecha_formateada'] = 'Ayer ' . $fecha->format('H:i');
        } else if ($diferencia->d < 7) {
            $notif['fecha_formateada'] = 'Hace ' . $diferencia->d . ' días';
        } else {
            $notif['fecha_formateada'] = $fecha->format('d/m/Y');
        }
    }

    echo json_encode($notifications);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al obtener notificaciones: ' . $e->getMessage()]);
}