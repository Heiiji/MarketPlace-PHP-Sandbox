<?php

namespace controllers;
use config\Database;
use gateways\UserGateway;

class UserController
{
    private UserGateway $gateway;

    public function __construct(private Database $database, private int $user_id)
    {
        $this->gateway = new UserGateway($database);
    }

    // TODO : paginated responses
    public function getAll()
    {
        echo json_encode($this->gateway->getAll());
    }

    public function create(): void
    {
        $data = (array)json_decode(file_get_contents("php://input"), true);

        $errors = $this->getValidationErrors($data);

        if (!empty($errors)) {
            $this->respondUnprocessableEntity($errors);
            return;
        }

        $id = $this->gateway->create($data);
        $this->respondCreated($id);
    }

    public function getById(array $vars): void
    {
        $id = $vars["id"];
        $user = $this->gateway->get($id);

        if ($user === false) {
            $this->respondNotFound($id);
            return;
        }

        echo json_encode($user);
    }

    public function update(array $vars): void
    {
        $id = $vars["id"];
        $data = (array)json_decode(file_get_contents("php://input"), true);

        $errors = $this->getValidationErrors($data, false);

        if (!empty($errors)) {
            $this->respondUnprocessableEntity($errors);
            return;
        }

        $rows = $this->gateway->update($id, $data);
        echo json_encode(["message" => "User updated", "rows" => $rows]);
    }

    public function delete(array $vars): void
    {
        $id = $vars["id"];
        $rows = $this->gateway->delete($id);
        echo json_encode(["message" => "User removed", "rows" => $rows]);
    }

    private function respondUnprocessableEntity(array $errors): void
    {
        http_response_code(422);
        echo json_encode(["errors" => $errors]);
    }

    private function respondNotFound(string $id): void
    {
        http_response_code(404);
        echo json_encode(["message" => "User with ID $id not found."]);
    }

    private function respondCreated(string $id): void
    {
        http_response_code(201);
        echo json_encode(["message" => "User created", "id" => $id]);
    }

    private function getValidationErrors(array $data, bool $is_new = true): array
    {
        $errors = [];

        if ($is_new && empty($data["username"])) {
            $errors[] = "Username is required";
        }

        if ($is_new && empty($data["password"])) {
            $errors[] = "Password is required";
        }

        if (!empty($data["age"]) && filter_var($data["age"], FILTER_VALIDATE_INT) === false) {
            $errors[] = "Age must be a int";
        }

        return $errors;
    }
}