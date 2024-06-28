<?php

class CartController
{
    public function __construct(private CartGateway $gateway, private int $user_id)
    {
    }
    public function processRequest(string $method, ?string $id): void
    {
        if ($id === null) {
            if ($method == 'GET') {
                echo json_encode($this->gateway->getUserCart($this->user_id));
            } elseif ($method == "POST") {
                $data = (array) json_decode(file_get_contents("php://input"), true);

                $errors = $this->getValidationErrors($data);

                if (!empty($errors)) {
                    $this->respondUnprocessableEntity($errors);
                    return;
                }

                $this->gateway->addToCart($this->user_id, $data["listing_id"]);
                $this->respondCreated();
            } else {
                $this->respondMethodNotAllowed("GET, POST");
            }
        } else {
            $elem = $this->gateway->get($id);

            if ($elem === false) {
                $this->respondNotFound($id);
                return;
            }

            switch ($method) {
                case "DELETE":
                    $rows = $this->gateway->delete($id);
                    echo json_encode(["message" => "Cart element removed", "rows" => $rows]);
                    break;
                default:
                    $this->respondMethodNotAllowed("DELETE");
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
        echo json_encode(["message" => "Cart $id invalid."]);
    }

    private function respondCreated(): void
    {
        http_response_code(201);
        echo json_encode(["message" => "Listing added to cart"]);
    }

    private function getValidationErrors(array $data): array
    {
        $errors = [];

        if (empty($data["listing_id"])) {
            $errors[] = "Listing id is required";
        }

        return $errors;
    }
}