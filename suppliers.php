<?php
session_start();

// Check if user is verified
if (!isset($_SESSION['verified']) || $_SESSION['verified'] !== true) {
    header('Location: index.php');
    exit();
}

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'] ?? '';
    $contact = $_POST['contact'] ?? '';
    $email = $_POST['email'] ?? '';
    $address = $_POST['address'] ?? '';
    
    if (!empty($name)) {
        $success_message = "Supplier added successfully!";
    } else {
        $error_message = "Please fill in all required fields.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Suppliers - AXYS Premiums Unlimited, Inc.</title>
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
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 30px;
        }
        .form-container, .table-container {
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
        input[type="email"],
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
        .btn:hover {
            opacity: 0.9;
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f8f9fa;
            color: #333;
        }
        tr:hover {
            background-color: #f5f5f5;
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
        .action-btn {
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            margin-right: 5px;
            color: white;
            text-decoration: none;
            display: inline-block;
        }
        .edit-btn {
            background-color: #8B008B;
        }
        .delete-btn {
            background-color: #dc3545;
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
            <h2><i class="fas fa-plus-circle"></i> Add Supplier</h2>
            
            <?php if ($success_message): ?>
                <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="name">Supplier Name:</label>
                    <input type="text" id="name" name="name" required>
                </div>

                <div class="form-group">
                    <label for="contact">Contact Person:</label>
                    <input type="text" id="contact" name="contact" required>
                </div>

                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>

                <div class="form-group">
                    <label for="address">Address:</label>
                    <textarea id="address" name="address" required></textarea>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Add Supplier
                </button>
            </form>
        </div>

        <div class="table-container">
            <h2><i class="fas fa-truck-field"></i> Supplier List</h2>
            <table>
                <thead>
                    <tr>
                        <th>Supplier Name</th>
                        <th>Contact Person</th>
                        <th>Email</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Data will be populated from database -->
                </tbody>
            </table>
        </div>
    </div>
</body>
</html> 