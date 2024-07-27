<?php

namespace controllers;
use config\Database;
use gateways\ListingGateway;

class ListingController
{
    private ListingGateway $gateway;

    public function __construct(private Database $database, private int $user_id)
    {
        $this->gateway = new ListingGateway($database);
    }

    // TODO : Paginated responses
    public function getAll(): void
    {
        if (array_key_exists('user', $_GET)) {
            $userQuery = $_GET["user"];
        } else {
            $userQuery = false;
        }
        if ($userQuery) {
            echo json_encode($this->gateway->getAllFiltered($userQuery));
        } else {
            echo json_encode($this->gateway->getAll());
        }
    }

    public function create(array $data): void
    {
        $len = count($data);
        if ($len === 0) {
            $data = (array)json_decode(file_get_contents("php://input"), true);
        }

        $errors = $this->getValidationErrors($data);

        if (!empty($errors)) {
            $this->respondUnprocessableEntity($errors);
            return;
        }

        $id = $this->gateway->create($this->user_id, $data);
        $this->respondCreated($id);
    }

    public function getById(array $vars): void
    {
        $id = $vars["id"];
        $listing = $this->gateway->get($id);

        if ($listing === false) {
            $this->respondNotFound($id);
            return;
        }
        echo json_encode($listing);
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

        $rows = $this->gateway->update($this->user_id, $id, $data);
        echo json_encode(["message" => "Listing updated", "rows" => $rows]);
    }

    public function delete(array $vars): void
    {
        $id = $vars["id"];
        $rows = $this->gateway->delete($this->user_id, $id);
        echo json_encode(["message" => "Listing removed", "rows" => $rows]);
    }

    private function respondUnprocessableEntity(array $errors): void
    {
        http_response_code(422);
        echo json_encode(["errors" => $errors]);
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