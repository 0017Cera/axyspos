<?php
session_start();

// Check if user is verified
if (!isset($_SESSION['verified']) || $_SESSION['verified'] !== true) {
    header('Location: index.php');
    exit();
}

// Get PO number from URL
if (!isset($_GET['po'])) {
    header('Location: view_po.php');
    exit();
}
$po_number = $_GET['po'];

// Database connection
$conn = new mysqli('localhost', 'root', '', 'purchase_order_system');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

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

// Create a temporary HTML file
$temp_file = tempnam(sys_get_temp_dir(), 'po_');
$html_content = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Purchase Order #' . $po_number . '</title>
    <style>
        @page {
            size: legal;
            margin: 0.5in;
        }
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            width: 8.5in;
            height: 13in;
        }
        .header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .logo {
            max-height: 60px;
        }
        .company-info {
            text-align: right;
        }
        .company-info h1 {
            color: #8B008B;
            margin: 0;
            font-size: 18px;
        }
        .company-info p {
            margin: 2px 0;
            font-size: 12px;
        }
        .po-title {
            text-align: center;
            font-size: 18px;
            margin: 20px 0;
            color: #8B008B;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 8px;
            text-align: left;
            border: 1px solid #ddd;
            font-size: 12px;
        }
        th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .total-row {
            font-weight: bold;
            background-color: #f8f9fa;
        }
        .signature-line {
            margin-top: 30px;
            border-top: 1px solid #000;
            padding-top: 5px;
            width: 200px;
        }
        .terms {
            font-size: 10px;
            margin-top: 20px;
        }
        .terms h3 {
            margin-bottom: 5px;
        }
        .terms ol {
            margin: 0;
            padding-left: 20px;
        }
        .terms li {
            margin-bottom: 3px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo-section">
            <img src="data:image/png;base64,' . base64_encode(file_get_contents('image/logo.png')) . '" alt="AXYS Premiums Unlimited, Inc." class="logo">
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

    <div>
        <p><strong>Purchase Order No:</strong> ' . $po_number . '</p>
        <p><strong>Date of Issue:</strong> ' . date('F d, Y', strtotime($po_data['created_at'])) . '</p>
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
        <tbody>';

// Add items to the table
$items = explode('||', $po_data['items']);
$quantities = explode('||', $po_data['quantities']);
$prices = explode('||', $po_data['prices']);

foreach ($items as $i => $item) {
    if (!empty($item)) {
        $qty = $quantities[$i];
        $price = $prices[$i];
        $total = $qty * $price;
        
        $html_content .= '
            <tr>
                <td>' . $qty . '</td>
                <td>PCS</td>
                <td>' . htmlspecialchars($item) . '</td>
                <td>₱' . number_format($price, 2) . '</td>
                <td>₱' . number_format($total, 2) . '</td>
            </tr>';
    }
}

$html_content .= '
            <tr class="total-row">
                <td colspan="4" style="text-align: right;"><strong>TOTAL AMOUNT</strong></td>
                <td>₱' . number_format($po_data['total_amount'], 2) . '</td>
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

    <div style="margin-top: 30px; padding: 15px; border: 1px solid #ddd; text-align: center;">
        <h3>SUPPLIERS CONFIRMATION</h3>
        <p>I have read carefully and understood all the details of the Purchase Order and I\'m willing to abide by the terms and conditions set by AXYS PREMIUMS UNLIMITED, INC.</p>
        <div class="signature-line">
            <p>Supplier\'s Signature Over Printed Name</p>
        </div>
    </div>
</body>
</html>';

// Write the HTML content to the temporary file
file_put_contents($temp_file, $html_content);

// Redirect to a special page that will handle the PDF generation
echo '<!DOCTYPE html>
<html>
<head>
    <title>Generating PDF...</title>
    <meta http-equiv="refresh" content="0;url=generate_pdf.php?file=' . basename($temp_file) . '&po=' . $po_number . '">
</head>
<body>
    <p>Generating PDF, please wait...</p>
</body>
</html>';

$conn->close();
?> 