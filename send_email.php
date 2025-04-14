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

if (isset($_GET['po'])) {
$po_number = $_GET['po'];

// Fetch PO details
$query = "SELECT po.*, 
              GROUP_CONCAT(DISTINCT poi.item_description SEPARATOR '||') as items,
              GROUP_CONCAT(DISTINCT poi.supplier) as suppliers,
              GROUP_CONCAT(DISTINCT poi.quantity SEPARATOR '||') as quantities,
              GROUP_CONCAT(DISTINCT poi.unit_price SEPARATOR '||') as prices,
          SUM(poi.quantity * poi.unit_price) as total_amount
          FROM purchase_orders po
              LEFT JOIN purchase_order_items poi ON poi.po_number = po.po_number
          WHERE po.po_number = ?
          GROUP BY po.po_number";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $po_number);
$stmt->execute();
$result = $stmt->get_result();
$po_data = $result->fetch_assoc();

    if ($po_data) {
        // Create PDF file
        $pdf_file = "po_files/PO_" . $po_number . ".pdf";
        
        // Generate PDF using the existing download_po.php script
        $download_url = "download_po.php?po=" . urlencode($po_number);
        file_get_contents($download_url);
        
        // Create Gmail compose URL with PDF attachment
        $gmail_body = "Dear Supplier,\n\n";
        $gmail_body .= "Please find attached Purchase Order #" . $po_number . ".\n\n";
        $gmail_body .= "Best regards,\nAXYS Premiums Unlimited, Inc.";
        
        $gmail_url = "https://mail.google.com/mail/?view=cm&fs=1&tf=1";
        $gmail_url .= "&subject=" . urlencode("Purchase Order #" . $po_number . " - AXYS Premiums Unlimited");
        $gmail_url .= "&body=" . urlencode($gmail_body);

        // Add AJAX endpoint for status update
        if (isset($_POST['update_status'])) {
            $update_query = "UPDATE purchase_orders SET status = 'Sent' WHERE po_number = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("s", $po_number);
            if ($update_stmt->execute()) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => $conn->error]);
            }
            exit();
        }
        
        // Display form
?>
<!DOCTYPE html>
<html>
<head>
            <title>Send Purchase Order - AXYS Premiums Unlimited</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
                    padding: 20px;
            background-color: #f4f4f4;
        }
                .container {
                    max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
                    text-align: center;
                }
                .preview {
                    margin: 20px 0;
                    padding: 20px;
            border: 1px solid #ddd;
            border-radius: 4px;
                    background-color: #f8f9fa;
                    text-align: left;
                }
                .btn {
                    padding: 15px 30px;
            background-color: #8B008B;
            color: white;
            border: none;
                    border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
                    display: inline-block;
                    font-size: 16px;
                    transition: background-color 0.3s;
                    margin: 5px;
                }
                .btn:hover {
            background-color: #6B006B;
        }
                .back-btn {
                    background-color: #6c757d;
                }
                .back-btn:hover {
                    background-color: #5a6268;
                }
                .gmail-btn {
                    background-color: #DB4437;
                }
                .gmail-btn:hover {
                    background-color: #B33228;
                }
                .download-btn {
            background-color: #28a745;
        }
                .download-btn:hover {
                    background-color: #218838;
                }
                .btn i {
                    margin-right: 8px;
                }
                .steps {
                    text-align: left;
                    margin: 20px 0;
                    padding: 20px;
                    background-color: #fff3cd;
                    border: 1px solid #ffeeba;
            border-radius: 4px;
                }
                .steps ol {
                    margin: 0;
                    padding-left: 20px;
                }
                .steps li {
                    margin-bottom: 10px;
        }
    </style>
</head>
<body>
            <div class="container">
                <h2><i class="fas fa-paper-plane"></i> Send Purchase Order #<?php echo htmlspecialchars($po_number); ?></h2>
                
                <div class="steps">
                    <h3><i class="fas fa-info-circle"></i> How to Send:</h3>
                    <ol>
                        <li>Click "Download PDF" to save the purchase order</li>
                        <li>Click "Open Gmail" to compose your email</li>
                        <li>Attach the downloaded PDF to your email</li>
                        <li>Review and send the email</li>
                    </ol>
                </div>
                
                <div class="preview">
                    <strong>Email Preview:</strong><br><br>
                    <?php echo nl2br(htmlspecialchars($gmail_body)); ?>
                </div>

                <div class="actions">
                    <a href="view_po.php" class="btn back-btn">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                    <a href="<?php echo $download_url; ?>" class="btn download-btn" download>
                        <i class="fas fa-download"></i> Download PDF
                    </a>
                    <a href="<?php echo $gmail_url; ?>" target="_blank" class="btn gmail-btn">
                        <i class="fab fa-google"></i> Open Gmail
                    </a>
                </div>
                </div>

            <script>
                // Track if Gmail was opened and update status
                document.querySelector('.gmail-btn').addEventListener('click', function(e) {
                    // Prevent immediate navigation
                    e.preventDefault();
                    
                    // Open Gmail in new window
                    window.open('<?php echo $gmail_url; ?>', '_blank');
                    
                    // Update status
                    fetch('send_email.php?po=<?php echo urlencode($po_number); ?>', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'update_status=1'
                    })
                    .then(response => response.json())
                    .then(data => {
                        // Wait 3 seconds before redirecting to ensure the status is updated
                        setTimeout(function() {
                            if (data.success) {
                                window.location.href = 'view_po.php?success=email_sent';
                            } else {
                                console.error('Status update error:', data.error);
                                window.location.href = 'view_po.php?error=' + encodeURIComponent(data.error || 'Failed to update status');
                            }
                        }, 3000);
                    })
                    .catch(error => {
                        console.error('AJAX error:', error);
                        // Still redirect after 3 seconds even if there's an error
                        setTimeout(function() {
                            window.location.href = 'view_po.php';
                        }, 3000);
                    });
                });
            </script>
</body>
</html>
        <?php
    } else {
        header("Location: view_po.php?error=Purchase order not found");
        exit();
    }
} else {
    header("Location: view_po.php?error=No purchase order specified");
    exit();
}

$conn->close();
?> 