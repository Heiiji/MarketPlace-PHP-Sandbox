<?php

// TODO: make auth flow here instead of orphelin files

class AuthController {
    public function login($vars) {
        // signin
        echo json_encode(['message' => 'Login successful']);
    }

    public function register($vars) {
        // signup
        echo json_encode(['message' => 'Registration successful']);
    }

    public function refreshToken($vars) {
        // referesh token
        echo json_encode(['message' => 'Token refreshed']);
    }
}