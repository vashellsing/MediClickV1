<?php
session_start();
header('Content-Type: application/json');

echo json_encode([
    'session_id' => session_id(),
    'session_data' => $_SESSION,
    'headers_sent' => headers_sent(),
    'cookie_exists' => isset($_COOKIE[session_name()]),
    'session_name' => session_name()
]);