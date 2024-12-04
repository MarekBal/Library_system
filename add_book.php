<?php
// Start session management
session_start();

// Include security functions
require_once 'security.php';

// Set secure headers
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("X-Content-Type-Options: nosniff");

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];

// Database connection
$conn = new mysqli('localhost', 'root', '', 'library_db');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check user role using prepared statement
$stmt = $conn->prepare("SELECT role FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$role = $result->fetch_assoc()['role'];

// Verify admin privileges
if ($role !== 'admin') {
    die("Nemáte oprávnenie na pridávanie kníh.");
}

// Process book addition form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Verify CSRF token
    validateCSRFToken($_POST['csrf_token']);

    // Sanitize and validate input data
    $title = trim($_POST['title']);
    $author = trim($_POST['author']);
    $published_year = (int)$_POST['published_year'];
    $genre = trim($_POST['genre']);

    // Validate input
    if (empty($title) || empty($author) || empty($genre)) {
        die("Všetky polia musia byť vyplnené.");
    }

    if ($published_year < 1000 || $published_year > date("Y")) {
        die("Neplatný rok vydania.");
    }

    // Insert new book using prepared statement
    $stmt = $conn->prepare("INSERT INTO books (title, author, published_year, genre) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssis", $title, $author, $published_year, $genre);
    
    if ($stmt->execute()) {
        $success = "Kniha bola úspešne pridaná!";
    } else {
        $error = "Chyba pri pridávaní knihy: " . $conn->error;
    }

    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <title>Pridať knihu</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Pridať knihu</h1>

    <!-- Display messages if they exist -->
    <?php if (isset($success)): ?>
        <p class="success"><?php echo sanitizeOutput($success); ?></p>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <p class="error"><?php echo sanitizeOutput($error); ?></p>
    <?php endif; ?>

    <!-- Book addition form with CSRF protection -->
    <form method="post" action="add_book.php">
        <!-- Hidden CSRF token field -->
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
        
        <label for="title">Názov:</label>
        <input type="text" id="title" name="title" required maxlength="255">
        <br>
        <label for="author">Autor:</label>
        <input type="text" id="author" name="author" required maxlength="255">
        <br>
        <label for="published_year">Rok vydania:</label>
        <input type="number" id="published_year" name="published_year" 
               required min="1000" max="<?php echo date('Y'); ?>">
        <br>
        <label for="genre">Žáner:</label>
        <input type="text" id="genre" name="genre" required maxlength="100">
        <br>
        <button type="submit">Pridať knihu</button>
    </form>

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