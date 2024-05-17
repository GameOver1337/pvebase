<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: /auth/login/");
    exit;
}

// Функция для задержки между запросами
function delayRequest($delaySeconds = 1.5)
{
    sleep($delaySeconds);
}

// Функция для получения уникального файла куков
function getCookieFile()
{
    return sys_get_temp_dir() . '/cookie.txt';
}

$class_Map = [
    "Воин" => "Воин",
    "Паладин" => "Паладин",
    "Охотник" => "Охотник",
    "Разбойник" => "Разбойник",
    "Жрец" => "Жрец",
    "Рыцарь Смерти" => "ДК",
    "Шаман" => "Шаман",
    "Маг" => "Маг",
    "Чернокнижник" => "Варлок",
    "Монах" => "Монах",
    "Друид" => "Друид"
];
$realms = [
    10 => "x100",
    5 => "x5",
    3 => "Fun"
];

// Функция для подключения к базе данных
function connectToDatabase()
{
    // Include the database configuration file
    include '../db/db.conf';

    // Database connection details
    $servername = $db_config['servername'];
    $username = $db_config['username'];
    $password = $db_config['password'];
    $dbname = $db_config['dbname'];
    $port = $db_config['port'];

    // Создаем соединение
    $conn = new mysqli($servername, $username, $password, $dbname, $port);
    $conn->set_charset("utf8mb4");

    // Проверяем соединение
    if ($conn->connect_error) {
        die("Ошибка подключения к базе данных: " . $conn->connect_error);
    }

    return $conn;
}

// Функция для получения имени пользователя из базы данных по его ID
function getUsernameFromDatabase($userId)
{
    $conn = connectToDatabase(); // Подключаемся к базе данных

    // Защита от SQL-инъекций
    $userId = $conn->real_escape_string($userId);

    // SQL-запрос для получения имени пользователя по ID
    $sql = "SELECT username FROM users WHERE id = '$userId'";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        // Получаем имя пользователя из результата запроса
        $row = $result->fetch_assoc();
        $username = $row['username'];

        // Проверяем, есть ли уже куки с именем пользователя
        if (isset($_COOKIE['username'])) {
            // Если куки существуют, получаем время последнего обновления
            $lastUpdateTime = $_COOKIE['last_update_time'];
            // Проверяем, прошло ли менее 24 часов с момента последнего обновления
            if (time() - $lastUpdateTime < 86400) { // 86400 секунд в сутках
                // Обновляем куки, устанавливая новое время истечения срока действия
                setcookie('username', $username, time() + (86400 * 1), "/");
                setcookie('last_update_time', time(), time() + (86400 * 1), "/");
            }
        } else {
            // Если куки не существуют, устанавливаем их
            setcookie('username', $username, time() + (86400 * 1), "/");
            setcookie('last_update_time', time(), time() + (86400 * 1), "/");
        }

        // Закрываем соединение с базой данных
        $conn->close();

        return $username;
    } else {
        // Если пользователь не найден, устанавливаем имя по умолчанию
        $username = 'Гость';

        // Закрываем соединение с базой данных
        $conn->close();

        return $username;
    }
}



// Получаем ID пользователя из сессии, если оно доступно
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : '';

// Получаем имя пользователя из базы данных
$username = getUsernameFromDatabase($user_id);

// Обработка формы при отправке
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Получаем данные из формы
    $playerName = $_POST['playerName'];
    $realm = $_POST['realm'];
    $description = $_POST['description']; // Добавлено поле описания
    // Получаем информацию об игроке
    $playerInfo = getPlayerInfo($playerName, $realm);

    // Проверяем, удалось ли получить информацию об игроке
    if ($playerInfo !== false) {
        // Создаем объект dom и загружаем данные
        $dom = new DOMDocument;
        @$dom->loadHTML($playerInfo);

        // Поиск таблицы
        $table = $dom->getElementById('yw0');

        if ($table) {
            // Таблица
            $rows = $table->getElementsByTagName('tr');

            // Извлекаем данные из первой строки (заголовки таблицы)
            $headerRow = $rows->item(0);
            $headers = $headerRow->getElementsByTagName('th');

            // Массив с табл.
            $playerData = [];

            // Извлекаем данные из оставшихся строк
            for ($i = 1; $i < $rows->length; $i++) {
                $row = $rows->item($i);
                $cells = $row->getElementsByTagName('td');

                if ($cells->length == $headers->length) {
                    for ($j = 0; $j < $cells->length; $j++) {
                        $playerData[$j] = $cells->item($j)->textContent;
                    }
                }
                if (isset($playerData[1])) {
                    preg_match("/\/char-\d+-(\d+)\.html/", $playerInfo, $matches);
                    $guid = isset($matches[1]) ? $matches[1] : "Не найдено";
                    $class = isset($class_Map[$playerData[4]]) ? $class_Map[$playerData[4]] : $playerData[4];
                    saveToDatabase($playerName, $playerData[1], $playerData[2], $playerData[3], $class, $guid, $description, $realm, $username);
                } else {
                    $GLOBALS['charAdded'] = "<p style='color: red; font-weight: bold; text-align: center;'>Персонаж не найден.</p>";
                }
            }
        }
    }
}

