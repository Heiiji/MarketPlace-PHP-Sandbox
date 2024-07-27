<?php

namespace controllers;

use config\Database;
use PDO;
use PDOStatement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../src/controllers/ListingController.php';
require_once __DIR__ . '/../../src/config/Database.php';

class ListingControllerTest extends TestCase {
    private ListingController $listingController;
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
                "name" => "Test element",
                "description" => "A fantastic description",
                "price" => 2,
                "image" => null
            ],
            [
                "id" => 2,
                "user_id" => 1,
                "name" => "Second element",
                "description" => "Another description",
                "price" => 0,
                "image" => null
            ]
        ]);

        // Mock du PDO
        $mockPdo = $this->createMock(PDO::class);
        $mockPdo->method('prepare')->willReturn($this->mockStmt);
        $mockPdo->method('lastInsertId')->willReturn('1');

        // Mock du service Database
        $this->mockDb = $this->createMock(Database::class);
        $this->mockDb->method('getConnection')->willReturn($mockPdo);

        // Instanciation du contrôleur avec le mock de la base de données
        $this->listingController = new ListingController($this->mockDb, 1);
    }

    public function testGetListing() {
        $expectedOutput = json_encode([
            [
                "id" => 1,
                "listing_id" => 2,
                "user_id" => 1,
                "name" => "Test element",
                "description" => "A fantastic description",
                "price" => 2,
                "image" => null
            ],
            [
                "id" => 2,
                "user_id" => 1,
                "name" => "Second element",
                "description" => "Another description",
                "price" => 0,
                "image" => null
            ]
        ]);
        // Capture la sortie
        ob_start();

        $this->listingController->getAll();
        $output = ob_get_clean();

        $this->assertEquals($expectedOutput, $output);
    }

    public function testCreateListing() {
        $input = ['name' => 'Example', 'price' => 1];

        // Capture la sortie
        ob_start();

        $this->listingController->create($input);
        $output = ob_get_clean();

        $response = json_decode($output, true);

        $this->assertArrayHasKey('message', $response);
        $this->assertArrayHasKey('id', $response);
        $this->assertIsString($response['message']);
        $this->assertIsString($response['id']);
    }
}