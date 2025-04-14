<?php
session_start();

// Check if user is verified
if (!isset($_SESSION['verified']) || $_SESSION['verified'] !== true) {
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - AXYS Premiums Unlimited, Inc.</title>
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
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .dashboard-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s;
            cursor: pointer;
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
        }
        .card-icon {
            font-size: 40px;
            margin-bottom: 15px;
            color: #8B008B;
        }
        h1 {
            color: #333;
            margin: 0;
        }
        .welcome-text {
            color: #666;
            margin-bottom: 30px;
        }
        .logout-btn {
            padding: 8px 20px;
            background-color: #8B008B;
            color: white;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
        }
        .logout-btn:hover {
            background-color: #6B006B;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="header">
        <img src="image/logo.png" alt="AXYS Premiums Unlimited, Inc." class="logo">
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>

    <div class="content">
        <h1>Dashboard</h1>
        <p class="welcome-text">Welcome to the Purchase Order Management System</p>

        <div class="dashboard-grid">
            <div class="dashboard-card" onclick="location.href='create_po.php'">
                <div class="card-icon">
                    <i class="fas fa-file-medical"></i>
                </div>
                <h3>Create Purchase Order</h3>
                <p>Create a new purchase order</p>
                
            </div>

            <div class="dashboard-card" onclick="location.href='view_po.php'">
                <div class="card-icon">
                    <i class="fas fa-list-check"></i>
                </div>
                <h3>View Purchase Orders</h3>
                <p>View and manage existing POs</p>
            </div>

            <div class="dashboard-card" onclick="location.href='suppliers.php'">
                <div class="card-icon">
                    <i class="fas fa-truck"></i>
                </div>
                <h3>Suppliers</h3>
                <p>Manage supplier information</p>
            </div>

            <div class="dashboard-card" onclick="location.href='reports.php'">
                <div class="card-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h3>Reports</h3>
                <p>View reports and analytics</p>
            </div>
        </div>
    </div>
</body>
</html> 