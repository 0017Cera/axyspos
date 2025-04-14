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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $po_number = $_POST['po_number'];
    $supplier = $_POST['supplier'];
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Delete existing items
        $delete_items = "DELETE FROM purchase_order_items WHERE po_number = ?";
        $stmt = $conn->prepare($delete_items);
        $stmt->bind_param("s", $po_number);
        $stmt->execute();
        
        // Insert new items
        if (isset($_POST['items']) && is_array($_POST['items'])) {
            $insert_item = "INSERT INTO purchase_order_items (po_number, item_description, supplier, quantity, unit_price) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_item);
            
            foreach ($_POST['items'] as $index => $item) {
                $quantity = $_POST['quantities'][$index];
                $unit_price = $_POST['prices'][$index];
                
                $stmt->bind_param("sssdd", $po_number, $item, $supplier, $quantity, $unit_price);
                $stmt->execute();
            }
        }
        
        // Commit transaction
        $conn->commit();
        header("Location: view_po.php?success=updated");
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        header("Location: view_po.php?error=" . urlencode("Failed to update purchase order: " . $e->getMessage()));
        exit();
    }
}

// If it's a GET request to fetch PO details
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['po_number'])) {
    $po_number = $_GET['po_number'];
    
    // Fetch PO details
    $query = "SELECT po.*, poi.supplier 
             FROM purchase_orders po 
             LEFT JOIN purchase_order_items poi ON po.po_number = poi.po_number 
             WHERE po.po_number = ? 
             LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $po_number);
    $stmt->execute();
    $po_result = $stmt->get_result();
    $po_data = $po_result->fetch_assoc();
    
    // Fetch PO items
    $items_query = "SELECT * FROM purchase_order_items WHERE po_number = ?";
    $stmt = $conn->prepare($items_query);
    $stmt->bind_param("s", $po_number);
    $stmt->execute();
    $items_result = $stmt->get_result();
    $items = [];
    
    while ($row = $items_result->fetch_assoc()) {
        $items[] = $row;
    }
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode([
        'po' => $po_data,
        'items' => $items
    ]);
    exit();
}
?> 