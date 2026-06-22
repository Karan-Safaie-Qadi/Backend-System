<?php

use App\Services\AdminService;

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $response = ['status' => 'ok', 'data' => AdminService::getDashboardStats()];
} else {
    http_response_code(405);
    $response = ['status' => 'error', 'message' => 'Method not allowed'];
}
