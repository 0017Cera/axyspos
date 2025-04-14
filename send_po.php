<?php
session_start();

require_once 'send_email.php';

if (!isset($_SESSION['verified']) || $_SESSION['verified'] !== true) {
    header("Location: index.php");
    exit();
}

if (!isset($_GET['po_number'])) {
    header("Location: view_po.php");
    exit();
}

$po_number = $_GET['po_number'];

// Database connection
$conn = new mysqli("localhost", "root", "", "purchase_order_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get PO details
$sql = "SELECT po.*, s.supplier_name, s.email as supplier_email 
        FROM purchase_orders po 
        JOIN suppliers s ON po.supplier_id = s.id 
        WHERE po.po_number = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $po_number);
$stmt->execute();
$result = $stmt->get_result();
$po = $result->fetch_assoc();

// Get PO items
$sql = "SELECT * FROM po_items WHERE po_number = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $po_number);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Create PDF directory if it doesn't exist
    if (!file_exists('po_files')) {
        mkdir('po_files');
    }

    // Generate PDF
    require_once('tcpdf/tcpdf.php');
    
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    $pdf->SetCreator('AXYS Premiums Unlimited, Inc.');
    $pdf->SetAuthor('AXYS Premiums Unlimited, Inc.');
    $pdf->SetTitle('Purchase Order - ' . $po_number);
    
    $pdf->setHeaderData('', 0, 'AXYS Premiums Unlimited, Inc.', 'Purchase Order: ' . $po_number);
    $pdf->setFooterData(array(0,64,0), array(0,64,128));
    
    $pdf->setHeaderFont(Array('helvetica', '', 10));
    $pdf->setFooterFont(Array('helvetica', '', 8));
    
    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
    
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetHeaderMargin(5);
    $pdf->SetFooterMargin(10);
    
    $pdf->SetAutoPageBreak(TRUE, 25);
    
    $pdf->setImageScale(1.25);
    
    $pdf->SetFont('helvetica', '', 10);
    
    $pdf->AddPage();
    
    // PO Details
    $html = '
    <h1>Purchase Order</h1>
    <table border="0" cellpadding="5">
        <tr>
            <td><strong>PO Number:</strong></td>
            <td>' . $po_number . '</td>
            <td><strong>Date:</strong></td>
            <td>' . $po['created_at'] . '</td>
        </tr>
        <tr>
            <td><strong>Supplier:</strong></td>
            <td>' . $po['supplier_name'] . '</td>
            <td><strong>Status:</strong></td>
            <td>' . $po['status'] . '</td>
        </tr>
    </table>
    <br><br>
    <h2>Items</h2>
    <table border="1" cellpadding="5">
        <tr>
            <th>Description</th>
            <th>Quantity</th>
            <th>Unit Price</th>
            <th>Total</th>
        </tr>';
    
    $grand_total = 0;
    foreach ($items as $item) {
        $total = $item['quantity'] * $item['unit_price'];
        $grand_total += $total;
        $html .= '<tr>
            <td>' . $item['description'] . '</td>
            <td>' . $item['quantity'] . '</td>
            <td>₱' . number_format($item['unit_price'], 2) . '</td>
            <td>₱' . number_format($total, 2) . '</td>
        </tr>';
    }
    
    $html .= '<tr>
            <td colspan="3" align="right"><strong>Grand Total:</strong></td>
            <td><strong>₱' . number_format($grand_total, 2) . '</strong></td>
        </tr>
    </table>';
    
    $pdf->writeHTML($html, true, false, true, false, '');
    
    $pdf_file = 'po_files/PO-' . $po_number . '.pdf';
    $pdf->Output($pdf_file, 'F');

    // Send email
    $to = $_POST['email'];
    $subject = $_POST['subject'];
    $message = $_POST['message'];
    
    $email_result = sendEmail($to, $subject, $message, $pdf_file);
    
    if ($email_result === true) {
        // Update PO status
        $sql = "UPDATE purchase_orders SET status = 'Sent' WHERE po_number = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $po_number);
        $stmt->execute();
        
        header("Location: view_po.php?success=1&message=Purchase Order sent successfully");
        exit();
    } else {
        $error = $email_result;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Purchase Order - AXYS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="email"],
        input[type="text"],
        textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        textarea {
            height: 100px;
            resize: vertical;
        }
        .preview {
            margin-top: 20px;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 4px;
        }
        .btn {
            background: #6f42c1;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover {
            background: #5a32a3;
        }
        .error {
            color: red;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Send Purchase Order</h1>
            <a href="dashboard.php" class="btn"><i class="fas fa-home"></i> Dashboard</a>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="email">Recipient Email:</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($po['supplier_email']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="subject">Subject:</label>
                <input type="text" id="subject" name="subject" value="Purchase Order - <?php echo $po_number; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="message">Message:</label>
                <textarea id="message" name="message" required>Dear <?php echo htmlspecialchars($po['supplier_name']); ?>,

Please find attached the purchase order (<?php echo $po_number; ?>) for your review.

Best regards,
AXYS Premiums Unlimited, Inc.</textarea>
            </div>
            
            <div class="preview">
                <h3>Purchase Order Details</h3>
                <p><strong>PO Number:</strong> <?php echo $po_number; ?></p>
                <p><strong>Supplier:</strong> <?php echo htmlspecialchars($po['supplier_name']); ?></p>
                <p><strong>Date:</strong> <?php echo $po['created_at']; ?></p>
                <p><strong>Total Items:</strong> <?php echo count($items); ?></p>
            </div>
            
            <button type="submit" class="btn">
                <i class="fas fa-paper-plane"></i> Send Purchase Order
            </button>
        </form>
    </div>
</body>
</html>
<?php $conn->close(); ?>