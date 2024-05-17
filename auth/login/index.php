<?php
function connectToDatabase()
{
    // Include the database configuration file
    include '../../db/db.conf';

    // Database connection details
    $servername = $db_config['servername'];
    $username = $db_config['username'];
    $password = $db_config['password'];
    $dbname = $db_config['dbname'];
    $port = $db_config['port'];

    // Создаем соединение
    $conn = new mysqli($servername, $username, $password, $dbname, $port);

    // Проверяем соединение
    if ($conn->connect_error) {
        die("Ошибка подключения к базе данных: " . $conn->connect_error);
    }

    return $conn;
}

// Остальной код страницы login.php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Проверка учетных данных в базе данных
    $conn = connectToDatabase();
    $stmt = $conn->prepare("SELECT id, password FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($id, $storedPassword);
    $stmt->fetch();
    $stmt->close();

    if ($storedPassword === $password) {
        // Авторизация успешна, сохраняем сессию
        $_SESSION['user_id'] = $id;
        header("Location: /add");
        exit;
    } else {
        $error = "Неверное имя пользователя или пароль";
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Вход</title>
    <link rel="stylesheet" href="/css/login.css">
    <link rel="icon" type="image/png" href="/source/icon.png">
</head>
<body>
    <h2>Вход</h2>
    <?php if (isset($error)) echo "<p>$error</p>"; ?>
    <form method="post">
        <label for="username">Имя пользователя:</label>
        <input type="text" name="username" required><br>
        <label for="password">Пароль:</label>
        <input type="password" name="password" required><br>
        <input type="submit" value="Войти">
    </form>
</body>
</html>
