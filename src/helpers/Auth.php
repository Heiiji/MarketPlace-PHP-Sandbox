<?php

namespace helpers;

use config\Database;
use exceptions\TokenExpiredException;
use gateways\UserGateway;
use InvalidArgumentException;

class Auth
{
    private int $user_id;
    private UserGateway $user_gateway;
    private JWTCodec $codec;

    public function __construct()
    {
        $database = new Database($_ENV["DB_HOST"], $_ENV["DB_NAME"], $_ENV["DB_USER"], $_ENV["DB_PASS"]);
        $database->getConnection();

        $this->user_gateway = new UserGateway($database);
        $this->codec = new JWTCodec($_ENV["SECRET_KEY"]);
    }

    public function authenticateAPIKey(): bool
    {

        if (empty($_SERVER["HTTP_X_API_KEY"])) {
            http_response_code(400);
            echo json_encode(["message" => "Missing API key."]);
            return false;
        }

        $api_key = $_SERVER["HTTP_X_API_KEY"];

        $user = $this->user_gateway->getByAPIKey($api_key);
        if ($user === false) {
            http_response_code(401);
            echo json_encode(["message" => "Invalid api key."]);
            return false;
        }

        $this->user_id = $user["id"];

        return true;
    }

    public function getUserId(): int
    {
        return $this->user_id;
    }

    public function authenticateAccessToken(): bool
    {
        if (!preg_match("/^Bearer\s+(.*)$/", $_SERVER["HTTP_AUTHORIZATION"], $matches)) {
            http_response_code(400);
            echo json_encode(["message" => "Missing authorization header."]);
            return false;
        }

        try {
            $data = $this->codec->decode($matches[1]);
        } catch (InvalidArgumentException) {
            http_response_code(401);
            echo json_encode(["message" => "Invalid authorization header."]);
            return false;
        } catch (TokenExpiredException) {
            http_response_code(401);
            echo json_encode(["message" => "Token has expired."]);
            return false;
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode(["message" => $e->getMessage()]);
            return false;
        }

        $this->user_id = $data["sub"];

        return true;
    }
}