<?php
// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';

try {
    // Connect to MySQL without selecting a database
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS purchase_order_system");
    echo "Database created successfully.<br>";
    
    // Select the database
    $pdo->exec("USE purchase_order_system");
    
    // Create users table
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(80) UNIQUE NOT NULL,
        email VARCHAR(120) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        role VARCHAR(20) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "Users table created successfully.<br>";
    
    // Create purchase_orders table
    $pdo->exec("CREATE TABLE IF NOT EXISTS purchase_orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        po_number VARCHAR(20) UNIQUE NOT NULL,
        supplier VARCHAR(100) NOT NULL,
        date_created DATETIME NOT NULL,
        status VARCHAR(20) NOT NULL,
        total_amount DECIMAL(10, 2) NOT NULL,
        created_by INT NOT NULL,
        FOREIGN KEY (created_by) REFERENCES users(id)
    )");
    echo "Purchase orders table created successfully.<br>";
    
    // Create purchase_order_items table
    $pdo->exec("CREATE TABLE IF NOT EXISTS purchase_order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        po_id INT NOT NULL,
        item_name VARCHAR(100) NOT NULL,
        quantity INT NOT NULL,
        unit_price DECIMAL(10, 2) NOT NULL,
        total_price DECIMAL(10, 2) NOT NULL,
        FOREIGN KEY (po_id) REFERENCES purchase_orders(id)
    )");
    echo "Purchase order items table created successfully.<br>";
    
    // Check if admin user already exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = 'AXYS'");
    $stmt->execute();
    $adminExists = $stmt->fetchColumn();
    
    // Insert default admin user if not exists
    if (!$adminExists) {
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            'AXYS',
            'admin@example.com',
            '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password: admin123
            'admin'
        ]);
        echo "Default admin user created successfully.<br>";
    } else {
        echo "Admin user already exists.<br>";
    }
    
    echo "<br>Database setup completed successfully!<br>";
    echo "<a href='index.php'>Go to Homepage</a>";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?> 