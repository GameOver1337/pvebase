<?php
// Include the database configuration file
include 'db/db.conf';

// Database connection details
$servername = $db_config['servername'];
$username = $db_config['username'];
$password = $db_config['password'];
$dbname = $db_config['dbname'];
$port = $db_config['port'];

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname, $port);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the table 'time_search_player' exists, if not, create it
$table_check_query = "SHOW TABLES LIKE 'time_search_player'";
$table_check_result = $conn->query($table_check_query);

if ($table_check_result->num_rows == 0) {
    // Table doesn't exist, create it
    $create_table_query = "CREATE TABLE `time_search_player` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `charName` varchar(255) NOT NULL,
        `level` int(11) NOT NULL,
        `faction` varchar(50) NOT NULL,
        `race` varchar(50) NOT NULL,
        `class` varchar(50) NOT NULL,
        `guid` int(11) NOT NULL,
        `description` text,
        `realm` int(11) NOT NULL,
        `addedByUsername` varchar(255) NOT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8";

    if ($conn->query($create_table_query) === TRUE) {
        echo "Table 'time_search_player' created successfully<br>";
    } else {
        echo "Error creating table 'time_search_player': " . $conn->error . "<br>";
    }
}

// Check if the table 'users' exists, if not, create it
$table_check_query = "SHOW TABLES LIKE 'users'";
$table_check_result = $conn->query($table_check_query);

if ($table_check_result->num_rows == 0) {
    // Table doesn't exist, create it
    $create_table_query = "CREATE TABLE `users` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `username` varchar(255) NOT NULL,
        `password` varchar(255) NOT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8";

    if ($conn->query($create_table_query) === TRUE) {
        // Insert default admin username and password
        $default_username = "admin";
        $default_password = "admin"; // Storing password as plain text
        $insert_default_user_query = "INSERT INTO users (username, password) VALUES ('$default_username', '$default_password')";
        if ($conn->query($insert_default_user_query) === TRUE) {
            echo "Table 'user' created successfully<br>";
            echo "Default user '$default_username' created successfully<br>";
        } else {
            echo "Error creating default user: " . $conn->error . "<br>";
        }
    } else {
        echo "Error creating table 'users': " . $conn->error . "<br>";
    }
}

// Close connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>База PvE-шников</title>
    <link rel="stylesheet" href="/css/index.css?<?php echo time();?>">
    <link rel="icon" type="image/png" href="/source/icon.png">
</head>
<body>
<?php
// Проверяем, установлена ли кука 'username'
if(isset($_COOKIE['username'])) {
    $username = $_COOKIE['username'];
    echo "<div id='welcome-message'><p>Привет, <span class='user'>$username</span>!</p></div>";
} else {
    echo "<div id='welcome-message'><p>Привет, <span class='guest'>Гость</span>!</p></div>";
}
?>
    <h2 style="text-align: center;">База PvE-шников</h2>

    <!-- Поле ввода для поиска по нику -->
    <div class="search-container">
        <input type="text" id="searchInput" oninput="searchPlayers()" placeholder="Найти PvE-шника...">
    </div>

    <!-- Кнопка для перехода на страницу администратора -->
    <div class="btn-wrapper">
        <a href="/add" class="btn">Добавить PvE-шника</a>
    </div>

    <!-- Таблица с данными -->
    <table>
        <thead>
            <tr>
                <th>id</th>
                <th>Ник</th>
                <th>Уровень</th>
                <th>Фракция</th>
                <th>Раса</th>
                <th>Класс</th>
                <th>guid</th>
                <th>Коментарий</th>
                <th>Реалм</th>
            </tr>
        </thead>
        <tbody>
            <?php
			// Database connection details
			$servername = $db_config['servername'];
			$username = $db_config['username'];
			$password = $db_config['password'];
			$dbname = $db_config['dbname'];
			$port = $db_config['port'];

            // Create connection
            $conn = new mysqli($servername, $username, $password, $dbname, $port);

            // Check connection
            if ($conn->connect_error) {
                die("Connection failed: " . $conn->connect_error);
            }

            // Set charset
            $conn->set_charset("utf8");

            // Realms array
            $realms = [
                10 => "x100",
                5 => "x5",
                3 => "Fun"
            ];

            // Pagination
            $limit = 20; // Records per page
            $page = isset($_GET['page']) ? $_GET['page'] : 1; // Current page
            $start = ($page - 1) * $limit; // Start position for query

            // Execute query
            $sql = "SELECT * FROM time_search_player LIMIT $start, $limit";
            $result = $conn->query($sql);

            // Output data from database as a table
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    // Replace realm number with corresponding value from $realms array
                    $realm = isset($realms[$row["realm"]]) ? $realms[$row["realm"]] : $row["realm"];
                    echo "<tr>";
                    echo "<td>" . $row["id"] . "</td>";
                    echo "<td><a class='player-link' href='profile.php?guid=" . $row['guid'] . "'>" . $row["charName"] . "</a></td>"; // Profile link with GUID
                    echo "<td>" . $row["level"] . "</td>";
                    echo "<td>" . $row["faction"] . "</td>";
                    echo "<td>" . $row["race"] . "</td>";
                    echo "<td>" . $row["class"] . "</td>";
                    echo "<td>" . $row["guid"] . "</td>";
                    echo "<td>" . $row["description"] . "</td>";
                    echo "<td>" . $realm . "</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='10' class='empty'>No players found</td></tr>";
            }
            $conn->close();
            ?>
        </tbody>
    </table>

    <!-- Pagination -->
    <div class="pagination">
        <?php
        // Count total records
        $conn = new mysqli($servername, $username, $password, $dbname, $port);
        $sql = "SELECT COUNT(id) AS total FROM time_search_player";
        $result = $conn->query($sql);
        $row = $result->fetch_assoc();
        $total_pages = ceil($row["total"] / $limit);

        // Output navigation buttons
        echo "<div style='text-align:center;margin-top:20px;'>";
        for ($i = 1; $i <= $total_pages; $i++) {
            echo "<a href='?page=" . $i . "'>" . $i . "</a>&nbsp;";
        }
        echo "</div>";
        ?>
    </div>

    <!-- JavaScript for searching by nickname -->
    <script>
        function searchPlayers() {
            // Get input value
            var input = document.getElementById("searchInput");
            var filter = input.value.toUpperCase();

            // Get table rows
            var table = document.querySelector("table");
            var rows = table.getElementsByTagName("tr");

            // Iterate through table rows and hide those that don't match the search query
            for (var i = 0; i < rows.length; i++) {
                var cell = rows[i].getElementsByTagName("td")[1]; // Search by second cell (nickname)
                if (cell) {
                    var charName = cell.textContent || cell.innerText;
                    if (charName.toUpperCase().indexOf(filter) > -1) {
                        rows[i].style.display = "";
                    } else {
                        rows[i].style.display = "none";
                    }
                }
            }
        }
    </script>
</body>
</html>
