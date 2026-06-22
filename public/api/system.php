<?php

declare(strict_types=1);

use App\Services\AdminService;
use App\Services\SystemService;
use App\Core\Config;

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $action = $_GET['action'] ?? 'info';
    switch ($action) {
        case 'info':
            $response = ['status' => 'ok', 'data' => AdminService::getSystemInfo()];
            break;
        case 'phpinfo':
            ob_start();
            phpinfo(INFO_GENERAL | INFO_CONFIGURATION | INFO_MODULES);
            $html = ob_get_clean();
            preg_match('/<table[^>]*>.*?<\/table>/s', $html, $matches);
            $response = ['status' => 'ok', 'data' => ['html' => $matches[0] ?? '']];
            break;
        case 'config':
            $response = ['status' => 'ok', 'data' => Config::all()];
            break;
        case 'methods':
            $response = ['status' => 'ok', 'data' => SystemService::getRegisteredMethods()];
            break;
        default:
            http_response_code(400);
            $response = ['status' => 'error', 'message' => "Unknown action: $action"];
    }
} elseif ($method === 'POST') {
    $action = $_GET['action'] ?? '';
    switch ($action) {
        case 'custom_query':
            $result = SystemService::customQuery($input['sql'] ?? '', $input['params'] ?? []);
            $response = ['status' => 'ok', 'data' => $result];
            break;
        case 'slug':
            $response = ['status' => 'ok', 'data' => SystemService::generateSlug($input['text'] ?? '')];
            break;
        case 'register_method':
            $response = ['status' => 'ok', 'message' => 'Method registered (session only)'];
            break;
        default:
            http_response_code(400);
            $response = ['status' => 'error', 'message' => "Unknown action: $action"];
    }
} else {
    http_response_code(405);
    $response = ['status' => 'error', 'message' => 'Method not allowed'];
}
