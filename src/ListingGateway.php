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

    public function update(string $id, array $data): int
    {
        $fields = [];

        if (!empty($data["name"])) {
            $fields["name"] = [$data["name"], PDO::PARAM_STR];
        }

        if (array_key_exists("description", $data)) {
            $fields["description"] = [$data["description"], $data["description"] === null ? PDO::PARAM_NULL : PDO::PARAM_STR];
        }

        if (!empty($data["price"])) {
            $fields["price"] = [$data["price"], PDO::PARAM_INT];
        }

        if (array_key_exists("image", $data)) {
            $fields["image"] = [$data["image"], $data["image"] === null ? PDO::PARAM_NULL : PDO::PARAM_STR];
        }

        if (!empty($data["author"])) {
            $fields["author"] = [$data["author"], PDO::PARAM_INT];
        }

        if (empty($fields)) {
            return 0;
        } else {
            $changes = array_map(function ($value) {
                return "$value = :$value";
            }, array_keys($fields));
            $sql = "UPDATE listing SET "
                . implode(", ", $changes)
                . " WHERE id = :id";

            $state = $this->conn->prepare($sql);
            $state->bindParam(":id", $id, PDO::PARAM_INT);

            foreach ($fields as $field => $values) {
                $state->bindValue(":$field", $values[0], $values[1]);
            }

            $state->execute();

            return $state->rowCount();
        }
    }

    public function delete(string $id): int
    {
        $sql = "DELETE FROM listing WHERE id = :id";

        $state = $this->conn->prepare($sql);
        $state->bindParam(":id", $id, PDO::PARAM_INT);

        $state->execute();

        return $state->rowCount();
    }
}