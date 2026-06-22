<?php

require_once __DIR__ . '/../bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$response = [
    'status' => 'ok',
    'message' => 'Backend System is running',
    'version' => \App\Core\Config::get('app.version'),
];

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