// Обработка запросов на удаление записей
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    // Получаем ID записи, которую нужно удалить
    $recordId = $_GET['id'];

    // Вызываем функцию для удаления записи из базы данных
    deleteRecord($recordId);
}

// Функция для удаления записи из базы данных
function deleteRecord($recordId)
{
    $conn = connectToDatabase();

    // SQL-запрос для удаления записи по ID
    $sql = "DELETE FROM time_search_player WHERE id = $recordId";

    if ($conn->query($sql) === TRUE) {
        $GLOBALS['recordDeleted'] = "<p style='color: green; font-weight: bold; text-align: center;'>Запись успешно удалена.</p>";
    } else {
        $GLOBALS['recordDeleted'] = "<p style='color: red; font-weight: bold; text-align: center;'>Ошибка при удалении записи: " . $conn->error . "</p>";
    }
    
    // Закрываем соединение
    $conn->close();

    // Переносим переадресацию и завершение скрипта после закрытия соединения
    header("Location: /add/");
    exit();
}


// Функция для получения списка записей из базы данных с пагинацией
function getRecordsFromDatabase($page = 1, $perPage = 5)
{
    $conn = connectToDatabase();
    $offset = ($page - 1) * $perPage;

    // SQL-запрос для получения записей с пагинацией
    $sql = "SELECT * FROM time_search_player LIMIT $offset, $perPage";
    $result = $conn->query($sql);

    $records = [];

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $records[] = $row;
        }
    }

    // Закрываем соединение
    $conn->close();

    return $records;
}

// Получаем список записей из базы данных с пагинацией
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$playerRecords = getRecordsFromDatabase($page);

// Функция для отправки запроса и получения данных по имени игрока
function getPlayerInfo($playerName, $realm)
{
    // Отправляем запрос на внешний сервер с использованием cURL, user-agent и куков
    $url = "https://cp.pandawow.me/armory.html?name=$playerName&realm=$realm";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36');
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 5);

    // Используем файл для хранения и отправки куков
    curl_setopt($ch, CURLOPT_COOKIEJAR, getCookieFile());
    curl_setopt($ch, CURLOPT_COOKIEFILE, getCookieFile());

    // Задержка перед запросом
    delayRequest();

    $response = curl_exec($ch);

    // Проверяем на ошибки выполнения запроса
    if (curl_errno($ch)) {
        $errorMessage = curl_error($ch);
        error_log("cURL Error: $errorMessage");
        return false; // Возвращаем false в случае ошибки
    }

    // Закрываем cURL соединение
    curl_close($ch);

    return $response;
}

// Функция для сохранения информации о персонаже в базе данных
function saveToDatabase($charName, $level, $faction, $race, $class, $guid, $description, $realm, $addedByUsername)
{
    $conn = connectToDatabase();

    // Преобразуем в UTF-8
    $charName = mb_convert_encoding($charName, 'UTF-8', 'auto');

    // Защита от SQL-инъекций и форматирование первой буквы
    $charName = ucwords($conn->real_escape_string($charName));
    $description = ucwords($conn->real_escape_string($description));

    // Устанавливаем кодировку для запроса
    $conn->set_charset("utf8");

    // Проверка наличия записи с таким же именем персонажа, реалмом и реалмом
    $checkDuplicateQuery = "SELECT id FROM time_search_player WHERE charName = '$charName' AND guid = '$guid'";
    $result = $conn->query($checkDuplicateQuery);

    if ($result !== FALSE) {
        if ($result->num_rows == 0) {
            // Записи с таким именем персонажа и реалмом нет, выполняем вставку
            // SQL-запрос для вставки данных
            $sql = "INSERT INTO time_search_player (charName, level, faction, race, class, guid, description, realm, addedByUsername)
                   VALUES ('$charName', '$level', '$faction', '$race', '$class', '$guid', '$description', '$realm', '$addedByUsername')";

            if ($conn->query($sql) === TRUE) {
                // После успешного добавления в базу данных, отправляем данные на вебхук Discord
                sendToDiscordWebhook($charName, $level, $faction, $race, $class, $realm, $description, $addedByUsername);
                
                $GLOBALS['charAdded'] = "<p style='color: green; font-weight: bold; text-align: center;'>Игрок $charName добавлен в базу.</p>";
            } else {
                $GLOBALS['charAdded'] = "<p style='color: red; font-weight: bold; text-align: center;'>Ошибка при выполнении запроса: " . $conn->error . "</p>";
            }
        } else {
            $GLOBALS['charAdded'] = "<p style='color: red; font-weight: bold; text-align: center;'>Запись с таким именем персонажа и реалмом уже существует.</p>";
        }
    } else {
        $GLOBALS['charAdded'] = "<p style='color: red; font-weight: bold; text-align: center;'>Ошибка при выполнении запроса: Номер:#" . $conn->error . "</p>";
    }

    // Закрываем соединение
    $conn->close();
}

