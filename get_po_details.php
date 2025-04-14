<?php
session_start();

// Check if user is verified
if (!isset($_SESSION['verified']) || $_SESSION['verified'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Check if PO number is provided
if (!isset($_GET['po'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Purchase order number is required']);
    exit();
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'purchase_order_system');
if ($conn->connect_error) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$po_number = $_GET['po'];

// Prepare statements to prevent SQL injection
$po_stmt = $conn->prepare("SELECT po_number, status, created_at FROM purchase_orders WHERE po_number = ?");
$po_stmt->bind_param("s", $po_number);
$po_stmt->execute();
$po_result = $po_stmt->get_result();
$po_data = $po_result->fetch_assoc();

if (!$po_data) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Purchase order not found']);
    exit();
}

// Get items
$items_stmt = $conn->prepare("SELECT item_description, supplier, quantity, unit_price 
                             FROM purchase_order_items 
                             WHERE po_number = ?");
$items_stmt->bind_param("s", $po_number);
$items_stmt->execute();
$items_result = $items_stmt->get_result();

$items = [];
while ($item = $items_result->fetch_assoc()) {
    $items[] = [
        'item_description' => $item['item_description'],
        'supplier' => $item['supplier'],
        'quantity' => $item['quantity'],
        'unit_price' => $item['unit_price']
    ];
}

// Get the supplier from the first item (since all items in a PO have the same supplier)
$supplier = !empty($items) ? $items[0]['supplier'] : '';

// Prepare response
$response = [
    'success' => true,
    'po' => [
        'po_number' => $po_data['po_number'],
        'supplier' => $supplier,
        'status' => $po_data['status'],
        'created_at' => $po_data['created_at']
    ],
    'items' => $items
];

// Send response
header('Content-Type: application/json');
echo json_encode($response);

// Close connections
$po_stmt->close();
$items_stmt->close();
$conn->close(); 