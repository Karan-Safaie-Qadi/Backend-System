<?php

use App\Services\AuthService;
use App\Core\Config;

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?? [];

switch ($method) {
    case 'POST':
        $action = $_GET['action'] ?? '';

        switch ($action) {
            case 'register':
                $user = AuthService::register($input);
                $response = ['status' => 'ok', 'message' => 'User registered successfully', 'data' => $user];
                break;

            case 'login':
                $user = AuthService::login(
                    $input['username'] ?? '',
                    $input['password'] ?? '',
                    !empty($input['remember'])
                );
                $response = ['status' => 'ok', 'message' => 'Login successful', 'data' => $user];
                break;

            case 'logout':
                AuthService::logout();
                $response = ['status' => 'ok', 'message' => 'Logged out'];
                break;

            case 'forgot_password':
                AuthService::forgotPassword($input['email'] ?? '');
                $response = ['status' => 'ok', 'message' => 'If the email exists, a reset link has been sent'];
                break;

            case 'reset_password':
                AuthService::resetPassword($input['token'] ?? '', $input['password'] ?? '');
                $response = ['status' => 'ok', 'message' => 'Password has been reset'];
                break;

            default:
                http_response_code(400);
                $response = ['status' => 'error', 'message' => "Unknown action: $action"];
        }
        break;

    case 'GET':
        $user = AuthService::getCurrentUser();
        $isLoggedIn = AuthService::isLoggedIn();
        $mode = Config::get('auth.registration_mode');
        $response = [
            'status' => 'ok',
            'data' => [
                'logged_in' => $isLoggedIn,
                'user' => $user,
                'registration_mode' => $mode,
                'levels' => \App\Auth\AccessControl::getLevels(),
            ],
        ];
        break;

    default:
        http_response_code(405);
        $response = ['status' => 'error', 'message' => 'Method not allowed'];
}
