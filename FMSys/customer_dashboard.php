<?php
session_start();
include "conn.php";

// Make sure user is logged in and is a customer
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'customer') {
    header('Location: login.php');
    exit;
}

// Use customer_id from session (cast to int for safety)
$customer_id = (int)$_SESSION['user_id'];
$customer_name = 'Customer'; // fallback

// Prepare and fetch customer name (beginner-friendly)
$stmt = $conn->prepare("SELECT name FROM customers WHERE customer_id = ? LIMIT 1");
if ($stmt) {
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows === 1) {
        $row = $result->fetch_assoc();
        // Ensure we have a string, otherwise keep fallback
        if (!empty($row['name'])) {
            $customer_name = $row['name'];
        }
    }
    $stmt->close();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Customer Dashboard - Farmer's Market</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, Helvetica, sans-serif;
        }

        body {
            background: #f0f0f0;
            min-height: 100vh;
            color: #333;
        }

        header {
            width: 100%;
            background: #2b7a0b;
            padding: 15px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
        }

        header .logo {
            font-size: 24px;
            font-weight: bold;
        }

        header nav a {
            color: white;
            text-decoration: none;
            margin-left: 20px;
            font-weight: bold;
        }

        header nav a:hover {
            text-decoration: underline;
        }

        .container {
            padding: 30px;
            background: white;
            margin: 20px auto;
            border-radius: 10px;
            width: 95%;
            max-width: 1200px;
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #2b7a0b;
        }

        .tiles {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .tile {
            background: #2b7a0b;
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            cursor: pointer;
            transition: 0.3s;
        }

        .tile:hover {
            background: #1e5307;
        }

        .links {
            text-align: center;
            margin-top: 20px;
        }

        .links a {
            text-decoration: none;
            color: #2b7a0b;
            margin: 0 10px;
        }

        .links a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <header>
        <div class="logo">Customer Dashboard</div>
        <nav>
            <a href="customer_dashboard.php">Dashboard</a>
            <a href="customer_profile.php">Profile</a>
            <a href="index.html">Logout</a>
        </nav>
    </header>

    <div class="container">
        <h2>Welcome,
            <?= htmlspecialchars($customer_name ?? 'Customer') ?>!
        </h2>

        <div class="tiles">
            <div class="tile" onclick="location.href='browse_products.php'">Browse Products</div>
            <div class="tile" onclick="location.href='customer_orders.php'">My Orders</div>
            <div class="tile" onclick="location.href='customer_messages.php'">My Messages</div>
            <div class="tile" onclick="location.href='customer_profile.php'">My Profile</div>
        </div>

        <div class="links">
            <a href="index.html">Logout</a>
        </div>
    </div>
</body>

</html>