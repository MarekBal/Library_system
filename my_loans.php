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

$sql = "SELECT id FROM users WHERE username='$username'";
$result = $conn->query($sql);
$user_id = $result->fetch_assoc()['id'];

$sql = "SELECT loans.id AS loan_id, books.title, books.author, loans.loan_date, loans.return_date FROM loans JOIN books ON loans.book_id = books.id WHERE loans.user_id='$user_id'";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <title>Moje výpožičky</title>
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
    <h1>Moje výpožičky</h1>
    <table>
        <tr>
            <th>Názov</th>
            <th>Autor</th>
            <th>Dátum zapožičania</th>
            <th>Dátum vrátenia</th>
            <th>Akcia</th>
        </tr>
        <?php
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                echo "<tr><td>" . $row["title"]. "</td><td>" . $row["author"]. "</td><td>" . $row["loan_date"]. "</td><td>" . $row["return_date"]. "</td><td><a href='return_book.php?loan_id=" . $row["loan_id"] . "'>Vrátiť</a></td></tr>";
            }
        } else {
            echo "<tr><td colspan='5'>Žiadne výpožičky</td></tr>";
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