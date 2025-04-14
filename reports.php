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
    <title>Reports - AXYS Premiums Unlimited, Inc.</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        .reports-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 30px;
            margin-bottom: 30px;
        }
        .report-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .chart-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 30px;
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
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #8B008B;
            margin: 10px 0;
        }
        .stat-label {
            color: #666;
            font-size: 14px;
        }
        .chart-title {
            margin-bottom: 20px;
            color: #333;
        }
        canvas {
            max-width: 100%;
        }
        .date-filter {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
        }
        .date-filter select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
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
        <h1><i class="fas fa-chart-line"></i> Reports & Analytics</h1>

        <div class="date-filter">
            <select id="timeRange">
                <option value="7">Last 7 Days</option>
                <option value="30">Last 30 Days</option>
                <option value="90">Last 90 Days</option>
                <option value="365">Last Year</option>
            </select>
        </div>

        <div class="reports-grid">
            <div class="report-card">
                <div class="stat-label">Total Purchase Orders</div>
                <div class="stat-number">0</div>
                <div class="stat-label">No previous data</div>
            </div>
            <div class="report-card">
                <div class="stat-label">Total Amount</div>
                <div class="stat-number">₱0.00</div>
                <div class="stat-label">No previous data</div>
            </div>
            <div class="report-card">
                <div class="stat-label">Average PO Value</div>
                <div class="stat-number">₱0.00</div>
                <div class="stat-label">No previous data</div>
            </div>
            <div class="report-card">
                <div class="stat-label">Active Suppliers</div>
                <div class="stat-number">0</div>
                <div class="stat-label">No previous data</div>
            </div>
        </div>

        <div class="chart-container">
            <h3 class="chart-title">Purchase Orders Over Time</h3>
            <canvas id="poChart"></canvas>
        </div>

        <div class="chart-container">
            <h3 class="chart-title">Top Suppliers by Value</h3>
            <canvas id="supplierChart"></canvas>
        </div>
    </div>

    <script>
        // Initialize empty charts
        const poCtx = document.getElementById('poChart').getContext('2d');
        new Chart(poCtx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'Purchase Orders',
                    data: [],
                    borderColor: '#8B008B',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Initialize empty supplier chart
        const supplierCtx = document.getElementById('supplierChart').getContext('2d');
        new Chart(supplierCtx, {
            type: 'bar',
            data: {
                labels: [],
                datasets: [{
                    label: 'Total Value',
                    data: [],
                    backgroundColor: '#8B008B'
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Time range change handler
        document.getElementById('timeRange').addEventListener('change', function() {
            // Add functionality to update charts based on selected time range
            console.log('Selected time range:', this.value);
        });
    </script>
</body>
</html> 