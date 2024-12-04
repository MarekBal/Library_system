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

// Verify and sanitize loan_id parameter
if (isset($_GET['loan_id'])) {
    $loan_id = filter_var($_GET['loan_id'], FILTER_VALIDATE_INT);
    if ($loan_id === false) {
        die("Neplatné ID výpožičky.");
    }

    // Database connection
    $conn = new mysqli('localhost', 'root', '', 'library_db');
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Begin transaction
    $conn->begin_transaction();

    try {
        // Get book_id using prepared statement
        $stmt = $conn->prepare("SELECT book_id FROM loans WHERE id = ?");
        $stmt->bind_param("i", $loan_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Výpožička nebola nájdená.");
        }
        
        $book_id = $result->fetch_assoc()['book_id'];

        // Delete loan record using prepared statement
        $stmt = $conn->prepare("DELETE FROM loans WHERE id = ?");
        $stmt->bind_param("i", $loan_id);
        
        if ($stmt->execute()) {
            // Update book copies using prepared statement
            $stmt = $conn->prepare("UPDATE books SET copies = copies + 1 WHERE id = ?");
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
        die("Chyba pri vracaní knihy: " . $e->getMessage());
    }

    // Clean up
    $stmt->close();
    $conn->close();
} else {
    die("Nebol zadaný žiadny ID výpožičky.");
}
?>