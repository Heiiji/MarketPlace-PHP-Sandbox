<?php

class ListingGateway
{
    private PDO $conn;
    public function __construct(Database $database)
    {
        $this->conn = $database->getConnection();
    }

    public function getAll(): array
    {
        $sql = "SELECT * FROM listing ORDER BY name";

        $stmt = $this->conn->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function get(string $id): array | false
    {
        $sql = "SELECT * FROM listing WHERE id = :id";
        $state = $this->conn->prepare($sql);
        $state->bindParam(":id", $id, PDO::PARAM_INT);
        $state->execute();

        $data = $state->fetch(PDO::FETCH_ASSOC);

        return $data;
    }

    public function create(array $data): string
    {
        $sql = "INSERT INTO listing (name, description, price, image, author) VALUES (:name, :description, :price, :image, :author)";

        $state = $this->conn->prepare($sql);
        $state->bindParam(":name", $data["name"], PDO::PARAM_STR);
        $state->bindParam(":description", $data["description"], PDO::PARAM_STR);
        $state->bindParam(":price", $data["price"], PDO::PARAM_INT);
        $state->bindParam(":image", $data["image"], PDO::PARAM_STR);
        $mockedUserId = 0;
        $state->bindParam(":author", $mockedUserId, PDO::PARAM_INT); // TODO : remove test mock and have proper relation

        $state->execute();

        return $this->conn->lastInsertId();
    }
}