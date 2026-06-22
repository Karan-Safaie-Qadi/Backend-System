<?php

declare(strict_types=1);

use App\Services\UserService;
use App\Services\AuthService;

$method = $_SERVER['REQUEST_METHOD'];
$currentUser = AuthService::getCurrentUser();
$actorLevel = $currentUser['access_level'] ?? 0;

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            $response = ['status' => 'ok', 'data' => UserService::getUser((int)$_GET['id'])];
        } elseif (isset($_GET['search'])) {
            $response = ['status' => 'ok', 'data' => UserService::searchUsers($_GET['search'])];
        } elseif (isset($_GET['admins'])) {
            $response = ['status' => 'ok', 'data' => UserService::getAdmins()];
        } elseif (isset($_GET['stats'])) {
            $response = ['status' => 'ok', 'data' => UserService::getStats()];
        } else {
            $page = (int)($_GET['page'] ?? 1);
            $perPage = (int)($_GET['per_page'] ?? 20);
            $response = ['status' => 'ok', 'data' => UserService::getAllUsers($page, $perPage)];
        }
        break;
    case 'POST':
        $response = ['status' => 'ok', 'data' => UserService::createUser($input, $actorLevel)];
        break;
    case 'PUT':
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) throw new \InvalidArgumentException('User ID required');
        $response = ['status' => 'ok', 'data' => UserService::updateUser($id, $input, $actorLevel)];
        break;
    case 'DELETE':
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) throw new \InvalidArgumentException('User ID required');
        UserService::deleteUser($id, $actorLevel);
        $response = ['status' => 'ok', 'message' => 'User deleted'];
        break;
    default:
        http_response_code(405);
        $response = ['status' => 'error', 'message' => 'Method not allowed'];
}
