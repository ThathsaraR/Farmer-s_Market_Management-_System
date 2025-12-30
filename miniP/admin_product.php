<?php
session_start();
include "conn.php";

// ---------------- Handle Approve/Reject Actions ----------------
if (isset($_GET['action'], $_GET['id'])) {
    $id = (int)$_GET['id'];

    // Check if product exists
    $check = $conn->query("SELECT * FROM products WHERE product_id=$id");
    if ($check->num_rows > 0) {
        $product = $check->fetch_assoc();
        if ($product['status'] === 'Pending') { // Only allow action if pending
            if ($_GET['action'] === 'approve') {
                $conn->query("UPDATE products SET status='Approved' WHERE product_id=$id");
            } elseif ($_GET['action'] === 'reject') {
                $conn->query("UPDATE products SET status='Rejected' WHERE product_id=$id");
            }
        }
    }
    header("Location: admin_product.php");
    exit;
}

// ---------------- Fetch Products ----------------
$products = $conn->query("SELECT * FROM products ORDER BY name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Product Management</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; font-family:Arial, Helvetica, sans-serif; }
        body { background:#f0f0f0; min-height:100vh; color:#333; }
        header { width:100%; background:#2b7a0b; padding:15px 40px; display:flex; justify-content:space-between; align-items:center; color:white; }
        header .logo { font-size:24px; font-weight:bold; }
        header nav a { color:white; text-decoration:none; margin-left:20px; font-weight:bold; }
        header nav a:hover { text-decoration:underline; }
        .container { padding:30px; background:white; margin:20px auto; border-radius:10px; width:95%; max-width:1400px; overflow-x:auto; }
        h2 { text-align:center; margin-bottom:20px; color:#2b7a0b; }
        table { width:100%; border-collapse:collapse; }
        table th, table td { padding:10px; border:1px solid #ccc; text-align:center; }
        table th { background:#2b7a0b; color:white; }
        .btn { padding:5px 10px; border:none; border-radius:5px; cursor:pointer; margin:2px; text-decoration:none; color:white; }
        .approve { background:green; }
        .reject { background:red; }
        .view { background:#1e90ff; }
        img.product-img { max-width:80px; height:auto; border-radius:5px; }
        .links { text-align:center; margin-top:15px; }
        .links a { text-decoration:none; color:#2b7a0b; margin:0 10px; }
        .links a:hover { text-decoration:underline; }
    </style>
</head>
<body>
<header>
    <div class="logo">Admin Product Management</div>
    <nav>
        <a href="admin_dashboard.php">Dashboard</a>
        <a href="admin_vendor.php">Vendors</a>
        <a href="index.php">Logout</a>
    </nav>
</header>

<div class="container">
    <h2>Product List</h2>
    <table>
        <tr>
            <th>Product Name</th>
            <th>Vendor ID</th>
            <th>Category</th>
            <th>Price</th>
            <th>Stock</th>
            <th>Description</th>
            <th>Image</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
        <?php if ($products->num_rows > 0): ?>
            <?php while($product = $products->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($product['name']); ?></td>
                <td><?php echo htmlspecialchars($product['vendor_id']); ?></td>
                <td><?php echo htmlspecialchars($product['category']); ?></td>
                <td><?php echo htmlspecialchars($product['price']); ?></td>
                <td><?php echo htmlspecialchars($product['stock']); ?></td>
                <td><?php echo htmlspecialchars($product['description']); ?></td>
                <td>
                    <?php if(!empty($product['image'])): ?>
                        <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="Product Image" class="product-img">
                    <?php else: ?>
                        N/A
                    <?php endif; ?>
                </td>
                <td><?php echo htmlspecialchars($product['status']); ?></td>
                <td>
                    <?php if($product['status'] === 'Pending'): ?>
                        <a href="?action=approve&id=<?php echo $product['product_id']; ?>" class="btn approve">Approve</a>
                        <a href="?action=reject&id=<?php echo $product['product_id']; ?>" class="btn reject">Reject</a>
                    <?php else: ?>
                        <span><?php echo $product['status']; ?></span>
                    <?php endif; ?>
                    <a href="view_product.php?id=<?php echo $product['product_id']; ?>" class="btn view">View</a>
                </td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="9">No products found.</td>
            </tr>
        <?php endif; ?>
    </table>

    <div class="links">
        <a href="admin_dashboard.php">Dashboard</a> |
        <a href="index.php">Logout</a>
    </div>
</div>
</body>
</html>
