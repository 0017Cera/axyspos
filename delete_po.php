<?php
session_start();

// Check if user is verified
if (!isset($_SESSION['verified']) || $_SESSION['verified'] !== true) {
    header('Location: index.php');
    exit();
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'purchase_order_system');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get PO number from URL
if (!isset($_GET['po'])) {
    header('Location: view_po.php');
    exit();
}
$po_number = $_GET['po'];

// Check if PO exists
$query = "SELECT status FROM purchase_orders WHERE po_number = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $po_number);
$stmt->execute();
$result = $stmt->get_result();
$po = $result->fetch_assoc();

if (!$po) {
    header('Location: view_po.php?error=' . urlencode('Purchase order not found'));
    exit();
}

// Start transaction
$conn->begin_transaction();

try {
    // Delete items first
    $delete_items = "DELETE FROM purchase_order_items WHERE po_number = ?";
    $stmt = $conn->prepare($delete_items);
    $stmt->bind_param("s", $po_number);
    $stmt->execute();

    // Delete the purchase order
    $delete_po = "DELETE FROM purchase_orders WHERE po_number = ?";
    $stmt = $conn->prepare($delete_po);
    $stmt->bind_param("s", $po_number);
    $stmt->execute();

    // Commit transaction
    $conn->commit();
    
    // Delete the PDF file if it exists
    $pdf_file = 'pdfs/PO_' . $po_number . '.pdf';
    if (file_exists($pdf_file)) {
        unlink($pdf_file);
    }

    header('Location: view_po.php?success=deleted');
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    header('Location: view_po.php?error=' . urlencode('Error deleting purchase order: ' . $e->getMessage()));
}

$conn->close(); 