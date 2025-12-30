<?php
session_start();
include "conn.php";

// ---------------- CHECK IF TABLE EXISTS FUNCTION ----------------
function checkTable($conn, $tableName) {
    $check = mysqli_query($conn, "SHOW TABLES LIKE '$tableName'");
    return mysqli_num_rows($check) > 0;
}

// -------------------- TOTAL VENDORS --------------------
if (checkTable($conn, "vendors")) {
    $result1 = mysqli_query($conn, "SELECT * FROM vendors");
    $totalVendors = mysqli_num_rows($result1);
} else {
    $totalVendors = 0;
}

// -------------------- PENDING APPROVALS --------------------
if (checkTable($conn, "vendors")) {
    $result2 = mysqli_query($conn, "SELECT * FROM vendors WHERE status='Pending'");
    $pendingApprovals = mysqli_num_rows($result2);
} else {
    $pendingApprovals = 0;
}

// -------------------- TOTAL PRODUCTS --------------------
if (checkTable($conn, "products")) {
    $result3 = mysqli_query($conn, "SELECT * FROM products");
    $totalProducts = mysqli_num_rows($result3);
} else {
    $totalProducts = 0;
}

// -------------------- TOTAL CUSTOMERS --------------------
if (checkTable($conn, "customers")) {
    $result4 = mysqli_query($conn, "SELECT * FROM customers");
    $totalCustomers = mysqli_num_rows($result4);
} else {
    $totalCustomers = 0;
}

// -------------------- TOTAL STALLS (IF TABLE EXISTS) --------------------
if (checkTable($conn, "reservations")) {
    $result5 = mysqli_query($conn, "SELECT * FROM reservations");
    $totalStalls = mysqli_num_rows($result5);
} else {
    $totalStalls = 0;
}

// -------------------- EVENTS SCHEDULED --------------------
if (checkTable($conn, "events")) {
    $result6 = mysqli_query($conn, "SELECT * FROM events");
    $eventsScheduled = mysqli_num_rows($result6);
} else {
    $eventsScheduled = 0;
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard - Farmer's Market</title>
<!-- Font Awesome CDN for icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="/FMSys/styles/ui.css" />
</head>
<body>

<header class="header">
    <div class="logo">Admin Dashboard</div>
    <nav>
        <a class="btn-ghost" href="admin_dashboard.php">Dashboard</a>
        <a class="btn-ghost" href="admin_vendor.php">Vendors</a>
        <a class="btn-ghost" href="admin_product.php">Products</a>
        <a class="btn-ghost" href="admin_event.php">Events</a>
        <a class="btn-ghost" href="index.html">Logout</a>
    </nav>
</header>

<div class="container">
    <h2>Welcome, Admin</h2>

    <div class="tiles">
        <div class="tile">
            <i class="fa-solid fa-users"></i>
            <h3>Total Vendors</h3>
            <p><?php echo $totalVendors; ?></p>
        </div>
        <div class="tile">
            <i class="fa-solid fa-user-clock"></i>
            <h3>Pending Approvals</h3>
            <p><?php echo $pendingApprovals; ?></p>
        </div>
        <div class="tile">
            <i class="fa-solid fa-box-open"></i>
            <h3>Total Products</h3>
            <p><?php echo $totalProducts; ?></p>
        </div>
        <div class="tile">
            <i class="fa-solid fa-user"></i>
            <h3>Total Customers</h3>
            <p><?php echo $totalCustomers; ?></p>
        </div>
        <div class="tile">
            <i class="fa-solid fa-store"></i>
            <h3>Total Stalls</h3>
            <p><?php echo $totalStalls; ?></p>
        </div>
        <div class="tile">
            <i class="fa-solid fa-calendar-days"></i>
            <h3>Events Scheduled</h3>
            <p><?php echo $eventsScheduled; ?></p>
        </div>
    </div>

    <div class="buttons">
        <a class="btn btn-primary" href="admin_vendor.php">Manage Vendors</a>
        <a class="btn btn-primary" href="admin_stall.php">Manage Stalls</a>
        <a class="btn btn-primary" href="admin_event.php">Manage Events</a>
    </div>

    <div class="links">
        <a href="index.html">Logout</a>
    </div>
</div>

</body>
</html>