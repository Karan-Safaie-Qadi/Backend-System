<?php

declare(strict_types=1);

use App\Services\AuthService;
use App\Core\Config;
use App\Auth\AccessControl;

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'POST':
        $action = $_GET['action'] ?? '';
        switch ($action) {
            case 'register':
                $user = AuthService::register($input);
                $response = ['status' => 'ok', 'message' => 'User registered', 'data' => $user];
                break;
            case 'login':
                $user = AuthService::login($input['username'] ?? '', $input['password'] ?? '', !empty($input['remember']));
                $response = ['status' => 'ok', 'message' => 'Login successful', 'data' => $user];
                break;
            case 'logout':
                AuthService::logout();
                $response = ['status' => 'ok', 'message' => 'Logged out'];
                break;
            case 'forgot_password':
                AuthService::forgotPassword($input['email'] ?? '');
                $response = ['status' => 'ok', 'message' => 'If email exists, reset link sent'];
                break;
            case 'reset_password':
                AuthService::resetPassword($input['token'] ?? '', $input['password'] ?? '');
                $response = ['status' => 'ok', 'message' => 'Password reset'];
                break;
            default:
                http_response_code(400);
                $response = ['status' => 'error', 'message' => "Unknown action: $action"];
        }
        break;
    case 'GET':
        $user = AuthService::getCurrentUser();
        $response = ['status' => 'ok', 'data' => [
            'logged_in' => AuthService::isLoggedIn(),
            'user' => $user,
            'registration_mode' => Config::get('auth.registration_mode'),
            'levels' => AccessControl::getLevels(),
        ]];
        break;
    default:
        http_response_code(405);
        $response = ['status' => 'error', 'message' => 'Method not allowed'];
}
