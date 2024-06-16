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
}