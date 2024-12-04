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

// Verify and sanitize book_id parameter
if (isset($_GET['book_id'])) {
    $book_id = filter_var($_GET['book_id'], FILTER_VALIDATE_INT);
    if ($book_id === false) {
        die("Neplatné ID knihy.");
    }
    
    $username = $_SESSION['username'];

    // Database connection
    $conn = new mysqli('localhost', 'root', '', 'library_db');
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Get user ID using prepared statement
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_id = $result->fetch_assoc()['id'];

    // Check book availability using prepared statement
    $stmt = $conn->prepare("SELECT copies FROM books WHERE id = ?");
    $stmt->bind_param("i", $book_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $copies = $result->fetch_assoc()['copies'];

    if ($copies > 0) {
        // Calculate loan and return dates
        $loan_date = date('Y-m-d');
        $return_date = date('Y-m-d', strtotime('+14 days'));

        // Begin transaction
        $conn->begin_transaction();

        try {
            // Create loan record using prepared statement
            $stmt = $conn->prepare("INSERT INTO loans (user_id, book_id, loan_date, return_date) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiss", $user_id, $book_id, $loan_date, $return_date);
            
            if ($stmt->execute()) {
                // Update book copies using prepared statement
                $stmt = $conn->prepare("UPDATE books SET copies = copies - 1 WHERE id = ?");
                $stmt->bind_param("i", $book_id);
                $stmt->execute();
                
                // Commit transaction
                $conn->commit();
                
                header("Location: my_loans.php");
                exit();
            }
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            die("Chyba pri spracovaní výpožičky: " . $e->getMessage());
        }
    } else {
        die("Všetky výtlačky tejto knihy sú momentálne vypožičané.");
    }

    // Clean up
    $stmt->close();
    $conn->close();
} else {
    die("Nebol zadaný žiadny ID knihy.");
}
?>