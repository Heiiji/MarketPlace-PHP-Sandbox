<?php

namespace gateways;
use PDO;

use config\Database;

class EntitlementGateway
{
    private PDO $conn;

    public function __construct(Database $database)
    {
        $this->conn = $database->getConnection();
    }

    public function get(string $id): array|false
    {
        $sql = "SELECT * FROM entitlement WHERE id = :id";
        $state = $this->conn->prepare($sql);
        $state->bindParam(":id", $id, PDO::PARAM_INT);
        $state->execute();

        $data = $state->fetch(PDO::FETCH_ASSOC);

        return $data;
    }

    public function getUserEntitlement(int $user_id): array
    {
        $sql = "SELECT * FROM entitlement INNER JOIN listing l ON l.id = entitlement.listing_id WHERE entitlement.user_id = :id";
        $state = $this->conn->prepare($sql);
        $state->bindParam(":id", $user_id, PDO::PARAM_INT);
        $state->execute();

        $data = $state->fetchAll(PDO::FETCH_ASSOC);

        return $data ?: [];
    }

    public function addEntitlement(int $user_id, int $listing_id): string
    {
        $sql = "INSERT INTO entitlement (listing_id, user_id, date)
            VALUES (:listing_id, :user_id, :date) ON DUPLICATE KEY UPDATE date = VALUES(date)";

        $statement = $this->conn->prepare($sql);
        $statement->bindValue(":listing_id", $listing_id);

        $statement->bindValue(":user_id", $user_id);

        $date = time();
        $statement->bindValue(":date", $date);

        $statement->execute();

        return $this->conn->lastInsertId();
    }

    public function delete(string $id): int
    {
        $sql = "DELETE FROM entitlement WHERE id = :id";

        $state = $this->conn->prepare($sql);
        $state->bindParam(":id", $id, PDO::PARAM_INT);

        $state->execute();

        return $state->rowCount();
    }

    public function addEntitlements(array $listings, int $user_id): bool {
        $this->conn->beginTransaction();
        try {
            $sql = "INSERT INTO entitlement (listing_id, user_id, date)
                    VALUES (:listing_id, :user_id, :date)";
            $stmt = $this->conn->prepare($sql);

            $date = time();
            foreach ($listings as $listing) {
                $stmt->bindValue(':listing_id', $listing['id'], PDO::PARAM_INT);
                $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
                $stmt->bindValue(':date', $date, PDO::PARAM_INT);
                $stmt->execute();
            }

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }
}