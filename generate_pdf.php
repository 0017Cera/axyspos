<?php
session_start();

// Check if user is verified
if (!isset($_SESSION['verified']) || $_SESSION['verified'] !== true) {
    header('Location: index.php');
    exit();
}

// Get parameters
$temp_file = isset($_GET['file']) ? $_GET['file'] : '';
$po_number = isset($_GET['po']) ? $_GET['po'] : '';

if (empty($temp_file) || empty($po_number)) {
    header('Location: view_po.php');
    exit();
}

// Full path to the temporary file
$temp_file_path = sys_get_temp_dir() . '/' . $temp_file;

// Check if the file exists
if (!file_exists($temp_file_path)) {
    header('Location: view_po.php');
    exit();
}

// Path to wkhtmltopdf executable
$wkhtmltopdf = 'C:\\Program Files\\wkhtmltopdf\\bin\\wkhtmltopdf.exe';

// Check if wkhtmltopdf is installed
if (!file_exists($wkhtmltopdf)) {
    // If wkhtmltopdf is not installed, fall back to browser print
    $html_content = file_get_contents($temp_file_path);
    echo '<!DOCTYPE html>
    <html>
    <head>
        <title>Purchase Order #' . $po_number . '</title>
        <style>
            @media print {
                @page {
                    size: legal;
                    margin: 0.5in;
                }
            }
        </style>
    </head>
    <body>
        ' . $html_content . '
        <script>
            window.onload = function() {
                window.print();
                setTimeout(function() {
                    window.location.href = "view_po.php";
                }, 1000);
            };
        </script>
    </body>
    </html>';
    exit();
}

// Generate PDF using wkhtmltopdf
$output_file = sys_get_temp_dir() . '/PO_' . $po_number . '.pdf';
$command = '"' . $wkhtmltopdf . '" --page-size legal --margin-top 0.5in --margin-right 0.5in --margin-bottom 0.5in --margin-left 0.5in "' . $temp_file_path . '" "' . $output_file . '"';
exec($command);

// Check if PDF was generated successfully
if (file_exists($output_file)) {
    // Set headers for PDF download
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="PO_' . $po_number . '.pdf"');
    header('Content-Length: ' . filesize($output_file));
    
    // Output the PDF file
    readfile($output_file);
    
    // Clean up temporary files
    unlink($temp_file_path);
    unlink($output_file);
} else {
    // If PDF generation failed, fall back to browser print
    $html_content = file_get_contents($temp_file_path);
    echo '<!DOCTYPE html>
    <html>
    <head>
        <title>Purchase Order #' . $po_number . '</title>
        <style>
            @media print {
                @page {
                    size: legal;
                    margin: 0.5in;
                }
            }
        </style>
    </head>
    <body>
        ' . $html_content . '
        <script>
            window.onload = function() {
                window.print();
                setTimeout(function() {
                    window.location.href = "view_po.php";
                }, 1000);
            };
        </script>
    </body>
    </html>';
}

// Clean up temporary file
if (file_exists($temp_file_path)) {
    unlink($temp_file_path);
}
?> 