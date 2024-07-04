<?php

namespace controllers;

use config\Database;
use PDO;
use PDOStatement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../src/controllers/UserController.php';
require_once __DIR__ . '/../../src/config/Database.php';

class UserControllerTest extends TestCase {
    private UserController $userController;
    private MockObject $mockDb;
    private MockObject $mockStmt;

    protected function setUp(): void {
        // Mock du PDOStatement
        $this->mockStmt = $this->createMock(PDOStatement::class);

        // Mock de la méthode bindValue, execute et fetch de PDOStatement
        $this->mockStmt->method('bindValue')->willReturn(true);
        $this->mockStmt->method('execute')->willReturn(true);
        $this->mockStmt->method('fetch')->willReturn([
            'id' => 1,
            'username' => 'testuser',
            'api_key' => 'test',
            'age' => 10,
            'password_hash' => password_hash('password', PASSWORD_DEFAULT),
        ]);

        // Mock du PDO
        $mockPdo = $this->createMock(PDO::class);
        $mockPdo->method('prepare')->willReturn($this->mockStmt);

        // Mock du service Database
        $this->mockDb = $this->createMock(Database::class);
        $this->mockDb->method('getConnection')->willReturn($mockPdo);

        // Instanciation du contrôleur avec le mock de la base de données
        $this->userController = new UserController($this->mockDb, 1);
    }

    public function testGetUser() {
        // Capture la sortie
        ob_start();

        $input = ['id' => 1];
        $this->mockStmt->expects($this->once())->method('bindParam')->with(':id', 1)->willReturn(true);
        $this->userController->getById($input);
        $output = ob_get_clean();

        $response = json_decode($output, true);

        $this->assertArrayHasKey('username', $response);
        $this->assertArrayHasKey('api_key', $response);
        $this->assertIsString($response['username']);
        $this->assertIsString($response['api_key']);
    }
}