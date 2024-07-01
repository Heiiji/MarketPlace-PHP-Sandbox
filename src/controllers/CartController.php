<?php

namespace controllers;
use config\Database;
use gateways\CartGateway;
use gateways\EntitlementGateway;

class CartController
{
    private CartGateway $gateway;
    private EntitlementGateway $entitlement_gateway;

    public function __construct(private Database $database, private int $user_id)
    {
        $this->gateway = new CartGateway($database);
        $this->entitlement_gateway = new EntitlementGateway($database);
    }

    public function get(): void
    {
        echo json_encode($this->gateway->getUserCart($this->user_id));
    }

    public function add(): void
    {
        $data = (array)json_decode(file_get_contents("php://input"), true);

        $errors = $this->getValidationErrors($data);

        if (!empty($errors)) {
            $this->respondUnprocessableEntity($errors);
            return;
        }

        $this->gateway->addToCart($this->user_id, $data["listing_id"]);
        $this->respondCreated();
    }

    public function remove(array $vars): void
    {
        $id = $vars["id"];

        $elem = $this->gateway->get($id);

        if ($elem === false) {
            $this->respondNotFound($id);
            return;
        }
        $rows = $this->gateway->delete($id);
        echo json_encode(["message" => "Cart element removed", "rows" => $rows]);
    }

    public function checkout(): void
    {
        $listings = $this->gateway->getUserCart($this->user_id);
        $this->gateway->clearUserCart($this->user_id);
        $this->entitlement_gateway->addEntitlements($listings, $this->user_id);
    }

    private function respondUnprocessableEntity(array $errors): void
    {
        http_response_code(422);
        echo json_encode(["errors" => $errors]);
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