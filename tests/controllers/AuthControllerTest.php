<?php

namespace controllers;

use config\Database;
use helpers\JWTCodec;
use PDO;
use PDOStatement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../src/controllers/AuthController.php';
require_once __DIR__ . '/../../src/config/Database.php';

class AuthControllerTest extends TestCase {
    private AuthController $authController;
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
            'password_hash' => password_hash('password', PASSWORD_DEFAULT),
        ]);

        // Mock du PDO
        $mockPdo = $this->createMock(PDO::class);
        $mockPdo->method('prepare')->willReturn($this->mockStmt);

        // Mock du service Database
        $this->mockDb = $this->createMock(Database::class);
        $this->mockDb->method('getConnection')->willReturn($mockPdo);

        // Instanciation du contrôleur avec le mock de la base de données
        $this->authController = new AuthController($this->mockDb);
    }

    public function testLogin() {
        // Capture la sortie
        ob_start();

        $input = ['username' => 'testuser', 'password' => 'password'];
        $this->authController->login($input);
        $output = ob_get_clean();

        $response = json_decode($output, true);

        $this->assertArrayHasKey('access_token', $response);
        $this->assertArrayHasKey('refresh_token', $response);
        $this->assertIsString($response['access_token']);
        $this->assertIsString($response['refresh_token']);
    }

    public function testLoginWrongCredentials() {
        $expectedOutput = json_encode(['message' => 'Invalid login credentials']);
        // Capture la sortie
        ob_start();

        $input = ['username' => 'testuser', 'password' => 'iAmWrongPassword'];
        $this->authController->login($input);
        $output = ob_get_clean();

        $this->assertEquals($expectedOutput, $output);
    }

    public function testRefreshToken() {
        $codec = new JWTCodec($_ENV['SECRET_KEY']);
        $payload = [
            "sub" => 1,
            "exp" => time() + 3600,
        ];
        $refresh_token = $codec->encode($payload);

        $input = ['token' => $refresh_token];

        ob_start();
        $this->authController->refreshToken($input);
        $output = ob_get_clean();

        $response = json_decode($output, true);

        $this->assertArrayHasKey('access_token', $response);
        $this->assertArrayHasKey('refresh_token', $response);
        $this->assertIsString($response['access_token']);
        $this->assertIsString($response['refresh_token']);
    }

    public function testExpiredRefreshToken() {
        $expectedOutput = json_encode(['message' => 'Invalid token']);
        $codec = new JWTCodec($_ENV['SECRET_KEY']);
        $payload = [
            "sub" => 1,
            "exp" => time() - 3600,
        ];
        $refresh_token = $codec->encode($payload);

        $input = ['token' => $refresh_token];

        ob_start();
        $this->authController->refreshToken($input);
        $output = ob_get_clean();

        $this->assertEquals($expectedOutput, $output);
    }
}