<?php

namespace gateways;
use PDO;

use config\Database;

class UserGateway
{
    private PDO $conn;

    public function __construct(Database $database)
    {
        $this->conn = $database->getConnection();
    }

    public function getByAPIKey(string $apiKey): array|false
    {
        $sql = "SELECT * FROM user WHERE api_key = :api_key";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':api_key', $apiKey, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAll(): array
    {
        $sql = "SELECT * FROM user ORDER BY username";

        $stmt = $this->conn->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function get(int $id): array|false
    {
        $sql = "SELECT * FROM user WHERE id = :id";
        $state = $this->conn->prepare($sql);
        $state->bindParam(":id", $id, PDO::PARAM_INT);
        $state->execute();

        $data = $state->fetch(PDO::FETCH_ASSOC);

        return $data;
    }

    public function create(array $data): string
    {
        $sql = "INSERT INTO user (username, password_hash, api_key, age)
            VALUES (:username, :password_hash, :api_key, :age)";

        $statement = $this->conn->prepare($sql);
        $statement->bindValue(":username", $_POST["username"]);

        $password_hash = password_hash($_POST["password"], PASSWORD_DEFAULT);
        $statement->bindValue(":password_hash", $password_hash);

        $api_key = bin2hex(random_bytes(16));
        $statement->bindValue(":api_key", $api_key);

        $statement->bindValue(":age", $_POST["age"]);

        $statement->execute();

        return $this->conn->lastInsertId();
    }

    public function update(string $id, array $data): int
    {
        $fields = [];

        if (!empty($data["username"])) {
            $fields["username"] = [$data["username"], PDO::PARAM_STR];
        }
        if (!empty($data["password"])) {
            $fields["password"] = [$data["password"], PDO::PARAM_STR];
        }

        if (!empty($data["age"])) {
            $fields["age"] = [$data["age"], PDO::PARAM_INT];
        }

        if (empty($fields)) {
            return 0;
        } else {
            $changes = array_map(function ($value) {
                return "$value = :$value";
            }, array_keys($fields));
            $sql = "UPDATE user SET "
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
        $sql = "DELETE FROM user WHERE id = :id";

        $state = $this->conn->prepare($sql);
        $state->bindParam(":id", $id, PDO::PARAM_INT);

        $state->execute();

        return $state->rowCount();
    }

    public function getByUsername(string $username): array|false
    {
        $sql = "SELECT * FROM user WHERE username = :username";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":username", $username, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}