// Функция для отправки данных на вебхук Discord
function sendToDiscordWebhook($charName, $level, $faction, $race, $class, $realm, $description, $addedByUsername)
{
    // Ваш URL вебхука Discord
    $webhookUrl = "https://discord.com/api/webhooks/1237410841343819868/yU6CzX94-BaESBQNSBDH79qgRsiYQYMu1SwsFlIy4eXTzXzg2_FzoFHr2bJedQLUmpf7";

    // Получаем название реалма по его идентификатору
    global $realms;
    $realmName = isset($realms[$realm]) ? $realms[$realm] : 'Неизвестный реалм';

    // Форматируем данные для отправки на вебхук
    $data = json_encode([
        "embeds" => [
            [
                "title" => ":new: PvE-шник добавлен в базу :new:",
                "fields" => [
                    ["name" => "", "value" => "**Добавил:**  :man_detective: => **$addedByUsername** <= :man_detective:", "inline" => false],
                    ["name" => "Имя", "value" => $charName, "inline" => true],
                    ["name" => "Уровень", "value" => $level, "inline" => true],
                    ["name" => "Фракция", "value" => $faction, "inline" => true],
                    ["name" => "Раса", "value" => $race, "inline" => true],
                    ["name" => "Класс", "value" => $class, "inline" => true],
                    ["name" => "Реалм", "value" => $realmName, "inline" => true],
                    ["name" => "Причина", "value" => $description, "inline" => false],
                    ["name" => "База:", "value" => "http://pvebas57.beget.tech/", "inline" => false]
                ],
                "color" => hexdec("#4e138a") // Зеленый цвет
            ]
        ]
    ]);

    // Настройка параметров запроса
    $options = [
        "http" => [
            "header" => "Content-Type: application/json",
            "method" => "POST",
            "content" => $data
        ]
    ];

    // Выполняем запрос на вебхук Discord
    $context = stream_context_create($options);
    $result = file_get_contents($webhookUrl, false, $context);

    // Проверяем результат запроса
    if ($result === false) {
        // Если есть ошибка, можно добавить обработку
        error_log("Failed to send data to Discord webhook!");
    }
}
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" href="/css/add.css?<?php echo time();?>">
    <link rel="icon" type="image/png" href="/source/icon.png">
    <title>Добавить PvE-шника</title>
</head>

<body>
<section class="container">
    <div class="user-info">
        <?php echo "Вы вошли как: <span>$username</span>"; ?>
        <a class="logout-link" href="/logout">Выход</a>
        <a class="logout-link" href="/">Главная</a>
    </div>
    <form id="uploadForm" method="post" enctype="multipart/form-data">
        <label for="playerName">Имя игрока:</label>
        <input type="text" name="playerName" id="playerName" placeholder="Hardsyle" required>
        <br>
        <label for="realm">Реалм:</label>
        <select name="realm" id="realm" required>
            <option value="10">x100</option>
            <option value="5">x5</option>
            <option value="3">Fun</option>
        </select>
        <br>
        <label for="description">Причина:</label>
        <textarea name="description" id="description"
                  placeholder="Крыса напала в крысу в спину, не оглянувшись назад и на зад..."></textarea>
        <br>
        <input type="submit" value="Добавить">
    </form>
    <?php echo isset($charAdded) ? $charAdded : ''; ?>
    <?php echo isset($recordDeleted) ? $recordDeleted : ''; ?>
    <table>
        <tr>
            <th>ID</th>
            <th>Имя</th>
            <th>Уровень</th>
            <th>Фракция</th>
            <th>Раса</th>
            <th>Класс</th>
            <th>Описание</th>
            <th>Реалм</th>
            <th>Добавил</th>
            <th>Действия</th>
        </tr>
        <?php foreach ($playerRecords as $record): ?>
            <tr>
                <td><?php echo $record['id']; ?></td>
                <td><?php echo $record['charName']; ?></td>
                <td><?php echo $record['level']; ?></td>
                <td><?php echo $record['faction']; ?></td>
                <td><?php echo $record['race']; ?></td>
                <td><?php echo $record['class']; ?></td>
                <td><?php echo $record['description']; ?></td>
                <td><?php echo isset($realms[$record['realm']]) ? $realms[$record['realm']] : 'Неизвестный реалм'; ?></td>
                <td><?php echo $record['addedByUsername']; ?></td>
                <td>
                    <a href="?action=delete&id=<?php echo $record['id']; ?>" onclick="return confirm('Вы уверены?')">Удалить</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
    <?php
    // Определяем общее количество записей в базе данных
    $conn = connectToDatabase();
    $totalRecordsQuery = "SELECT COUNT(id) as total FROM time_search_player";
    $totalRecordsResult = $conn->query($totalRecordsQuery);
    $totalRecords = $totalRecordsResult->fetch_assoc()['total'];

    // Определяем общее количество страниц для пагинации
    $totalPages = ceil($totalRecords / 5);

    // Выводим пагинацию
    echo "<div class='pagination'>";
    for ($i = 1; $i <= $totalPages; $i++) {
        echo "<a href='?page=$i'>$i</a>";
    }
    echo "</div>";

    // Закрываем соединение
    $conn->close();
    ?>
</section>
</body>

</html>
