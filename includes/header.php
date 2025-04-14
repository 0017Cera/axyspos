<?php
require_once 'config/database.php';
require_once 'includes/session.php';
requireLogin();

$currentUser = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Purchase Order System'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .sidebar {
            min-height: 100vh;
            background-color: #f8f9fa;
            padding-top: 20px;
        }
        .main-content {
            padding: 20px;
        }
        .nav-link {
            color: #333;
            padding: 8px 16px;
            margin: 4px 0;
        }
        .nav-link:hover {
            background-color: #e9ecef;
            border-radius: 4px;
        }
        .nav-link.active {
            background-color: #0d6efd;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-2 sidebar">
                <div class="d-flex flex-column">
                    <h4 class="mb-4 px-3">PO System</h4>
                    <nav class="nav flex-column">
                        <a class="nav-link <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>" href="dashboard.php">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                        <a class="nav-link <?php echo $currentPage === 'create_po' ? 'active' : ''; ?>" href="create_po.php">
                            <i class="bi bi-plus-circle"></i> Create PO
                        </a>
                        <a class="nav-link" href="logout.php">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </nav>
                </div>
            </div>
            <div class="col-md-10 main-content">
                <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        <?php 
                        echo htmlspecialchars($_SESSION['message']); 
                        unset($_SESSION['message']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?> 