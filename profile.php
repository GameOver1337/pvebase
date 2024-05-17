<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Профиль</title>
    <link rel="stylesheet" href="/css/profile.css?<?php echo time();?>">
    <link rel="icon" type="image/png" href="/source/icon.png">
</head>
<body>
<div class="content">
    <?php
    // Include the database configuration file
    include 'db/db.conf';

    // Database connection details
    $servername = $db_config['servername'];
    $username = $db_config['username'];
    $password = $db_config['password'];
    $dbname = $db_config['dbname'];
    $port = $db_config['port'];

    $conn = new mysqli($servername, $username, $password, $dbname, $port);

    // Проверка соединения
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Проверка наличия guid в запросе
    if (isset($_GET['guid']) && !empty($_GET['guid'])) {
        // Защита от SQL инъекций
        $guid = $conn->real_escape_string($_GET['guid']);

        // Выполнение запроса к базе данных
        $sql = "SELECT * FROM time_search_player WHERE guid = $guid";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            // Вывод информации о персонаже
            while($row = $result->fetch_assoc()) {
                $charName = $row["charName"];
                $level = $row["level"];
                $faction = $row["faction"];
                $race = $row["race"];
                $class = $row["class"];
                $description = $row["description"];
                $realm = $row["realm"];
                $charLink = "https://cp.pandawow.me/armory/char-$realm-$guid.html";
            }
        } else {
            echo "Персонаж не найден";
        }
    } else {
        echo "Не указан GUID персонажа";
    }

    $conn->close();
    ?>

    <?php
    // Включаем библиотеку для парсинга HTML
    include('simple_html_dom.php');

    // Функция для получения всего HTML-кода страницы по URL
    function getPageHTML($url)
    {
        // Отправляем запрос на внешний сервер с использованием cURL, user-agent и куков
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

    // Функция для получения уникального файла куков
    function getCookieFile()
    {
        return sys_get_temp_dir() . '/cookie.txt';
    }

    // Функция для задержки перед запросом (можно настроить в соответствии с требованиями сайта)
    function delayRequest()
    {
        // Например, делаем задержку на 1 секунду
        sleep(1);
    }

    // Получаем HTML-код всей страницы
    $pageURL = "https://cp.pandawow.me/armory/char-$realm-$guid.html"; // Здесь укажите URL вашей страницы
    $html = getPageHTML($pageURL); 

    if ($html !== false) {
        // Парсим HTML с помощью Simple HTML DOM Parser
        $html = str_get_html($html);

        // Находим первый элемент с классом и удаляем его
        $armoryMainContent = $html->find('div[class=container]', 0);
        if ($armoryMainContent !== null) {
            $armoryMainContent->outertext = '';
        }
        $armoryMainContent = $html->find('div[class=container main_nav_wrapper clearfix]', 0);
        if ($armoryMainContent !== null) {
            $armoryMainContent->outertext = '';
        }
        $armoryMainContent = $html->find('div[class=content content_2 clearfix]', 0);
        if ($armoryMainContent !== null) {
            $armoryMainContent->outertext = '';
        }
        $armoryMainContent = $html->find('div[class=content content_2 content_3 clearfix]', 0);
        if ($armoryMainContent !== null) {
            $armoryMainContent->outertext = '';
        }
        $armoryMainContent = $html->find('div[class=content content_1]', 0);
        if ($armoryMainContent !== null) {
            $armoryMainContent->outertext = '';
        }
        $armoryMainContent = $html->find('header[class=inner_main_info]', 0);
        if ($armoryMainContent !== null) {
            $armoryMainContent->outertext = '';
        }
        $armoryMainContent = $html->find('footer[class=inner_main_info_footer]', 0);
        if ($armoryMainContent !== null) {
            $armoryMainContent->outertext = '';
        }
        $armoryMainContent = $html->find('div[class=model_button]', 0);
        if ($armoryMainContent !== null) {
            $armoryMainContent->outertext = '';
        }
        $armoryMainContent = $html->find('li[class=acount_1]', 0);
        if ($armoryMainContent !== null) {
            $armoryMainContent->outertext = '';
        }
        $armoryMainContent = $html->find('li[class=acount_2]', 0);
        if ($armoryMainContent !== null) {
            $armoryMainContent->outertext = '';
        }
        // Заменяем ссылки внутри тега <img>
        $raidContainer = $html->find('div[class=container clearfix]', 0);
        if ($raidContainer !== null) {
            $style = $raidContainer->style;
            $newStyle = str_replace("url('/icons/", "url('https://cp.pandawow.me/icons/", $style);
            $raidContainer->style = $newStyle;
        }
        foreach($html->find('li.inventory_item') as $li) {
            $style = $li->style;
            $newStyle = str_replace("url('/icons/", "url('https://cp.pandawow.me/icons/", $style);
            $li->style = $newStyle;
        }
        foreach($html->find('div.tab-page') as $div) {
            foreach($div->find('img') as $img) {
                $src = $img->src;
                $newSrc = str_replace("/icons/large/", "https://cp.pandawow.me/icons/large/", $src);
                $img->src = $newSrc;
            }
        }
        foreach($html->find('div.spec-tab.spec1') as $div) {
            foreach($div->find('img') as $img) {
                $src = $img->src;
                $newSrc = str_replace("/icons/large/", "https://cp.pandawow.me/icons/large/", $src);
                $img->src = $newSrc;
            }
        }
        foreach($html->find('div.spec-tab.spec2') as $div) {
            foreach($div->find('img') as $img) {
                $src = $img->src;
                $newSrc = str_replace("/icons/large/", "https://cp.pandawow.me/icons/large/", $src);
                $img->src = $newSrc;
            }
        }
        foreach($html->find('a.no-replace') as $a) {
            foreach($a->find('img') as $img) {
                $src = $img->src;
                $newSrc = str_replace("/icons/large/", "https://cp.pandawow.me/icons/large/", $src);
                $img->src = $newSrc;
            }
        }
        foreach($html->find('li.item') as $img) {
            $src = $img->src;
            $newSrc = str_replace("/icons/large/", "https://cp.pandawow.me/icons/large/", $src);
            $img->src = $newSrc;
        }
        foreach($html->find('ul.recent li.item') as $li) {
            $img = $li->find('img', 0);
            if ($img) {
                $src = $img->src;
                $newSrc = str_replace("/icons/large/", "https://cp.pandawow.me/icons/large/", $src);
                $img->src = $newSrc;
            }
        }
        foreach($html->find('ul.equipment.equipment_3 li.item') as $li) {
            $img = $li->find('img', 0);
            if ($img) {
                $src = $img->src;
                $newSrc = str_replace("/icons/equipment/", "https://cp.pandawow.me/icons/equipment/", $src);
                $img->src = $newSrc;
            }
        }
        foreach($html->find('img.icon-frame') as $img) {
            $src = $img->src;
            $newSrc = str_replace("https://cp.pandawow.mehttps://cp.pandawow.me/", "https://cp.pandawow.me/", $src);
            $img->src = $newSrc;
        }

        // Выводим основную информацию о персонаже
         echo "<div id='characterInfo'>";
        // echo "<h1>$charName</h1>";
        // echo "<p>Уровень: $level;</p>";
        // echo "<p>Фракция: $faction</p>";
        // echo "<p>Раса: $race</p>";
        // echo "<p>Класс: $class</p>";
         echo "<p>Коментарий: $description</p>";
         echo "<br><a href='$charLink' class='btn'>Ссылка на профиль</a><br />";
         echo "<br><a href='/' class='btn'>Вернуться назад</a><br />";
        // echo "</div>";

        // Выводим результат
        echo $html;
    } else {
        echo "Не удалось получить HTML-код страницы.";
    }
    ?>
</div>
<script src="js/showtab.js"></script>
<script src="js/widgets.js"></script>
<script src="js/widgets1.js"></script>
</body>
</html>
