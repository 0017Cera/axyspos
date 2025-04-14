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

// Check for success message
$success_message = '';
if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'created':
            $success_message = 'Purchase Order created successfully!';
            break;
        case 'updated':
            $success_message = 'Purchase Order updated successfully!';
            break;
        case 'sent':
            $success_message = 'Purchase Order sent successfully!';
            break;
        case 'deleted':
            $success_message = 'Purchase Order deleted successfully!';
            break;
    }
}

// Check for error message
$error_message = '';
if (isset($_GET['error'])) {
    $error_message = htmlspecialchars($_GET['error']);
}

// Fetch individual purchase order items
$query = "SELECT 
            po.po_number, 
            po.status, 
            po.created_at, 
            poi.item_description, 
            poi.supplier, 
            poi.quantity, 
            poi.unit_price
          FROM purchase_orders po
          LEFT JOIN purchase_order_items poi ON po.po_number = poi.po_number
          ORDER BY po.created_at DESC, po.po_number, poi.id"; // Order to group items by PO

$result = $conn->query($query);

// Check if suppliers table exists
$suppliers = [];
$suppliers_result = $conn->query("SHOW TABLES LIKE 'suppliers'");
if ($suppliers_result && $suppliers_result->num_rows > 0) {
    // Fetch suppliers for the dropdown
    $suppliers_query = "SELECT name FROM suppliers ORDER BY name";
    $suppliers_result = $conn->query($suppliers_query);
    if ($suppliers_result) {
        while ($row = $suppliers_result->fetch_assoc()) {
            $suppliers[] = $row['name'];
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Purchase Orders - AXYS Premiums Unlimited, Inc.</title>
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
            height: 60px;
            width: auto;
            object-fit: contain;
            margin-right: 20px;
        }
        .content {
            padding: 15px;
            margin: 0 auto;
        }
        .table-container {
            background: white;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            overflow-x: auto;
            width: 100%;
        }
        .search-container {
            margin-bottom: 20px;
        }
        .search-input {
            padding: 10px;
            width: 300px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            min-width: 1200px;
        }
        th, td {
            padding: 10px 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
            vertical-align: middle;
        }
        th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #333;
            position: sticky;
            top: 0;
            z-index: 1;
        }
        tr:hover {
            background-color: #f5f5f5;
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
        .status {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-approved {
            background-color: #d4edda;
            color: #155724;
        }
        .status-rejected {
            background-color: #f8d7da;
            color: #721c24;
        }
        .status-draft {
            background-color: #e2e3e5;
            color: #383d41;
        }
        .items-list, .expand-btn {
            display: none;
        }
        .total-amount {
            font-weight: bold;
            color: #8B008B;
        }
        .success-message {
            color: green;
            padding: 15px;
            background: #e8f5e9;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        .error-message {
            color: red;
            padding: 15px;
            background: #ffebee;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        .action-btn {
            padding: 5px 10px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 12px;
            margin-right: 5px;
            text-decoration: none;
            display: inline-block;
        }
        .view-btn {
            background-color: #8B008B;
            color: white;
        }
        .edit-btn {
            background-color: #ffc107;
            color: #000;
        }
        .send-btn {
            background-color: #28a745;
            color: white;
        }
        .download-btn {
            background-color: #17a2b8;
            color: white;
        }
        .delete-btn {
            background-color: #dc3545;
            color: white;
        }
        
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 800px;
            border-radius: 5px;
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: black;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
        }
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .items-container {
            margin-top: 20px;
        }
        
        .item-row {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
        }
        
        .item-row input {
            flex: 1;
        }
        
        .btn-add-item {
            margin-top: 10px;
            padding: 8px 15px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .btn-remove-item {
            padding: 8px 15px;
            background-color: #f44336;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .btn-save {
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #2196F3;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
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
            <h1><i class="fas fa-list"></i> Purchase Orders</h1>
            
            <?php if ($success_message): ?>
            <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>
            <?php if ($error_message): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
            
            <div class="search-container">
            <input type="text" id="searchInput" class="search-input" placeholder="Search Purchase Orders...">
            <button id="exportBtn" class="nav-btn" style="margin-left: 10px;">
                <i class="fas fa-file-excel"></i> Export to Excel
            </button>
            </div>

        <div class="table-container">
            <table id="poTable">
                <thead>
                    <tr>
                        <th>PO Number</th>
                        <th>Supplier</th>
                        <th>Item Description</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th>Item Total</th>
                        <th>Status</th>
                        <th>Created Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                                    <?php
                        $current_po = null; 
                        $po_rowspan = 0; 
                        $po_items_data = [];

                        // First pass: Group items by PO and count rows needed
                        while($row = $result->fetch_assoc()) {
                            $po_items_data[$row['po_number']][] = $row;
                        }
                        // Reset result pointer
                        $result->data_seek(0);
                        $result = $conn->query($query); // Re-execute query if data_seek isn't reliable

                        while ($row = $result->fetch_assoc()): 
                            $item_total = $row['quantity'] * $row['unit_price'];
                            $is_first_item_of_po = ($current_po !== $row['po_number']);
                            if ($is_first_item_of_po) {
                                $current_po = $row['po_number'];
                                $po_rowspan = count($po_items_data[$current_po]); // Get rowspan count
                            }
                        ?>
                            <tr data-po="<?php echo htmlspecialchars($row['po_number']); ?>">
                                <?php if ($is_first_item_of_po): ?>
                                    <td rowspan="<?php echo $po_rowspan; ?>"><?php echo htmlspecialchars($row['po_number']); ?></td>
                                    <td rowspan="<?php echo $po_rowspan; ?>"><?php echo htmlspecialchars($row['supplier'] ?? 'N/A'); ?></td>
                                <?php endif; ?>
                                
                                <td><?php echo htmlspecialchars($row['item_description'] ?? 'N/A'); ?></td>
                                <td style="text-align: right;"><?php echo htmlspecialchars($row['quantity'] ?? 'N/A'); ?></td>
                                <td style="text-align: right;">₱<?php echo number_format($row['unit_price'] ?? 0, 2); ?></td>
                                <td style="text-align: right;">₱<?php echo number_format($item_total, 2); ?></td>
                                
                                <?php if ($is_first_item_of_po): ?>
                                    <td rowspan="<?php echo $po_rowspan; ?>">
                                        <span class="status status-<?php echo strtolower(htmlspecialchars($row['status'])); ?>">
                                            <?php echo htmlspecialchars($row['status']); ?>
                                    </span>
                                </td>
                                    <td rowspan="<?php echo $po_rowspan; ?>"><?php echo date("Y-m-d", strtotime($row['created_at'])); ?></td>
                                    <td class="actions" rowspan="<?php echo $po_rowspan; ?>">
                                         <a href="view_po_details.php?po=<?php echo urlencode($row['po_number']); ?>" class="action-btn view-btn" title="View Details"><i class="fas fa-eye"></i></a>
                                         <a href="download_po.php?po=<?php echo urlencode($row['po_number']); ?>" class="action-btn download-btn" title="Download PDF"><i class="fas fa-download"></i></a>
                                         <a href="send_email.php?po=<?php echo urlencode($row['po_number']); ?>" class="action-btn email-btn" title="Send Email"><i class="fas fa-paper-plane"></i></a>
                                        <!-- Add edit button if needed -->
                                        <!-- <a href="#" class="action-btn edit-btn" data-po="<?php echo htmlspecialchars($row['po_number']); ?>" title="Edit"><i class="fas fa-edit"></i></a> -->
                                        <a href="delete_po.php?po=<?php echo urlencode($row['po_number']); ?>" class="action-btn delete-btn" onclick="return confirm('Are you sure you want to delete PO <?php echo htmlspecialchars($row['po_number']); ?>?');" title="Delete"><i class="fas fa-trash"></i></a>
                                    </td>
                                    <?php endif; ?>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" style="text-align: center;">No purchase orders found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Edit Purchase Order</h2>
            <form id="editForm" method="POST" action="edit_po.php">
                <input type="hidden" name="po_number" id="edit_po_number">
                
                <div class="form-group">
                    <label for="edit_supplier">Supplier:</label>
                    <?php if (count($suppliers) > 0): ?>
                        <select id="edit_supplier" name="supplier" required>
                            <option value="">Select Supplier</option>
                            <?php foreach ($suppliers as $supplier): ?>
                                <option value="<?php echo htmlspecialchars($supplier); ?>"><?php echo htmlspecialchars($supplier); ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php else: ?>
                        <input type="text" id="edit_supplier" name="supplier" required>
                    <?php endif; ?>
                </div>
                
                <div class="items-container">
                    <h3>Items</h3>
                    <div id="itemsList"></div>
                    <button type="button" class="btn-add-item" onclick="addItemRow()">Add Item</button>
                </div>
                
                <button type="submit" class="btn-save">Save Changes</button>
            </form>
        </div>
    </div>

    <script>
        const searchInput = document.getElementById('searchInput');
            const table = document.getElementById('poTable');
        const tbody = table.getElementsByTagName('tbody')[0];
        const rows = tbody.getElementsByTagName('tr');

        // --- Improved Search Functionality (PO Number and Supplier Only) ---
        searchInput.addEventListener('keyup', function() {
            const filter = searchInput.value.toLowerCase().trim();
            const matchingPoNumbers = new Set();

            // 1. Identify POs matching the filter based on PO Number or Supplier
            if (filter !== '') {
                for (let i = 0; i < rows.length; i++) {
                const row = rows[i];
                    if (!row.hasAttribute('data-po')) continue;
                    
                    const poNumberAttr = row.getAttribute('data-po');
                    // Only check once per PO
                    if (matchingPoNumbers.has(poNumberAttr)) continue; 

                const cells = row.getElementsByTagName('td');
                    // Check if this row contains the PO number and supplier cells (has rowspan on first cell)
                    if (cells.length > 4 && cells[0].hasAttribute('rowspan')) {
                        const poNumberText = cells[0].textContent.toLowerCase();
                        const supplierText = cells[1].textContent.toLowerCase();

                        if (poNumberText.includes(filter) || supplierText.includes(filter)) {
                            matchingPoNumbers.add(poNumberAttr);
                        }
                    }
                }
            }

            // 2. Show/hide rows based on matching PO numbers
            for (let i = 0; i < rows.length; i++) {
                const row = rows[i];
                if (!row.hasAttribute('data-po')) continue;
                
                const poNumberAttr = row.getAttribute('data-po');
                const showRow = filter === '' || matchingPoNumbers.has(poNumberAttr);
                row.style.display = showRow ? "" : "none";
            }

            // 3. Adjust rowspans based on visible rows
            adjustRowspansAfterFilter();
        });

        // --- Function to Adjust Rowspans After Filtering ---
        function adjustRowspansAfterFilter() {
            const uniquePOs = new Set();
            // Collect all unique PO numbers from the dataset
            tbody.querySelectorAll('tr[data-po]').forEach(row => {
                uniquePOs.add(row.getAttribute('data-po'));
            });

            uniquePOs.forEach(poNumber => {
                const poRows = tbody.querySelectorAll(`tr[data-po="${poNumber}"]`);
                const visiblePoRows = Array.from(poRows).filter(row => row.style.display !== 'none');
                const visibleRowCount = visiblePoRows.length;
                const firstVisibleRow = visiblePoRows[0]; // Find the first row that is currently visible for this PO

                poRows.forEach(row => {
                    const cells = row.getElementsByTagName('td');
                    // Indices of cells that have rowspan in the initial full row
                    const rowspanIndices = [0, 1, 6, 7, 8]; // PO#, Supplier, Status, Date, Actions
                    
                    // Check if this row is the type that CAN have rowspan (more than 4 cells)
                    const isPotentiallyFirstRow = cells.length > 4;

                    if (isPotentiallyFirstRow) {
                        if (row === firstVisibleRow && visibleRowCount > 0) {
                            // This is the first *visible* row, apply/adjust rowspan
                            rowspanIndices.forEach(index => {
                                if (cells[index]) {
                                    cells[index].style.display = ""; // Ensure cell is visible
                                    cells[index].setAttribute('rowspan', visibleRowCount);
                                }
                            });
                        } else {
                            // This is NOT the first visible row (or all are hidden), hide the spanned columns
                            rowspanIndices.forEach(index => {
                                if (cells[index]) {
                                    cells[index].style.display = "none";
                                    // Optional: Remove rowspan if needed, but hiding is usually sufficient
                                    // cells[index].removeAttribute('rowspan'); 
                                }
                            });
                        }
                    } 
                    // No action needed for rows that only contain item details (4 cells)
                });
            });
        }

        // Initial adjustment on page load might be needed if default state isn't perfect
        // document.addEventListener('DOMContentLoaded', adjustRowspansAfterFilter); 
        // --- End of Improved Search --- 

        // --- Keep Modal functionality --- 
        const modal = document.getElementById('editModal');
        const span = document.getElementsByClassName('close')[0];
        // ... (rest of modal JS: openEditModal, span.onclick, window.onclick, addItemRow) ...
        // function openEditModal(poNumber) { ... }
        // span.onclick = function() { ... }
        // window.onclick = function(event) { ... }
        // function addItemRow(itemName = '', quantity = '', price = '') { ... }
        // document.addEventListener('DOMContentLoaded', function() { ... }); // Keep the part that adds listeners to edit buttons

        // --- Keep Export functionality --- 
        function escapeCSV(value) {
            if (value == null) return ''; // Handle null or undefined
            let stringValue = String(value);
            // Remove potential HTML tags (simple version)
            stringValue = stringValue.replace(/<[^>]*>/g, ''); 
            // Trim whitespace
            stringValue = stringValue.trim(); 
            // If the value contains a comma, double quote, or newline, enclose it in double quotes
            if (stringValue.includes(',') || stringValue.includes('"') || stringValue.includes('\n')) {
                // Escape existing double quotes by doubling them
                stringValue = stringValue.replace(/"/g, '""');
                stringValue = '"' + stringValue + '"';
            }
            return stringValue;
        }
        function exportTableToCSV(filename) {
            const table = document.getElementById("poTable");
            const headers = Array.from(table.querySelectorAll("thead th"))
                                .map(th => escapeCSV(th.textContent))
                                // Exclude the 'Actions' column header for export
                                .filter(header => header.toLowerCase() !== '"actions"'); 
            
            let csv = [headers.join(',')];
            let currentPOInfo = {};
            const visibleRows = table.querySelectorAll("tbody tr:not([style*='display: none'])");

            visibleRows.forEach(row => {
                const cells = Array.from(row.querySelectorAll("td"));
                let rowData = [];

                // Check if this row defines the PO details (has rowspan)
                if (cells.length > 4 && cells[0].hasAttribute('rowspan')) { 
                    currentPOInfo.poNumber = cells[0].textContent;
                    currentPOInfo.supplier = cells[1].textContent;
                    currentPOInfo.status = cells[6].textContent; // Status is at index 6
                    currentPOInfo.createdDate = cells[7].textContent; // Date is at index 7
                    
                    rowData.push(escapeCSV(currentPOInfo.poNumber));
                    rowData.push(escapeCSV(currentPOInfo.supplier));
                    rowData.push(escapeCSV(cells[2].textContent)); // Item Desc
                    rowData.push(escapeCSV(cells[3].textContent)); // Qty
                    rowData.push(escapeCSV(cells[4].textContent)); // Unit Price
                    rowData.push(escapeCSV(cells[5].textContent)); // Item Total
                    rowData.push(escapeCSV(currentPOInfo.status));
                    rowData.push(escapeCSV(currentPOInfo.createdDate));
                } else if (cells.length === 4) { // Subsequent item row
                    // Ensure we have current PO info (should always be true if data is sorted)
                    if (currentPOInfo.poNumber) {
                        rowData.push(escapeCSV(currentPOInfo.poNumber));
                        rowData.push(escapeCSV(currentPOInfo.supplier));
                        rowData.push(escapeCSV(cells[0].textContent)); // Item Desc
                        rowData.push(escapeCSV(cells[1].textContent)); // Qty
                        rowData.push(escapeCSV(cells[2].textContent)); // Unit Price
                        rowData.push(escapeCSV(cells[3].textContent)); // Item Total
                        rowData.push(escapeCSV(currentPOInfo.status));
                        rowData.push(escapeCSV(currentPOInfo.createdDate));
                    }
                } else if (cells.length > 4 && !cells[0].hasAttribute('rowspan')) {
                     // Handles case where filter might show a PO's first row without others
                     // Or if rowspan logic failed. Assumes first row has all data.
                    rowData.push(escapeCSV(cells[0].textContent)); // PO Num
                    rowData.push(escapeCSV(cells[1].textContent)); // Supplier
                    rowData.push(escapeCSV(cells[2].textContent)); // Item Desc
                    rowData.push(escapeCSV(cells[3].textContent)); // Qty
                    rowData.push(escapeCSV(cells[4].textContent)); // Unit Price
                    rowData.push(escapeCSV(cells[5].textContent)); // Item Total
                    rowData.push(escapeCSV(cells[6].textContent)); // Status
                    rowData.push(escapeCSV(cells[7].textContent)); // Date
                }

                // Only add if rowData was populated
                if (rowData.length > 0) {
                    csv.push(rowData.join(','));
                }
            });

            const bom = "\uFEFF"; // UTF-8 Byte Order Mark
            const csvString = bom + csv.join('\n'); // Prepend BOM to the CSV string
            const blob = new Blob([csvString], { type: 'text/csv;charset=utf-8;' });

            const link = document.createElement("a");
            if (link.download !== undefined) { // Feature detection
                const url = URL.createObjectURL(blob);
                link.setAttribute("href", url);
                link.setAttribute("download", filename);
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                URL.revokeObjectURL(url);
            } else {
                 alert("Your browser doesn't support direct file downloads. Please try a different browser.");
            }
        }
        document.getElementById('exportBtn').addEventListener('click', function() {
            const date = new Date();
            const timestamp = date.getFullYear().toString() +
                              (date.getMonth() + 1).toString().padStart(2, '0') +
                              date.getDate().toString().padStart(2, '0') + '_' +
                              date.getHours().toString().padStart(2, '0') +
                              date.getMinutes().toString().padStart(2, '0');
            exportTableToCSV(`purchase_orders_${timestamp}.csv`);
        });

    </script>
</body>
</html>
<?php $conn->close(); ?> 