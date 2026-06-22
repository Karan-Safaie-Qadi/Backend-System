<?php

use App\Services\AdminService;

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    $response = ['status' => 'error', 'message' => 'Method not allowed'];
} else {
    $response = ['status' => 'ok', 'data' => AdminService::getDashboardStats()];
}
