<?php

class UserController
{
    public function __construct(private UserGateway $gateway, private int $user_id)
    {
    }
    public function processRequest(string $method, ?string $id): void
    {
        if ($id === null) {
            if ($method == 'GET') {
                echo json_encode($this->gateway->getAll());
            } elseif ($method == "POST") {
                $data = (array) json_decode(file_get_contents("php://input"), true);

                $errors = $this->getValidationErrors($data);

                if (!empty($errors)) {
                    $this->respondUnprocessableEntity($errors);
                    return;
                }

                $id = $this->gateway->create($data);
                $this->respondCreated($id);
            } else {
                $this->respondMethodNotAllowed("GET, POST");
            }
        } else {
            $user = $this->gateway->get($id);

            if ($user === false) {
                $this->respondNotFound($id);
                return;
            }

            switch ($method) {
                case "GET":
                    echo json_encode($user);
                    break;
                case "PATCH":
                    $data = (array) json_decode(file_get_contents("php://input"), true);

                    $errors = $this->getValidationErrors($data, false);

                    if (!empty($errors)) {
                        $this->respondUnprocessableEntity($errors);
                        return;
                    }

                    $rows = $this->gateway->update($id, $data);
                    echo json_encode(["message" => "User updated", "rows" => $rows]);
                    break;
                case "DELETE":
                    $rows = $this->gateway->delete($id);
                    echo json_encode(["message" => "User removed", "rows" => $rows]);
                    break;
                default:
                    $this->respondMethodNotAllowed("GET, PATCH, DELETE");
                    break;
            }
        }
    }

    private function respondUnprocessableEntity(array $errors): void
    {
        http_response_code(422);
        echo json_encode(["errors" => $errors]);
    }

    private function respondMethodNotAllowed(string $allowed_methods): void
    {
        http_response_code(405);
        header("Allow: $allowed_methods");
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