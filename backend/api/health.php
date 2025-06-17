<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

echo json_encode([
    'status' => 'ok',
    'message' => 'API is running',
    'timestamp' => date('Y-m-d H:i:s')
]);