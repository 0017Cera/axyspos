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

// Fetch PO details
$query = "SELECT po.*, 
          GROUP_CONCAT(poi.item_description SEPARATOR '||') as items,
          GROUP_CONCAT(poi.supplier SEPARATOR '||') as suppliers,
          GROUP_CONCAT(poi.quantity SEPARATOR '||') as quantities,
          GROUP_CONCAT(poi.unit_price SEPARATOR '||') as prices,
          SUM(poi.quantity * poi.unit_price) as total_amount
          FROM purchase_orders po
          LEFT JOIN purchase_order_items poi ON po.po_number = poi.po_number
          WHERE po.po_number = ?
          GROUP BY po.po_number";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $po_number);
$stmt->execute();
$result = $stmt->get_result();
$po_data = $result->fetch_assoc();

if (!$po_data) {
    header('Location: view_po.php');
    exit();
}

// Check if print view is requested
$print_view = isset($_GET['print']);
$download = isset($_GET['download']);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Purchase Order #<?php echo htmlspecialchars($po_number); ?> - AXYS Premiums Unlimited, Inc.</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @page {
            size: legal;
            margin: 0.5in;
        }
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: <?php echo $print_view ? '#fff' : '#f4f4f4'; ?>;
            width: 100%;
            min-height: 100vh;
        }
        .header {
            background-color: white;
            padding: 15px 30px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .logo {
            height: 50px;
        }
        .content {
            padding: 30px;
            max-width: 1200px;
            margin: 0 auto;
        }
        .po-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 40px;
        }
        .po-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 30px;
        }
        .company-info {
            text-align: right;
        }
        .company-info h1 {
            color: #8B008B;
            margin: 0;
            font-size: 24px;
        }
        .company-info p {
            margin: 5px 0;
            font-size: 14px;
        }
        .po-title {
            text-align: center;
            font-size: 24px;
            margin: 20px 0;
            color: #8B008B;
        }
        .po-details {
            margin-bottom: 30px;
        }
        .po-details p {
            margin: 10px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border: 1px solid #ddd;
        }
        th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #333;
        }
        .total-row {
            font-weight: bold;
            background-color: #f8f9fa;
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
        .terms {
            font-size: 12px;
            margin-top: 30px;
        }
        .terms h3 {
            margin-bottom: 10px;
        }
        .terms ol {
            margin: 0;
            padding-left: 20px;
        }
        .terms li {
            margin-bottom: 5px;
        }
        .signature-box {
            margin-top: 30px;
            padding: 15px;
            border: 1px solid #ddd;
            text-align: center;
        }
        .signature-line {
            margin-top: 50px;
            border-top: 1px solid #000;
            padding-top: 5px;
            width: 300px;
            margin-left: auto;
            margin-right: auto;
        }
        @media print {
            .no-print {
                display: none;
            }
            .po-container {
                box-shadow: none;
                padding: 0;
            }
            body {
                background-color: #fff;
            }
        }
    </style>
</head>
<body>
    <?php if (!$print_view): ?>
    <div class="header no-print">
        <img src="image/logo.png" alt="AXYS Premiums Unlimited, Inc." class="logo">
        <div>
            <a href="view_po.php" class="nav-btn">
                <i class="fas fa-list"></i> Back to List
            </a>
            <a href="dashboard.php" class="nav-btn">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="logout.php" class="nav-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>
    <?php endif; ?>

    <div class="content">
        <div class="po-container">
            <div class="po-header">
                <div class="logo-section">
                    <img src="image/logo.png" alt="AXYS Premiums Unlimited, Inc." style="height: 120px;">
                </div>
                <div class="company-info">
                    <h1>Axys Premiums Unlimited, Inc.</h1>
                    <p>Unit 2B Roofdeck Veraida 1 Condominium,</p>
                    <p>120 Amorsolo St., Legaspi Village, Makati City</p>
                    <p>Tel Nos.: (632) 840-0913 to 14</p>
                    <p>Telefax: (632) 812-4876</p>
                    <p>Email: info@axys-premiums.com</p>
                    <p>Website: www.axys-premiums.com</p>
                </div>
            </div>

            <h2 class="po-title">PURCHASE ORDER</h2>

            <div class="po-details">
                <p><strong>Purchase Order No:</strong> <?php echo htmlspecialchars($po_data['po_number']); ?></p>
                <p><strong>Date of Issue:</strong> <?php echo date('F d, Y', strtotime($po_data['created_at'])); ?></p>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>QTY</th>
                        <th>UNIT</th>
                        <th>DESCRIPTION</th>
                        <th>UNIT COST</th>
                        <th>TOTAL COST</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $items = explode('||', $po_data['items']);
                    $quantities = explode('||', $po_data['quantities']);
                    $prices = explode('||', $po_data['prices']);
                    
                    foreach ($items as $i => $item) {
                        if (!empty($item)) {
                            $qty = $quantities[$i];
                            $price = $prices[$i];
                            $total = $qty * $price;
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($qty); ?></td>
                                <td>PCS</td>
                                <td><?php echo htmlspecialchars($item); ?></td>
                                <td>₱<?php echo number_format($price, 2); ?></td>
                                <td>₱<?php echo number_format($total, 2); ?></td>
                            </tr>
                            <?php
                        }
                    }
                    ?>
                    <tr class="total-row">
                        <td colspan="4" style="text-align: right;"><strong>TOTAL AMOUNT</strong></td>
                        <td>₱<?php echo number_format($po_data['total_amount'], 2); ?></td>
                    </tr>
                </tbody>
            </table>

            <div style="margin-top: 20px;">
                <p><strong>Delivery due date:</strong> _________________________</p>
                <p><strong>Delivery To:</strong> _________________________</p>
                <p><strong>Terms of Payment:</strong> _________________________</p>
                <p><strong>Special Note:</strong> _________________________</p>
            </div>

            <div style="margin-top: 30px;">
                <div style="float: left; width: 45%;">
                    <p><strong>Requested By / Date:</strong></p>
                    <div class="signature-line"></div>
                </div>
                <div style="float: right; width: 45%;">
                    <p><strong>Approved By / Date:</strong></p>
                    <div class="signature-line"></div>
                </div>
                <div style="clear: both;"></div>
            </div>

            <div style="margin-top: 30px;">
                <p><strong>Prepared By / Date:</strong></p>
                <div class="signature-line" style="margin-left: 0;"></div>
            </div>

            <div class="terms">
                <h3>TERMS AND CONDITION OF THIS PURCHASE ORDER</h3>
                <ol>
                    <li>Indicate our Purchase Order Number on your Delivery Receipt and Invoice. Submit a separate invoice for each Purchase Order.</li>
                    <li>Processing for payment of our account shall be made after our receipt of the following:
                        <ul>
                            <li>Original copy of Sales Invoice</li>
                            <li>Original copy of Delivery Receipt</li>
                        </ul>
                    </li>
                    <li>We reserve the right to reject all or any materials delivered under this order which do not conform to the description shown above or the specifications and quality standards set by AXYS PREMIUMS UNLIMITED, INC. Rejected materials shall be returned to you at your expense.</li>
                    <li>In the event of failure to deliver or reject in materials delivered, supplier must return all payment or payments made by AXYS PREMIUMS UNLIMITED, INC. within 7 days upon cancellation of order.</li>
                    <li>In the event of failure on the part of the supplier to supply with the terms and condition of AXYS PREMIUMS UNLIMITED, INC., the supplier shall be responsible for all damages and in losses that may be incurred by AXYS PREMIUMS UNLIMITED, INC.</li>
                </ol>
            </div>

            <div class="signature-box">
                <h3>SUPPLIERS CONFIRMATION</h3>
                <p>I have read carefully and understood all the details of the Purchase Order and I'm willing to abide by the terms and conditions set by AXYS PREMIUMS UNLIMITED, INC.</p>
                <div class="signature-line">
                    <p>Supplier's Signature Over Printed Name</p>
                </div>
            </div>
        </div>
    </div>

    <?php if ($print_view): ?>
    <script>
        // Auto-print when print view is opened
        window.onload = function() {
            // If download parameter is present, set filename
            <?php if ($download): ?>
            document.title = "PO_<?php echo $po_number; ?>";
            <?php endif; ?>
            
            // Print the page
            window.print();
            
            // If download parameter is present, redirect back to view_po.php after printing
            <?php if ($download): ?>
            setTimeout(function() {
                window.location.href = 'view_po.php';
            }, 1000);
            <?php endif; ?>
        };
    </script>
    <?php endif; ?>
</body>
</html>
<?php $conn->close(); ?> 