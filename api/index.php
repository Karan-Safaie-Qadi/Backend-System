<?php

require_once __DIR__ . '/../bootstrap.php';

use App\Core\Config;

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$response = ['status' => 'error', 'message' => 'Unknown endpoint'];

try {
    $endpoint = $_GET['endpoint'] ?? '';
    $method = $_SERVER['REQUEST_METHOD'];

    switch ($endpoint) {
        case 'dashboard':
            require __DIR__ . '/dashboard.php';
            break;
        case 'auth':
            require __DIR__ . '/auth.php';
            break;
        case 'users':
            require __DIR__ . '/users.php';
            break;
        case 'products':
            require __DIR__ . '/products.php';
            break;
        case 'articles':
            require __DIR__ . '/articles.php';
            break;
        case 'system':
            require __DIR__ . '/system.php';
            break;
        case 'config':
            $response = ['status' => 'ok', 'data' => ['registration_mode' => Config::get('auth.registration_mode')]];
            break;
        default:
            http_response_code(404);
            $response = ['status' => 'error', 'message' => "Endpoint '{$endpoint}' not found"];
    }
} catch (\InvalidArgumentException $e) {
    http_response_code(400);
    $response = ['status' => 'error', 'message' => $e->getMessage()];
} catch (\RuntimeException $e) {
    http_response_code(500);
    $response = ['status' => 'error', 'message' => $e->getMessage()];
} catch (\Exception $e) {
    http_response_code(500);
    $response = ['status' => 'error', 'message' => Config::get('app.debug') ? $e->getMessage() : 'Internal server error'];
}

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
