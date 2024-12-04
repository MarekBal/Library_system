<?php
// Start session management
session_start();

// Include security functions
require_once 'security.php';

// Set secure headers
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("X-Content-Type-Options: nosniff");

// Process login form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Verify CSRF token
    validateCSRFToken($_POST['csrf_token']);
    
    // Track login attempts for rate limiting
    $_SESSION['login_attempts'] = isset($_SESSION['login_attempts']) ? $_SESSION['login_attempts'] + 1 : 1;
    if ($_SESSION['login_attempts'] > 5) {
        die("Too many login attempts. Please try again later.");
    }

    // Database connection
    $conn = new mysqli('localhost', 'root', '', 'library_db');
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Prepare and execute secure SQL query using prepared statement
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $_POST['username']);
    $stmt->execute();
    $result = $stmt->get_result();

    // Verify user credentials
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($_POST['password'], $row['password'])) {
            $_SESSION['username'] = $_POST['username'];
            unset($_SESSION['login_attempts']);
            session_regenerate_id(true);
            header("Location: index.php");
            exit();
        } else {
            $error = "Nesprávne heslo.";
        }
    } else {
        $error = "Používateľ neexistuje.";
    }

    // Clean up database resources
    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <title>BookNest</title>
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

    <h1>Prihlásenie</h1>
    
    <!-- Display error messages if they exist -->
    <?php if (isset($error)): ?>
        <p class="error"><?php echo sanitizeOutput($error); ?></p>
    <?php endif; ?>

    <!-- Login form with CSRF protection -->
    <form method="post" action="login.php">
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
        
        <label for="username">Používateľské meno:</label>
        <input type="text" id="username" name="username" required>
        <br>
        <label for="password">Heslo:</label>
        <input type="password" id="password" name="password" required>
        <br>
        <button type="submit">Prihlásiť</button>
    </form>

    <p>Nemáte účet? <a href="register.php">Zaregistrujte sa</a></p>
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
