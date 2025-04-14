-- Create purchase_orders table
CREATE TABLE IF NOT EXISTS purchase_orders (
    po_number VARCHAR(20) PRIMARY KEY,
    status VARCHAR(20) DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create purchase_order_items table
CREATE TABLE IF NOT EXISTS purchase_order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    po_number VARCHAR(20),
    item_description TEXT,
    supplier VARCHAR(100),
    quantity INT,
    unit_price DECIMAL(10,2),
    FOREIGN KEY (po_number) REFERENCES purchase_orders(po_number) ON DELETE CASCADE
); 