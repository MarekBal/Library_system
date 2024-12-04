<?php
// Start session management
session_start();

// Include security functions
require_once 'security.php';

// Set secure headers
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("X-Content-Type-Options: nosniff");

// Process registration form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Verify CSRF token
    validateCSRFToken($_POST['csrf_token']);
    
    // Validate password strength
    $password = $_POST['password'];
    if (strlen($password) < 8 || 
        !preg_match('/[A-Z]/', $password) || 
        !preg_match('/[a-z]/', $password) || 
        !preg_match('/[0-9]/', $password)) {
        die("Heslo musí mať aspoň 8 znakov a obsahovať veľké, malé písmeno a číslo.");
    }

    // Database connection
    $conn = new mysqli('localhost', 'root', '', 'library_db');
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Check if username already exists
    $stmt = $conn->prepare("SELECT username FROM users WHERE username = ?");
    $stmt->bind_param("s", $_POST['username']);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        die("Používateľské meno už existuje.");
    }

    // Hash password securely
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert new user using prepared statement
    $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
    $stmt->bind_param("ss", $_POST['username'], $hashed_password);
    
    if ($stmt->execute()) {
        // Successful registration
        header("Location: login.php");
        exit();
    } else {
        $error = "Chyba pri registrácii: " . $conn->error;
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
    <title>Registrácia</title>
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

    <h1>Registrácia</h1>

    <!-- Display error messages if they exist -->
    <?php if (isset($error)): ?>
        <p class="error"><?php echo sanitizeOutput($error); ?></p>
    <?php endif; ?>

    <!-- Registration form with CSRF protection -->
    <form method="post" action="register.php">
        <!-- Hidden CSRF token field -->
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
        
        <label for="username">Používateľské meno:</label>
        <input type="text" id="username" name="username" required>
        <br>
        <label for="password">Heslo:</label>
        <input type="password" id="password" name="password" required 
               pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}"
               title="Heslo musí obsahovať aspoň 8 znakov, vrátane veľkého, malého písmena a čísla">
        <br>
        <button type="submit">Registrovať</button>
    </form>

    <p>Už máte účet? <a href="login.php">Prihláste sa</a></p>
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