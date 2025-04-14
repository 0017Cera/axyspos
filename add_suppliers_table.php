<?php
// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';

try {
    // Connect to MySQL
    $pdo = new PDO("mysql:host=$host;dbname=purchase_order_system", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create suppliers table
    $pdo->exec("CREATE TABLE IF NOT EXISTS suppliers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        contact_person VARCHAR(100),
        email VARCHAR(120),
        phone VARCHAR(20),
        address TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "Suppliers table created successfully.<br>";
    
    // Add some sample suppliers
    $suppliers = [
        ['ABC Supplies', 'John Doe', 'john@abcsupplies.com', '123-456-7890', '123 Main St, City'],
        ['XYZ Corporation', 'Jane Smith', 'jane@xyzcorp.com', '098-765-4321', '456 Market St, Town'],
        ['Global Trading', 'Mike Johnson', 'mike@globaltrading.com', '555-555-5555', '789 Commerce Ave, Village']
    ];
    
    $stmt = $pdo->prepare("INSERT INTO suppliers (name, contact_person, email, phone, address) VALUES (?, ?, ?, ?, ?)");
    
    foreach ($suppliers as $supplier) {
        try {
            $stmt->execute($supplier);
            echo "Added supplier: {$supplier[0]}<br>";
        } catch (PDOException $e) {
            // Skip if supplier already exists
            continue;
        }
    }
    
    echo "<br>Suppliers setup completed successfully!<br>";
    echo "<a href='view_po.php'>Go to Purchase Orders</a>";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?> 