<?php
header('Content-Type: application/json');
require_once '../config/database.php';

// Carga automática de clases (para EmailSender)
require_once __DIR__ . '/../vendor/autoload.php';
use MediClick\EmailSender;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$paciente_id = $input['paciente_id'] ?? null;
$medico_id   = $input['medico_id'] ?? null;
$horario_id  = $input['horario_id'] ?? null;
$tipo_cita   = $input['tipo_cita'] ?? 'especializacion';

if (!$paciente_id || !$medico_id || !$horario_id) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

try {
    $conn->beginTransaction();

    // 1. Obtener información del horario
    $stmt = $conn->prepare("
        SELECT fecha, hora_inicio, id_medico 
        FROM horarios 
        WHERE id_horario = ?
    ");
    $stmt->execute([$horario_id]);
    $horario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$horario) {
        throw new Exception("Horario no encontrado");
    }

    // 2. Actualizar el horario a "ocupado"
    $stmt = $conn->prepare("UPDATE horarios SET estado = 'ocupado' WHERE id_horario = ?");
    $stmt->execute([$horario_id]);

    // 3. Crear la cita
    $fecha_hora_cita = $horario['fecha'] . ' ' . $horario['hora_inicio'];
    
    $stmt = $conn->prepare("
        INSERT INTO citas (id_paciente, id_medico, fecha_hora_cita, estado, tipo_cita, fecha_creacion) 
        VALUES (?, ?, ?, 'CONFIRMADA', ?, NOW())
    ");
    $stmt->execute([$paciente_id, $medico_id, $fecha_hora_cita, $tipo_cita]);
    $cita_id = $conn->lastInsertId();

    // 4. Crear notificación interna (DB)
    $mensaje = "Su cita de " . $tipo_cita . " ha sido agendada exitosamente para el " . 
               date('d/m/Y', strtotime($horario['fecha'])) . " a las " . 
               substr($horario['hora_inicio'], 0, 5) . " hrs.";
    
    $stmt = $conn->prepare("
        INSERT INTO notificacion (id_paciente, mensaje, leida) 
        VALUES (?, ?, 'no')
    ");
    $stmt->execute([$paciente_id, $mensaje]);

    // 5. PREPARAR DATOS PARA EL EMAIL (Dentro de la transacción para asegurar lectura)
    // Obtener datos del paciente
    $stmtPaciente = $conn->prepare("SELECT nombre, correo FROM pacientes WHERE id_paciente = ?");
    $stmtPaciente->execute([$paciente_id]);
    $paciente = $stmtPaciente->fetch(PDO::FETCH_ASSOC);
    
    // Obtener datos del médico
    $stmtMedico = $conn->prepare("SELECT nombre, apellido FROM medicos WHERE id_medico = ?");
    $stmtMedico->execute([$medico_id]);
    $medico = $stmtMedico->fetch(PDO::FETCH_ASSOC);

    // Validamos que existan los datos antes de continuar
    if(!$paciente || !$medico) {
        throw new Exception("Error obteniendo datos de paciente o médico para notificación.");
    }

    // 6. CONFIRMAR TRANSACCIÓN (Aquí se guarda todo en la BD)
    $conn->commit();

    // --- FASE DE NOTIFICACIÓN ---
    // El envío de correo va DESPUÉS del commit. Si falla el correo, la cita YA EXISTE.
    
    $emailEnviado = false;
    try {
        $emailSender = new EmailSender();
        
        $citaData = [
            'paciente_nombre' => $paciente['nombre'],
            'medico_nombre'   => $medico['nombre'] . ' ' . $medico['apellido'],
            'tipo_cita'       => $tipo_cita === 'general' ? 'Medicina General' : 'Especialización',
            'fecha'           => date('d/m/Y', strtotime($horario['fecha'])),
            'hora'            => substr($horario['hora_inicio'], 0, 5)
        ];
        
        $emailEnviado = $emailSender->enviarNotificacionCita(
            $paciente['correo'],
            $paciente['nombre'],
            'agendada',
            $citaData
        );
    } catch (Exception $emailEx) {
        // Si falla el email solo lo logueamos, no detenemos el éxito de la cita
        error_log("Advertencia: La cita se creó pero falló el email: " . $emailEx->getMessage());
    }

    // 7. Responder al cliente
    echo json_encode([
        'success' => true, 
        'message' => 'Cita agendada exitosamente',
        'cita_id' => $cita_id,
        'email_notificado' => $emailEnviado // Opcional: para debug en front
    ]);

} catch (Exception $e) {
    // Solo hacemos rollback si la transacción sigue activa
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}