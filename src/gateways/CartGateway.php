<?php

namespace gateways;
use PDO;

use config\Database;

class CartGateway
{
    private PDO $conn;

    public function __construct(Database $database)
    {
        $this->conn = $database->getConnection();
    }

    public function get(string $id): array|false
    {
        $sql = "SELECT * FROM cart WHERE id = :id";
        $state = $this->conn->prepare($sql);
        $state->bindParam(":id", $id, PDO::PARAM_INT);
        $state->execute();

        $data = $state->fetch(PDO::FETCH_ASSOC);

        return $data;
    }

    public function getUserCart(int $user_id): array
    {
        $sql = "SELECT * FROM listing INNER JOIN cart c ON listing.id = c.listing_id AND c.user_id = :id";
        $state = $this->conn->prepare($sql);
        $state->bindParam(":id", $user_id, PDO::PARAM_INT);
        $state->execute();

        $data = $state->fetch(PDO::FETCH_ASSOC);

        return $data ?: [];
    }

    public function addToCart(int $user_id, int $listing_id): string
    {
        $sql = "INSERT INTO cart (listing_id, user_id, created_at)
            VALUES (:listing_id, :user_id, :created_at)";

        $statement = $this->conn->prepare($sql);
        $statement->bindValue(":listing_id", $listing_id);

        $statement->bindValue(":user_id", $user_id);

        $date = time();
        $statement->bindValue(":created_at", $date);

        $statement->execute();

        return $this->conn->lastInsertId();
    }

    public function delete(string $id): int
    {
        $sql = "DELETE FROM cart WHERE id = :id";

        $state = $this->conn->prepare($sql);
        $state->bindParam(":id", $id, PDO::PARAM_INT);

        $state->execute();

        return $state->rowCount();
    }
}