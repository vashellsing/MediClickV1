<?php
header('Content-Type: application/json');
session_start();

// Por ahora, retornamos un ID fijo para testing
// En producción, esto debería venir de la sesión del usuario
echo json_encode(['paciente_id' => 1]);