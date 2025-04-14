<?php
session_start();

// Check if user is verified
if (!isset($_SESSION['verified']) || $_SESSION['verified'] !== true) {
    header('Location: index.php');
    exit();
}

$success_message = '';
$error_message = '';

// Database connection
$conn = new mysqli('localhost', 'root', '', 'purchase_order_system');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $po_number = $_POST['po_number'] ?? '';
    $items = $_POST['items'] ?? [];
    $quantities = $_POST['quantities'] ?? [];
    $prices = $_POST['prices'] ?? [];
    $suppliers = $_POST['suppliers'] ?? [];
    
    if (!empty($po_number) && !empty($items)) {
        // Check if PO number already exists
        $check = $conn->prepare("SELECT po_number FROM purchase_orders WHERE po_number = ?");
        $check->bind_param("s", $po_number);
        $check->execute();
        $result = $check->get_result();
        
        if ($result->num_rows > 0) {
            $error_message = "PO Number already exists. Please use a different number.";
        } else {
            // Start transaction
            $conn->begin_transaction();
            
            try {
                // Insert into purchase_orders table
                $stmt = $conn->prepare("INSERT INTO purchase_orders (po_number, status) VALUES (?, 'Pending')");
                $stmt->bind_param("s", $po_number);
                $stmt->execute();
                
                // Insert items into purchase_order_items table
                $stmt = $conn->prepare("INSERT INTO purchase_order_items (po_number, item_description, supplier, quantity, unit_price) VALUES (?, ?, ?, ?, ?)");
                
                for ($i = 0; $i < count($items); $i++) {
                    $stmt->bind_param("sssid", 
                        $po_number,
                        $items[$i],
                        $suppliers[$i],
                        $quantities[$i],
                        $prices[$i]
                    );
                    $stmt->execute();
                }
                
                // Commit transaction
                $conn->commit();
                
                // Redirect to view_po.php
                header('Location: view_po.php?success=created');
                exit();
            } catch (Exception $e) {
                // Rollback transaction on error
                $conn->rollback();
                $error_message = "Error creating purchase order: " . $e->getMessage();
            }
        }
    } else {
        $error_message = "Please fill in all required fields.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Create Purchase Order - AXYS Premiums Unlimited, Inc.</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .header {
            background-color: white;
            padding: 15px 30px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logo {
            height: 50px;
        }
        .content {
            padding: 30px;
            max-width: 1200px;
            margin: 0 auto;
        }
        .form-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            color: #333;
        }
        input[type="text"],
        input[type="number"],
        select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .items-container {
            margin-top: 20px;
        }
        .item-row {
            display: grid;
            grid-template-columns: 2fr 2fr 1fr 1fr 1fr auto;
            gap: 10px;
            margin-bottom: 10px;
            align-items: end;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        .btn-primary {
            background-color: #8B008B;
            color: white;
        }
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        .btn:hover {
            opacity: 0.9;
        }
        .success-message {
            color: green;
            padding: 10px;
            background: #e8f5e9;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .error-message {
            color: red;
            padding: 10px;
            background: #ffebee;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .nav-btn {
            padding: 8px 20px;
            background-color: #8B008B;
            color: white;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            margin-left: 10px;
        }
        .nav-btn:hover {
            background-color: #6B006B;
        }
        .total-amount {
            margin-top: 20px;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 5px;
            text-align: right;
        }
        .total-amount-label {
            font-size: 18px;
            color: #333;
            margin-right: 10px;
        }
        .total-amount-value {
            font-size: 24px;
            font-weight: bold;
            color: #8B008B;
        }
        .item-total {
            text-align: right;
            font-weight: bold;
            color: #8B008B;
        }
        .po-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        .po-details .form-group {
            margin-bottom: 0;
        }
        .item-input-container {
            position: relative;
            display: flex;
            align-items: center;
        }
        .item-input {
            width: 100%;
            padding-right: 30px;
        }
        .item-select {
            position: absolute;
            right: 0;
            top: 0;
            height: 100%;
            width: 30px;
            border: none;
            background: transparent;
            cursor: pointer;
            opacity: 0.5;
        }
        .item-select:hover {
            opacity: 1;
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="image/logo.png" alt="AXYS Premiums Unlimited, Inc." class="logo">
        <div>
            <a href="dashboard.php" class="nav-btn">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="logout.php" class="nav-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <div class="content">
        <div class="form-container">
            <h1><i class="fas fa-file-circle-plus"></i> Create Purchase Order</h1>
            
            <?php if ($success_message): ?>
                <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <form method="POST" action="" id="poForm">
                <div class="po-details">
                    <div class="form-group">
                        <label for="po_number">PO Number:</label>
                        <input type="text" id="po_number" name="po_number" required>
                    </div>
                    <div class="form-group">
                        <label>Date Created:</label>
                        <input type="text" value="<?php echo date('Y-m-d'); ?>" readonly>
                    </div>
                </div>

                <div class="items-container">
                    <h3>Items</h3>
                    <div class="item-row">
                        <div>
                            <label>Item Description</label>
                        </div>
                        <div>
                            <label>Supplier</label>
                        </div>
                        <div>
                            <label>Quantity</label>
                        </div>
                        <div>
                            <label>Unit Price</label>
                        </div>
                        <div>
                            <label>Total</label>
                        </div>
                        <div>
                            <label>&nbsp;</label>
                        </div>
                    </div>
                    <div id="items-list">
                        <div class="item-row">
                            <div>
                                <div class="item-input-container">
                                    <input type="text" name="items[]" placeholder="Item Description" required class="item-input">
                                    <select class="item-select" onchange="updateItemInput(this)">
                                        <option value="">Select Item</option>
                                        <?php
                                        // Define the list of predefined items
                                        $predefinedItems = [
                                            "T-SHIRTS",
                                            "PRINTING",
                                            "EMBROIDERY",
                                            "JACKETS",
                                            "BAGS",
                                            "PENS",
                                            "NOTEBOOK",
                                            "MEMOPADS",
                                            "ID LACE",
                                            "ID HOLDER",
                                            "TUMBLERS",
                                            "PINS",
                                            "CAPS",
                                            "UMBRELLA",
                                            "LONGSLEEVES",
                                            "ARMSLEEVES",
                                            "STRESS BALL",
                                            "MEDICAL SUPPLIES",
                                            "FOLDERS",
                                            "OTHERS"
                                        ];
                                        foreach ($predefinedItems as $item):
                                        ?>
                                            <option value="<?php echo $item; ?>"><?php echo $item; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <input type="text" name="suppliers[]" placeholder="Supplier Name" required>
                            </div>
                            <div>
                                <input type="number" name="quantities[]" placeholder="Qty" min="1" required class="quantity">
                            </div>
                            <div>
                                <input type="number" name="prices[]" placeholder="Price" step="0.01" min="0" required class="price">
                            </div>
                            <div>
                                <input type="text" class="item-total" readonly>
                            </div>
                            <div>
                                <button type="button" class="btn btn-secondary" onclick="removeItem(this)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-secondary" onclick="addItem()">
                        <i class="fas fa-plus"></i> Add Item
                    </button>
                </div>

                <div class="total-amount">
                    <span class="total-amount-label">Total Amount:</span>
                    <span class="total-amount-value">₱0.00</span>
                </div>

                <div class="form-group" style="margin-top: 20px;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Create Purchase Order
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Define the list of predefined items
        const predefinedItems = [
            "T-SHIRTS",
            "PRINTING",
            "EMBROIDERY",
            "JACKETS",
            "BAGS",
            "PENS",
            "NOTEBOOK",
            "MEMOPADS",
            "ID LACE",
            "ID HOLDER",
            "TUMBLERS",
            "PINS",
            "CAPS",
            "UMBRELLA",
            "LONGSLEEVES",
            "ARMSLEEVES",
            "STRESS BALL",
            "MEDICAL SUPPLIES",
            "FOLDERS",
            "OTHERS"
        ];

        function addItem() {
            const itemsList = document.getElementById('items-list');
            const newItem = document.createElement('div');
            newItem.className = 'item-row';
            newItem.innerHTML = `
                <div>
                    <div class="item-input-container">
                        <input type="text" name="items[]" placeholder="Item Description" required class="item-input">
                        <select class="item-select" onchange="updateItemInput(this)">
                            <option value="">Select Item</option>
                            ${predefinedItems.map(item => `<option value="${item}">${item}</option>`).join('')}
                        </select>
                    </div>
                </div>
                <div>
                    <input type="text" name="suppliers[]" placeholder="Supplier Name" required>
                </div>
                <div>
                    <input type="number" name="quantities[]" placeholder="Qty" min="1" required class="quantity">
                </div>
                <div>
                    <input type="number" name="prices[]" placeholder="Price" step="0.01" min="0" required class="price">
                </div>
                <div>
                    <input type="text" class="item-total" readonly>
                </div>
                <div>
                    <button type="button" class="btn btn-secondary" onclick="removeItem(this)">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `;
            itemsList.appendChild(newItem);
            attachCalculationEvents(newItem);
        }

        function updateItemInput(select) {
            const input = select.parentElement.querySelector('.item-input');
            input.value = select.value;
        }

        function removeItem(button) {
            const itemsList = document.getElementById('items-list');
            if (itemsList.children.length > 1) {
                button.closest('.item-row').remove();
                calculateTotal();
            }
        }

        function calculateItemTotal(row) {
            const quantity = parseFloat(row.querySelector('.quantity').value) || 0;
            const price = parseFloat(row.querySelector('.price').value) || 0;
            const total = quantity * price;
            row.querySelector('.item-total').value = '₱' + total.toFixed(2);
            return total;
        }

        function calculateTotal() {
            const rows = document.querySelectorAll('.item-row');
            let total = 0;
            rows.forEach(row => {
                if (!row.querySelector('label')) { // Skip header row
                    total += calculateItemTotal(row);
                }
            });
            document.querySelector('.total-amount-value').textContent = '₱' + total.toFixed(2);
        }

        function attachCalculationEvents(row) {
            const quantityInput = row.querySelector('.quantity');
            const priceInput = row.querySelector('.price');
            
            quantityInput.addEventListener('input', () => {
                calculateItemTotal(row);
                calculateTotal();
            });
            
            priceInput.addEventListener('input', () => {
                calculateItemTotal(row);
                calculateTotal();
            });
        }

        // Attach events to initial row
        document.querySelectorAll('.item-row').forEach(row => {
            if (!row.querySelector('label')) { // Skip header row
                attachCalculationEvents(row);
            }
        });

        // Add CSS for the item input container
        document.head.insertAdjacentHTML('beforeend', `
            <style>
                .item-input-container {
                    position: relative;
                    display: flex;
                    align-items: center;
                }
                .item-input {
                    width: 100%;
                    padding-right: 30px;
                }
                .item-select {
                    position: absolute;
                    right: 0;
                    top: 0;
                    height: 100%;
                    width: 30px;
                    border: none;
                    background: transparent;
                    cursor: pointer;
                    opacity: 0.5;
                }
                .item-select:hover {
                    opacity: 1;
                }
            </style>
        `);

        // Initialize the first row with the dropdown
        document.addEventListener('DOMContentLoaded', function() {
            const firstRow = document.querySelector('.item-row');
            if (firstRow) {
                const itemInput = firstRow.querySelector('input[name="items[]"]');
                const itemInputContainer = document.createElement('div');
                itemInputContainer.className = 'item-input-container';
                
                const select = document.createElement('select');
                select.className = 'item-select';
                select.onchange = function() { updateItemInput(this); };
                select.innerHTML = `
                    <option value="">Select Item</option>
                    ${predefinedItems.map(item => `<option value="${item}">${item}</option>`).join('')}
                `;
                
                itemInput.parentNode.replaceChild(itemInputContainer, itemInput);
                itemInputContainer.appendChild(itemInput);
                itemInputContainer.appendChild(select);
            }
        });
    </script>
</body>
</html> 