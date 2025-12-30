<?php
session_start();
include 'conn.php'; 

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'vendor') {
    header('Location: index.html');
    exit;
}

$vendor_id = $_SESSION['user_id'];
$param_type = is_numeric($vendor_id) ? "i" : "s";

$stmt = $conn->prepare("SELECT name, email, status FROM vendors WHERE vendor_id = ? LIMIT 1");
if (!$stmt) die("DB prepare error: " . htmlspecialchars($conn->error));
$stmt->bind_param($param_type, $vendor_id);
$stmt->execute();
$result = $stmt->get_result();

$vendor_name = "Vendor";
$vendor_status = "";

if ($result && $result->num_rows === 1) {
    $row = $result->fetch_assoc();
    $vendor_name = $row['name'];
    $vendor_status = strtolower($row['status']);
} else {
    session_destroy();
    header('Location: index.html');
    exit;
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<title>Vendor Dashboard - Farmer's Market</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
    * { margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
    body { background: #f3f9f0; min-height: 100vh; color: #333; }

    /* Header */
    header {
        background: #2b7a0b;
        padding: 18px 50px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        color: white;
        box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        position: sticky;
        top: 0;
        z-index: 100;
    }
    .logo { font-size: 26px; font-weight: 700; }
    nav a { color:white; text-decoration:none; margin-left:20px; font-weight:600; transition: color 0.3s; }
    nav a:hover { color: #d9ffd9; }

    /* Container */
    .container { max-width: 1200px; margin: 30px auto; padding: 0 20px; }

    /* Status message */
    .status-message {
        background: #fff3f3;
        border-left: 6px solid #ff4d4d;
        padding: 20px;
        margin-bottom: 30px;
        border-radius: 10px;
        font-weight: bold;
        text-align: center;
        color: #a60000;
        box-shadow: 0 2px 6px rgba(0,0,0,0.05);
    }

    /* Tiles */
    .tiles {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 25px;
        margin-bottom: 40px;
    }
    .tile {
        background: #ffffff;
        padding: 25px 15px;
        text-align: center;
        border-radius: 12px;
        cursor: pointer;
        box-shadow: 0 2px 6px rgba(0,0,0,0.08);
        transition: transform 0.25s, box-shadow 0.25s;
        font-weight: 600;
        font-size: 16px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }
    .tile:hover {
        transform: translateY(-5px);
        box-shadow: 0 6px 18px rgba(0,0,0,0.15);
    }
    .tile a { text-decoration:none; color: inherit; display:block; width: 100%; }

    .tile i {
        font-size: 36px;
        margin-bottom: 12px;
        color: #2b7a0b;
    }
    .tile:hover i { color: #073d0eff; }

    /* Charts */
    .charts {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 30px;
    }
    .chart {
        background: #ffffff;
        padding: 30px;
        border-radius: 12px;
        min-height: 200px;
        text-align: center;
        font-weight: bold;
        display: flex;
        justify-content: center;
        align-items: center;
        box-shadow: 0 2px 6px rgba(0,0,0,0.08);
        transition: transform 0.25s, box-shadow 0.25s;
    }
    .chart:hover { transform: translateY(-5px); box-shadow: 0 6px 18px rgba(0,0,0,0.15); }

    @media (max-width: 768px) {
        .charts { grid-template-columns: 1fr; }
        nav { display: flex; flex-wrap: wrap; margin-top:10px; }
        nav a { margin: 5px 10px 0 0; }
    }
</style>
</head>

<body>
<header>
    <div class="logo">Welcome, <?=htmlspecialchars($vendor_name)?>!</div>
    <nav>
        <a href="vendor_dashboard.php">Dashboard</a>
        <a href="vendor_products.php">Products</a>
        <a href="vendor_stalls.php">Stalls</a>
        <a href="vendor_order.php">Orders</a>
        <a href="vendor_messages.php">Messages</a>
        <a href="vendor_profile.php">Profile</a>
        <a href="index.html">Logout</a>
    </nav>
</header>

<div class="container">
<?php if($vendor_status !== 'approved'): ?>
    <div class="status-message">
        Your account is pending approval. Please wait until your account is approved.
    </div>
<?php else: ?>
    <div class="tiles">
        <div class="tile">
            <i class="fas fa-box-open"></i>
            <a href="vendor_products.php">My Products</a>
        </div>
        <div class="tile">  
            <i class="fas fa-store"></i>
            <a href="vendor_stalls.php">Stall Reservations</a>
        </div>
        <div class="tile">
            <i class="fas fa-shopping-cart"></i>
            <a href="vendor_order.php">Orders / Customer Requests</a>
        </div>
        <div class="tile">
            <i class="fas fa-envelope"></i>
            <a href="vendor_messages.php">Messages</a>
        </div>
        <div class="tile">
            <i class="fas fa-calendar-alt"></i>
            <a href="vendor_events.php">Upcoming Events</a>
        </div>
    </div>

    <div class="charts">
        <div class="chart">Weekly Sales Chart (Coming Soon)</div>
        <div class="chart">Most Reserved Products Chart (Coming Soon)</div>
    </div>
<?php endif; ?>
</div>
</body>
</html>
