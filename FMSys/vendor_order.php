<?php
// vendor_order.php - beginner-friendly robust version
session_start();
include 'conn.php'; // conn.php should set $conn (mysqli connection)

// Require vendor login (simple)
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'vendor') {
    header('Location: index.html');
    exit;
}

$vendor_id = (int) $_SESSION['user_id'];
$message = '';

// Simple action handling (confirm/pending/reject) via GET
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = strtolower(trim($_GET['action']));
    $order_id = (int) $_GET['id'];

    if (in_array($action, ['confirm','pending','reject'], true)) {
        $map = ['confirm'=>'confirmed','pending'=>'pending','reject'=>'rejected'];
        $new_status = $map[$action];

        $sql = "UPDATE orders
                SET status = '" . mysqli_real_escape_string($conn, $new_status) . "',
                    updated_at = NOW()
                WHERE id = " . $order_id . " AND vendor_id = " . $vendor_id . "
                LIMIT 1";

        if (mysqli_query($conn, $sql)) {
            if (mysqli_affected_rows($conn) > 0) {
                $message = "Order #{$order_id} set to " . ucfirst($new_status) . ".";
            } else {
                $message = "No matching order found for update (maybe not your order).";
            }
        } else {
            $message = "Database error: " . mysqli_error($conn);
        }
    } else {
        $message = "Invalid action.";
    }
}

// --- Helper: try a list of possible column names, return first non-empty ---
function try_cols($row, $candidates, $default = '') {
    foreach ($candidates as $c) {
        if (isset($row[$c]) && $row[$c] !== null && $row[$c] !== '') {
            return $row[$c];
        }
    }
    return $default;
}

