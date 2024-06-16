<?php

require __DIR__ . "/vendor/autoload.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();

    $database = new Database($_ENV["DB_HOST"], $_ENV["DB_NAME"], $_ENV["DB_USER"], $_ENV["DB_PASS"]);

    $conn = $database->getConnection();

    $sql = "INSERT INTO user (username, password_hash, api_key, age)
            VALUES (:username, :password_hash, :api_key, :age)";
    $statement = $conn->prepare($sql);
    $statement->bindValue(":username", $_POST["username"]);

    $password_hash = password_hash($_POST["password"], PASSWORD_DEFAULT);
    $statement->bindValue(":password_hash", $password_hash);

    $api_key = bin2hex(random_bytes(16));
    $statement->bindValue(":api_key", $api_key);

    $statement->bindValue(":age", $_POST["age"]);

    $statement->execute();

    echo "Thanks for registering!, Your API key is : " . $api_key;
}

?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>Register</title>
    </head>
    <body>
    <h1>Register</h1>

    <form method="post">
        <label for="name">
            Username
            <input name="username" id="username">
        </label>
        <label for="name">
            Password
            <input name="password" type="password" id="password">
        </label>

        <button>Register</button>
    </form>
    </body>
</html>