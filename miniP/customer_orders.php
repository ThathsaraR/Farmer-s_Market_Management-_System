<?php
session_start();
include 'conn.php';

// Check if customer is logged in
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'customer') {
    header("Location: index.html");
    exit;
}

$customer_id = $_SESSION['user_id'];

// Fetch customer orders
$sql = "SELECT o.*, p.name AS product_name, v.name AS vendor_name
        FROM orders o
        LEFT JOIN products p ON o.product_id = p.product_id
        LEFT JOIN vendors v ON o.vendor_id = v.vendor_id
        WHERE o.customer_id = $customer_id
        ORDER BY o.created_at DESC";

$result = mysqli_query($conn, $sql);
$orders = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $orders[] = $row;
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Orders</title>
<style>
body {
    font-family: Arial, Helvetica, sans-serif;
    background: #f4f4f4;
    margin: 0;
    padding: 0;
}
header {
    background: #2b7a0b;
    color: white;
    padding: 15px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
header a {
    color: white;
    text-decoration: none;
    margin-left: 15px;
}
.container {
    max-width: 900px;
    margin: 20px auto;
    padding: 20px;
    background: white;
    border-radius: 8px;
}
h2 {
    text-align: center;
    color: #2b7a0b;
    margin-bottom: 20px;
}
table {
    width: 100%;
    border-collapse: collapse;
}
table th, table td {
    border: 1px solid #ccc;
    padding: 8px;
    text-align: center;
}
table th {
    background: #2b7a0b;
    color: white;
}
.status-pending { color: orange; font-weight: bold; }
.status-completed { color: green; font-weight: bold; }
.status-cancelled { color: red; font-weight: bold; }
</style>
</head>
<body>

<header>
    <div>My Orders</div>
    <div>
        <a href="customer_dashboard.php">Dashboard</a>
        <a href="customer_profile.php">Profile</a>
        <a href="index.html">Logout</a>
    </div>
</header>

<div class="container">
    <h2>My Orders</h2>

    <?php if(empty($orders)): ?>
        <p style="text-align:center; color:#555;">You have no orders yet.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Product</th>
                    <th>Vendor</th>
                    <th>Quantity</th>
                    <th>Pickup Time</th>
                    <th>Status</th>
                    <th>Ordered At</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($orders as $o): ?>
                <tr>
                    <td><?= htmlspecialchars($o['id']) ?></td>
                    <td><?= htmlspecialchars($o['product_name'] ?? '') ?></td>
                    <td><?= htmlspecialchars($o['vendor_name'] ?? '') ?></td>
                    <td><?= htmlspecialchars($o['quantity']) ?></td>
                    <td><?= htmlspecialchars($o['pickup_time']) ?></td>
                    <td class="status-<?= strtolower($o['status']) ?>"><?= htmlspecialchars($o['status']) ?></td>
                    <td><?= htmlspecialchars($o['created_at']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

</body>
</html>