// --- Fetch orders for this vendor with SELECT * (so we don't fail if a column name is different) ---
$orders = [];
$sql = "SELECT * FROM orders WHERE vendor_id = " . $vendor_id . " ORDER BY created_at DESC";
$result = mysqli_query($conn, $sql);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $orders[] = $row;
    }
    mysqli_free_result($result);
} else {
    $message = "Failed to fetch orders: " . mysqli_error($conn);
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Vendor Orders - Farmer's Market</title>
<style>
/* keep your styles (same as before) */
* { box-sizing:border-box; margin:0; padding:0; font-family: Arial, Helvetica, sans-serif; }
body { background:#f4f6f7; color:#222; min-height:100vh; }
header { background:#2b7a0b; color:#fff; padding:14px 20px; display:flex; justify-content:space-between; align-items:center; }
header a { color:#fff; text-decoration:none; margin-left:12px; font-weight:600; }
.wrap { max-width:1100px; margin:20px auto; padding:20px; background:#fff; border-radius:10px; box-shadow:0 6px 20px rgba(0,0,0,0.06); }
h2 { color:#2b7a0b; text-align:center; margin-bottom:14px; }
.msg { background:#e6ffea; border-left:5px solid #2b7a0b; padding:10px; margin-bottom:12px; border-radius:6px; text-align:center; }
.msg.error { background:#fff0f0; border-left-color:#c53030; color:#8b0000; }
table { width:100%; border-collapse:collapse; margin-top:12px; }
th, td { padding:10px 8px; border:1px solid #e1e1e1; text-align:center; font-size:14px; }
th { background:#f1f7f1; color:#2b7a0b; font-weight:700; }
.btn { padding:6px 10px; border-radius:6px; border:none; cursor:pointer; font-weight:600; text-decoration:none; display:inline-block; }
.btn-confirm { background:#2b7a0b; color:#fff; }
.btn-pending { background:#f59e0b; color:#fff; }
.btn-reject { background:#ef4444; color:#fff; }
.status-pending { color:#d97706; font-weight:700; } /* orange */
.status-confirmed { color:#16a34a; font-weight:700; } /* green */
.status-rejected { color:#ef4444; font-weight:700; } /* red */
.links { text-align:center; margin-top:14px; }
.links a { color:#2b7a0b; text-decoration:none; margin:0 8px; font-weight:600; }
@media (max-width: 760px) { th, td { font-size:12px; padding:8px; } }
.small { font-size:12px; color:#666; }
</style>
</head>
<body>

<header>
    <div>Vendor Orders</div>
    <nav>
        <a href="vendor_dashboard.php">Dashboard</a>
        <a href="vendor_profile.php">Profile</a>
        <a href="index.html">Logout</a>
    </nav>
</header>

<div class="wrap">
    <h2>Customer Pickup Requests</h2>

    <?php if (!empty($message)): ?>
        <div class="msg <?php echo (stripos($message, 'Failed') !== false || stripos($message, 'error') !== false || stripos($message, 'No matching') !== false) ? 'error' : ''; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <p style="font-size:13px;color:#555;">
        Tip: If you still get wrong columns, run <code>DESCRIBE orders</code> in phpMyAdmin or MySQL to see real column names.
    </p>

    <table>
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Product</th>
                <th>Qty</th>
                <th>Pickup Time</th>
                <th>Status</th>
                <th>Placed At</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($orders)): ?>
            <tr><td colspan="8" style="padding:20px; color:#666;">No orders found.</td></tr>
        <?php else: ?>
            <?php foreach ($orders as $o): ?>
                <?php
                    // try common names for fields. Add more candidates if your DB uses other names.
                    $order_id = try_cols($o, ['id','order_id','orderID'], 0);
                    $customer = try_cols($o, ['customer_name','name','cust_name','customer','customer_fullname','fullname'], 'Unknown customer');
                    $customer_email = try_cols($o, ['customer_email','email','cust_email'], '');
                    $product = try_cols($o, ['product_name','product','item_name','item'], 'Unknown product');
                    $price = try_cols($o, ['product_price','price','amount'], '');
                    $qty = try_cols($o, ['quantity','qty','qnt'], 1);
                    $pickup = try_cols($o, ['pickup_time','pickup','pickup_datetime','pickup_at'], '');
                    $status = strtolower(try_cols($o, ['status','order_status','state'], 'pending'));
                    $created = try_cols($o, ['created_at','created','order_time','placed_at','timestamp'], '');
                ?>
                <tr>
                    <td><?php echo (int)$order_id; ?></td>
                    <td>
                        <?php echo htmlspecialchars($customer); ?>
                        <?php if (!empty($customer_email)): ?>
                            <div class="small"><?php echo htmlspecialchars($customer_email); ?></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php echo htmlspecialchars($product); ?>
                        <?php if ($price !== ''): ?>
                            <div class="small">$<?php echo number_format((float)$price, 2); ?></div>
                        <?php endif; ?>
                    </td>
                    <td><?php echo (int)$qty; ?></td>
                    <td><?php echo htmlspecialchars($pickup); ?></td>
                    <td>
                        <?php if ($status === 'confirmed'): ?>
                            <span class="status-confirmed">Confirmed</span>
                        <?php elseif ($status === 'rejected'): ?>
                            <span class="status-rejected">Rejected</span>
                        <?php else: ?>
                            <span class="status-pending"><?php echo htmlspecialchars(ucfirst($status)); ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($created); ?></td>
                    <td>
                        <?php if ($status !== 'confirmed'): ?>
                            <a class="btn btn-confirm" href="?action=confirm&id=<?php echo (int)$order_id; ?>" onclick="return confirm('Confirm order #<?php echo (int)$order_id; ?>?')">Confirm</a>
                        <?php else: ?>
                            ✔
                        <?php endif; ?>

                        <?php if ($status !== 'pending'): ?>
                            <a class="btn btn-pending" href="?action=pending&id=<?php echo (int)$order_id; ?>" onclick="return confirm('Set order #<?php echo (int)$order_id; ?> to Pending?')">Pending</a>
                        <?php endif; ?>

                        <?php if ($status !== 'rejected'): ?>
                            <a class="btn btn-reject" href="?action=reject&id=<?php echo (int)$order_id; ?>" onclick="return confirm('Reject order #<?php echo (int)$order_id; ?>?')">Reject</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>

    <div class="links">
        <a href="vendor_dashboard.php">Dashboard</a> |
        <a href="vendor_profile.php">Profile</a> |
        <a href="index.html">Logout</a>
    </div>
</div>

</body>
</html>
