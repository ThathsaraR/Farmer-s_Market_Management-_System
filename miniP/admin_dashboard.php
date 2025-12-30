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
<style>
    * {margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;}
    body {
        background: #f0f2f5;
        min-height:100vh;
        color: #333;
    }
    header {
        width:100%;
        background: #2b7a0b;
        padding: 15px 40px;
        display:flex;
        justify-content:space-between;
        align-items:center;
        color:white;
        position: sticky;
        top:0;
        z-index: 1000;
    }
    header .logo { font-size:24px; font-weight:bold; }
    header nav a {
        color:white;
        text-decoration:none;
        margin-left:20px;
        font-weight:600;
        transition: 0.3s;
    }
    header nav a:hover { text-decoration: underline; color: #d4edda; }

    .container {
        padding:30px;
        margin: 20px auto;
        max-width:1200px;
    }

    h2 {
        text-align:center;
        margin-bottom:30px;
        color:#2b7a0b;
        font-size:28px;
    }

    .tiles {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 20px;
        margin-bottom: 40px;
    }

    .tile {
        background:#fff;
        padding:25px 20px;
        border-radius:15px;
        text-align:center;
        box-shadow:0 10px 20px rgba(0,0,0,0.08);
        transition: all 0.3s ease;
        cursor:pointer;
        display:flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    .tile:hover {
        transform: translateY(-5px);
        box-shadow:0 15px 25px rgba(0,0,0,0.15);
    }

    .tile i {
        font-size:40px;
        color:#2b7a0b;
        margin-bottom:10px;
    }

    .tile h3 {
        font-size:18px;
        margin-bottom:10px;
        color:#2b7a0b;
    }

    .tile p {
        font-size:26px;
        font-weight:bold;
        color:#444;
    }

    .buttons {
        display:flex;
        flex-wrap:wrap;
        gap:15px;
        justify-content:center;
        margin-bottom:30px;
    }

    .buttons a {
        padding:14px 25px;
        background:#2b7a0b;
        color:white;
        text-decoration:none;
        border-radius:8px;
        font-weight:600;
        transition:0.3s;
    }

    .buttons a:hover {
        background:#1e5307;
    }

    .links {
        text-align:center;
        margin-top:20px;
    }

    .links a {
        text-decoration:none;
        color:#2b7a0b;
        margin:0 12px;
        font-weight:600;
        transition:0.3s;
    }

    .links a:hover { text-decoration:underline; color:#1e5307; }

    @media(max-width:600px){
        header {flex-direction:column; gap:10px;}
        .tiles {grid-template-columns: 1fr 1fr;}
    }
</style>
</head>
<body>

<header>
    <div class="logo">Admin Dashboard</div>
    <nav>
        <a href="admin_dashboard.php">Dashboard</a>
        <a href="admin_vendor.php">Vendors</a>
        <a href="admin_product.php">Products</a>
        <a href="admin_event.php">Events</a>
        <a href="index.html">Logout</a>
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
        <a href="admin_vendor.php">Manage Vendors</a>
        <a href="admin_stall.php">Manage Stalls</a>
        <a href="admin_event.php">Manage Events</a>
    </div>

    <div class="links">
        <a href="index.html">Logout</a>
    </div>
</div>

</body>
</html>