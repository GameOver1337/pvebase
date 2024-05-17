<?php
session_start();

// Удаляем все переменные сессии
$_SESSION = [];

// Уничтожаем сессию
session_destroy();

// Устанавливаем куки с истекшим сроком действия и пустым значением
setcookie(session_name(), '', time() - 3600, '/');

// Пытаемся удалить куку напрямую из заголовков ответа
if (isset($_SERVER['HTTP_COOKIE'])) {
    $cookies = explode(';', $_SERVER['HTTP_COOKIE']);
    foreach ($cookies as $cookie) {
        $parts = explode('=', $cookie);
        $name = trim($parts[0]);
        setcookie($name, '', time() - 3600);
        setcookie($name, '', time() - 3600, '/');
    }
}

// Перенаправляем на главную страницу
header("Location: /");
exit;
?>
