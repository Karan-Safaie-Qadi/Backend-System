<?php

namespace App\Services;

use App\Core\Config;
use App\Core\Session;
use App\Core\Mailer;
use App\Models\User;
use App\Models\ActivityLog;

class AuthService
{
    public static function register(array $data): array
    {
        $mode = Config::get('auth.registration_mode', 'email');
        $minPass = Config::get('auth.password_min_length', 8);
        $errors = [];

        if (empty($data['username'])) $errors[] = 'Username is required.';
        if (empty($data['password'])) $errors[] = 'Password is required.';
        elseif (strlen($data['password']) < $minPass) $errors[] = "Password must be at least $minPass characters.";

        if ($mode === 'email') {
            if (empty($data['email'])) $errors[] = 'Email is required.';
            elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email format.';
            elseif (User::findByEmail($data['email'])) $errors[] = 'Email already exists.';
        } else {
            if (empty($data['phone'])) $errors[] = 'Phone number is required.';
            elseif (User::findByPhone($data['phone'])) $errors[] = 'Phone already exists.';
        }

        if ($errors) throw new \InvalidArgumentException(implode(' ', $errors));
        if (User::findByUsername($data['username'])) throw new \InvalidArgumentException('Username already exists.');

        $userData = [
            'username' => $data['username'],
            'password' => password_hash($data['password'], PASSWORD_DEFAULT),
            'display_name' => $data['display_name'] ?? $data['username'],
            'access_level' => 1, 'is_active' => 1,
        ];
        if (!empty($data['email'])) $userData['email'] = $data['email'];
        if (!empty($data['phone'])) $userData['phone'] = $data['phone'];
        if (!empty($data['avatar'])) $userData['avatar'] = $data['avatar'];

        $userId = User::create($userData);
        $user = User::find($userId);
        ActivityLog::log($userId, 'register', 'user', $userId, 'User registered');
        return $user;
    }

    public static function login(string $username, string $password, bool $remember = false): array
    {
        $user = User::findByUsername($username) ?? User::findByEmail($username) ?? User::findByPhone($username);
        if (!$user) throw new \RuntimeException('Invalid credentials.');
        if (!User::isActive($user)) throw new \RuntimeException('Account is deactivated.');
        if (!password_verify($password, $user['password'])) throw new \RuntimeException('Invalid credentials.');

        User::updateLastLogin($user['id']);
        Session::set('user_id', $user['id']);
        Session::set('user_level', $user['access_level']);
        Session::set('user_name', $user['display_name']);

        if ($remember) {
            $token = bin2hex(random_bytes(32));
            User::setRememberToken($user['id'], $token);
            setcookie('remember_token', $token, time() + Config::get('auth.remember_lifetime', 604800), '/', '', false, true);
        }

        ActivityLog::log($user['id'], 'login', 'user', $user['id'], 'User logged in');
        return $user;
    }

    public static function logout(): void
    {
        $userId = Session::get('user_id');
        if ($userId) {
            User::clearRememberToken($userId);
            ActivityLog::log($userId, 'logout', 'user', $userId, 'User logged out');
        }
        Session::destroy();
        if (isset($_COOKIE['remember_token'])) setcookie('remember_token', '', time() - 3600, '/');
    }

    public static function loginWithCookie(): ?array
    {
        if (Session::has('user_id')) {
            $user = User::find(Session::get('user_id'));
            if ($user && User::isActive($user)) return $user;
            return null;
        }
        if (isset($_COOKIE['remember_token'])) {
            $user = User::findByRememberToken($_COOKIE['remember_token']);
            if ($user && User::isActive($user)) {
                Session::set('user_id', $user['id']);
                Session::set('user_level', $user['access_level']);
                Session::set('user_name', $user['display_name']);
                return $user;
            }
        }
        return null;
    }

    public static function forgotPassword(string $email): void
    {
        $user = User::findByEmail($email);
        if (!$user) return;
        $token = bin2hex(random_bytes(32));
        User::setPasswordResetToken($email, $token);
        $link = Config::get('app.url', 'http://localhost') . '/reset-password?token=' . $token;
        try {
            Mailer::send($email, 'Password Reset', "<h1>Reset Password</h1><a href='$link'>$link</a><p>Expires in 1 hour.</p>");
        } catch (\Exception $e) {}
        ActivityLog::log($user['id'], 'forgot_password', 'user', $user['id'], 'Password reset requested');
    }

    public static function resetPassword(string $token, string $password): void
    {
        $minPass = Config::get('auth.password_min_length', 8);
        if (strlen($password) < $minPass) throw new \InvalidArgumentException("Password must be at least $minPass characters.");
        $user = User::findByPasswordResetToken($token);
        if (!$user) throw new \RuntimeException('Invalid or expired reset token.');
        User::updatePassword($user['id'], password_hash($password, PASSWORD_DEFAULT));
        User::clearPasswordResetToken($user['id']);
        ActivityLog::log($user['id'], 'reset_password', 'user', $user['id'], 'Password reset completed');
    }

    public static function changePassword(int $userId, string $oldPassword, string $newPassword): void
    {
        $user = User::find($userId);
        if (!$user) throw new \RuntimeException('User not found.');
        if (!password_verify($oldPassword, $user['password'])) throw new \RuntimeException('Current password is incorrect.');
        $minPass = Config::get('auth.password_min_length', 8);
        if (strlen($newPassword) < $minPass) throw new \InvalidArgumentException("Password must be at least $minPass characters.");
        User::updatePassword($userId, password_hash($newPassword, PASSWORD_DEFAULT));
        ActivityLog::log($userId, 'change_password', 'user', $userId, 'Password changed');
    }

    public static function updateProfile(int $userId, array $data): array
    {
        $user = User::find($userId);
        if (!$user) throw new \RuntimeException('User not found.');
        $update = [];
        if (isset($data['display_name'])) $update['display_name'] = $data['display_name'];
        if (isset($data['email']) && $data['email'] !== $user['email']) {
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) throw new \InvalidArgumentException('Invalid email.');
            if (User::findByEmail($data['email'])) throw new \InvalidArgumentException('Email in use.');
            $update['email'] = $data['email'];
            $update['email_verified_at'] = null;
        }
        if (isset($data['phone']) && $data['phone'] !== $user['phone']) {
            if (User::findByPhone($data['phone'])) throw new \InvalidArgumentException('Phone in use.');
            $update['phone'] = $data['phone'];
            $update['phone_verified_at'] = null;
        }
        if (isset($data['avatar'])) $update['avatar'] = $data['avatar'];
        if ($update) {
            User::updateRecord($userId, $update);
            ActivityLog::log($userId, 'update_profile', 'user', $userId, 'Profile updated');
        }
        return User::find($userId);
    }

    public static function getCurrentUser(): ?array
    {
        $id = Session::get('user_id');
        return $id ? User::find($id) : null;
    }

    public static function isLoggedIn(): bool
    {
        return Session::has('user_id');
    }

    public static function getCurrentLevel(): int
    {
        return (int)Session::get('user_level', 0);
    }
}
