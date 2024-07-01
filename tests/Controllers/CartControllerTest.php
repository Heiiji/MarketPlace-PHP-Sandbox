<?php

namespace Controllers;

use config\Database;
use helpers\JWTCodec;
use PDO;
use PDOStatement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../src/controllers/CartController.php';
require_once __DIR__ . '/../../src/config/Database.php';

class CartControllerTest extends TestCase {
    private CartController $cartController;
    private MockObject $mockDb;
    private MockObject $mockStmt;

    protected function setUp(): void {
        // Mock du PDOStatement
        $this->mockStmt = $this->createMock(PDOStatement::class);

        // Mock de la méthode bindValue, execute et fetch de PDOStatement
        $this->mockStmt->method('bindValue')->willReturn(true);
        $this->mockStmt->method('bindParam')->willReturn(true);
        $this->mockStmt->method('execute')->willReturn(true);
        $this->mockStmt->method('fetchAll')->willReturn([
            [
                "id" => 1,
                "listing_id" => 2,
                "user_id" => 1,
                "created_at" => time(),
                "name" => "Test element",
                "description" => "A fantastic description",
                "price" => 2,
                "image" => null
            ],
            [
                "id" => 2,
                "listing_id" => 3,
                "user_id" => 1,
                "created_at" => time(),
                "name" => "Second element",
                "description" => "Another description",
                "price" => 0,
                "image" => null
            ]
        ]);

        // Mock du PDO
        $mockPdo = $this->createMock(PDO::class);
        $mockPdo->method('prepare')->willReturn($this->mockStmt);

        // Mock du service Database
        $this->mockDb = $this->createMock(Database::class);
        $this->mockDb->method('getConnection')->willReturn($mockPdo);

        // Instanciation du contrôleur avec le mock de la base de données
        $this->cartController = new CartController($this->mockDb, 1);
    }

    public function testGetCart() {
        $expectedOutput = json_encode([
            [
                "id" => 1,
                "listing_id" => 2,
                "user_id" => 1,
                "created_at" => time(),
                "name" => "Test element",
                "description" => "A fantastic description",
                "price" => 2,
                "image" => null
            ],
            [
                "id" => 2,
                "listing_id" => 3,
                "user_id" => 1,
                "created_at" => time(),
                "name" => "Second element",
                "description" => "Another description",
                "price" => 0,
                "image" => null
            ]
        ]);
        // Capture la sortie
        ob_start();

        $this->mockStmt->expects($this->once())->method('bindParam')->with(':id', 1)->willReturn(true);
        $this->cartController->get();
        $output = ob_get_clean();

        $this->assertEquals($expectedOutput, $output);
    }

    public function testAddToCart() {
        $expectedOutput = json_encode(["message" => "Listing added to cart"]);
        $input = ['listing_id' => 1];

        ob_start();
        $this->mockStmt->expects($this->once())->method('execute')->with(array(':listing_id' => 1, ':user_id' => 1, ':created_at' => time()))->willReturn(true);
        $this->cartController->add($input);
        $output = ob_get_clean();

        $this->assertEquals($expectedOutput, $output);
    }

    public function testAddToCartWithNoArg() {
        $expectedOutput = json_encode(["errors" => ["Listing id is required"]]);

        ob_start();
        $this->cartController->add([]);
        $output = ob_get_clean();

        $this->assertEquals($expectedOutput, $output);
    }
}