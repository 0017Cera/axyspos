<?php
require_once 'config/database.php';
require_once 'includes/session.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $pin = $_POST['pin'] ?? '';
    if ($pin === 'AXYS') {
        $_SESSION['verified'] = true;
        header('Location: dashboard.php');
        exit();
    } else {
        $error = 'Invalid PIN';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Welcome - AXYS Premiums Unlimited, Inc.</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .welcome-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 500px;
            text-align: center;
        }
        .logo {
            max-width: 300px;
            margin-bottom: 20px;
        }
        h1 {
            color: #333;
            margin: 20px 0;
            font-size: 28px;
        }
        .get-started-btn {
            display: inline-block;
            padding: 12px 30px;
            background-color: #8B008B;
            color: white;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-size: 18px;
            margin: 20px 0;
            text-decoration: none;
            transition: background-color 0.3s;
        }
        .get-started-btn:hover {
            background-color: #6B006B;
        }
        .welcome-text {
            color: #666;
            font-size: 16px;
            margin: 20px 0;
            line-height: 1.6;
        }
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
            max-width: 400px;
            width: 90%;
        }
        .pin-input {
            padding: 10px;
            font-size: 16px;
            border: 2px solid #ddd;
            border-radius: 4px;
            width: 200px;
            text-align: center;
            margin: 20px 0;
        }
        .error {
            color: red;
            margin: 10px 0;
        }
        .close {
            position: absolute;
            right: 15px;
            top: 10px;
            font-size: 24px;
            cursor: pointer;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="welcome-container">
        <img src="image/logo.png" alt="AXYS Premiums Unlimited, Inc." class="logo">
        <h1>Welcome to AXYS Premiums Unlimited, Inc.</h1>
        <p class="welcome-text">Purchase Order Management System</p>
        <button onclick="showPinModal()" class="get-started-btn">GET STARTED</button>
    </div>

    <!-- PIN Verification Modal -->
    <div id="pinModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closePinModal()">&times;</span>
            <h2>Enter PIN</h2>
            <?php if (isset($error)): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form method="POST" action="">
                <input type="password" name="pin" class="pin-input" placeholder="Enter PIN" required>
                <br>
                <button type="submit" class="get-started-btn">Verify</button>
            </form>
        </div>
    </div>

    <script>
        function showPinModal() {
            document.getElementById('pinModal').style.display = 'flex';
        }

        function closePinModal() {
            document.getElementById('pinModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            var modal = document.getElementById('pinModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html> 