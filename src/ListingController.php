<?php

class ListingController
{
    public function __construct(private ListingGateway $gateway, private int $user_id)
    {
    }
    public function processRequest(string $method, ?string $id): void
    {
        if (array_key_exists('user', $_GET)) {
            $userQuery = $_GET["user"];
        } else {
            $userQuery = false;
        }
        if ($id === null) {
            if ($method == 'GET') {
                if ($userQuery) {
                    echo json_encode($this->gateway->getAllFiltered($userQuery));
                } else {
                    echo json_encode($this->gateway->getAll());
                }
            } elseif ($method == "POST") {
                $data = (array) json_decode(file_get_contents("php://input"), true);

                $errors = $this->getValidationErrors($data);

                if (!empty($errors)) {
                    $this->respondUnprocessableEntity($errors);
                    return;
                }

                $id = $this->gateway->create($this->user_id, $data);
                $this->respondCreated($id);
            } else {
                $this->respondMethodNotAllowed("GET, POST");
            }
        } else {
            $listing = $this->gateway->get($id);

            if ($listing === false) {
                $this->respondNotFound($id);
                return;
            }

            switch ($method) {
                case "GET":
                    echo json_encode($listing);
                    break;
                case "PATCH":
                    $data = (array) json_decode(file_get_contents("php://input"), true);

                    $errors = $this->getValidationErrors($data, false);

                    if (!empty($errors)) {
                        $this->respondUnprocessableEntity($errors);
                        return;
                    }

                    $rows = $this->gateway->update($this->user_id, $id, $data);
                    echo json_encode(["message" => "Listing updated", "rows" => $rows]);
                    break;
                case "DELETE":
                    $rows = $this->gateway->delete($this->user_id, $id);
                    echo json_encode(["message" => "Listing removed", "rows" => $rows]);
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
        echo json_encode(["message" => "Listing with ID $id not found."]);
    }

    private function respondCreated(string $id): void
    {
        http_response_code(201);
        echo json_encode(["message" => "Listing created", "id" => $id]);
    }

    private function getValidationErrors(array $data, bool $is_new = true): array
    {
        $errors = [];

        if ($is_new && empty($data["name"])) {
            $errors[] = "Name is required";
        }

        if (empty($data["price"])) {
            $errors[] = "Price is required";
        } elseif (filter_var($data["price"], FILTER_VALIDATE_FLOAT) === false) {
            $errors[] = "Price must be a float";
        }

        return $errors;
    }
}