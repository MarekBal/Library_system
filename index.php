<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];

$conn = new mysqli('localhost', 'root', '', 'library_db');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT role FROM users WHERE username='$username'";
$result = $conn->query($sql);
$role = $result->fetch_assoc()['role'];

$sql = "SELECT * FROM books";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <title>Dostupné knihy</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div id="header-placeholder"></div>

    <script>
        fetch('header.html')
            .then(response => response.text())
            .then(data => {
            document.getElementById('header-placeholder').innerHTML = data;
        });
    </script>
    <h1>Dostupné knihy</h1>
    <div class="buttons">
        <button class="main-button"><a href="logout.php" class="button">Odhlásiť sa</a></button>
        <?php if ($role === 'admin'): ?>
            <button class="main-button"><a href="add_book.php" class="button">Pridať knihu</a></button>
        <?php endif; ?>
        <button class="main-button"><a href="my_loans.php" class="button">Moje výpožičky</a></button>
    </div>
    <table>
        <tr>
            <th>ID</th>
            <th>Názov</th>
            <th>Autor</th>
            <th>Rok vydania</th>
            <th>Žáner</th>
            <th>Dostupné výtlačky</th>
            <th>Akcia</th>
        </tr>
        <?php
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                echo "<tr><td>" . $row["id"]. "</td><td>" . $row["title"]. "</td><td>" . $row["author"]. "</td><td>" . $row["published_year"]. "</td><td>" . $row["genre"]. "</td><td>" . $row["copies"]. "</td><td><a href='loan.php?book_id=" . $row["id"] . "' >Zapožičať</a></td></tr>";
            }
        } else {
            echo "<tr><td colspan='7'>Žiadne knihy</td></tr>";
        }
        $conn->close();
        ?>
    </table>
    <div id="footer-placeholder"></div>
    <script>
        fetch('footer.html')
            .then(response => response.text())
            .then(data => {
            document.getElementById('footer-placeholder').innerHTML = data;
         });
    </script>

</body>
</html>