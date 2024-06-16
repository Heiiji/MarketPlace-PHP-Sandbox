<?php

class ListingController
{
    public function __construct(private ListingGateway $gateway)
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
                    echo "update $id";
                    break;
                case "DELETE":
                    echo "delete $id";
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

    private function getValidationErrors(array $data): array
    {
        $errors = [];

        if (empty($data["name"])) {
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