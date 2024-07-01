<?php

namespace controllers;
use config\Database;
use Exception;
use gateways\RefreshTokenGateway;
use gateways\UserGateway;
use helpers\JWTCodec;

class AuthController
{
    private UserGateway $gateway;
    private RefreshTokenGateway $refresh_token_gateway;

    public function __construct(private Database $database)
    {
        $this->gateway = new UserGateway($database);
        $this->refresh_token_gateway = new RefreshTokenGateway($database, $_ENV['SECRET_KEY']);
    }

    public function login(array $data) {
        $len = count($data);
        if ($len === 0) {
            $data = (array)json_decode(file_get_contents("php://input"), true);
        }

        if (!array_key_exists('username', $data) || !array_key_exists('password', $data)) {
            http_response_code(400);
            echo json_encode(["message" => "Missing login credentials"]);
            return;
        }

        $user = $this->gateway->getByUsername($data["username"]);

        if ($user === false) {
            http_response_code(401);
            echo json_encode(["message" => "Invalid login credentials"]);
            return;
        }

        if (!password_verify($data["password"], $user["password_hash"])) {
            http_response_code(401);
            echo json_encode(["message" => "Invalid login credentials"]);
            return;
        }

        $codec = new JWTCodec($_ENV['SECRET_KEY']);

        require __DIR__ . "/../helpers/tokens.php";

        $this->refresh_token_gateway->create($refresh_token, $refresh_token_expiry);
    }

    public function refreshToken(array $data) {
        $len = count($data);
        if ($len === 0) {
            $data = (array)json_decode(file_get_contents("php://input"), true);
        }

        if (!array_key_exists('token', $data)) {
            http_response_code(400);
            echo json_encode(["message" => "Missing token"]);
            exit;
        }

        $codec = new JWTCodec($_ENV['SECRET_KEY']);
        try {
            $payload = $codec->decode($data['token']);
        } catch (Exception) {
            http_response_code(400);
            echo json_encode(["message" => "Invalid token"]);
            return;
        }

        $user_id = $payload['sub'];

        $refresh_token = $this->refresh_token_gateway->getByToken($data["token"]);

        if ($refresh_token === false) {
            http_response_code(400);
            echo json_encode(["message" => "Invalid token"]);
            exit;
        }

        $user_gateway = new UserGateway($this->database);

        $user = $user_gateway->get($user_id);

        if ($user === false) {
            http_response_code(401);
            echo json_encode(["message" => "Invalid authentication"]);
            exit;
        }

        require __DIR__ . "/../helpers/tokens.php";

        $this->refresh_token_gateway->delete($data["token"]);
        $this->refresh_token_gateway->create($refresh_token, $refresh_token_expiry);
    }

    public function logout() {
        $data = (array)json_decode(file_get_contents("php://input"), true);

        if (!array_key_exists('token', $data)) {
            http_response_code(400);
            echo json_encode(["message" => "Missing token"]);
            exit;
        }

        $codec = new JWTCodec($_ENV['SECRET_KEY']);
        try {
            $payload = $codec->decode($data['token']);
        } catch (Exception) {
            http_response_code(400);
            echo json_encode(["message" => "Invalid token"]);
            exit;
        }

        $this->refresh_token_gateway->delete($data["token"]);
    }
}