<?php
session_start();
include "conn.php";

// Require customer login
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'customer') {
    header("Location: index.html");
    exit;
}

$customer_id = $_SESSION['user_id'];
$message = "";

// Get product ID from GET
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($product_id <= 0) {
    die("Invalid product ID.");
}

// Fetch product info
$sql = "SELECT p.*, v.name AS vendor_name
        FROM products p
        LEFT JOIN vendors v ON p.vendor_id = v.vendor_id
        WHERE p.product_id = $product_id";
$result = mysqli_query($conn, $sql);
$product = mysqli_fetch_assoc($result);

if (!$product) {
    die("Product not found!");
}

// Handle order submission
if (isset($_POST['order'])) {
    $quantity = (int)$_POST['quantity'];
    $pickup_time = $_POST['pickup_time'];

    if ($quantity <= 0 || empty($pickup_time)) {
        $message = "Please enter a valid quantity and pickup time.";
    } else {
        $vendor_id = $product['vendor_id'];
        $status = 'Pending';
        $created_at = date('Y-m-d H:i:s');
        $updated_at = $created_at;

        $stmt = $conn->prepare("INSERT INTO orders (vendor_id, customer_id, product_id, quantity, pickup_time, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiissss", $vendor_id, $customer_id, $product_id, $quantity, $pickup_time, $status, $created_at, $updated_at);

        if ($stmt->execute()) {
            $message = "Order placed successfully!";
        } else {
            $message = "Failed to place order: " . $stmt->error;
        }
        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Product Details</title>
<style>
body {font-family: Arial; background:#f0f0f0; margin:0; padding:0;}
header {background:#2b7a0b; color:white; padding:14px 24px; display:flex; justify-content:space-between; align-items:center;}
header a {color:white; margin-left:15px; text-decoration:none;}
.container {max-width:700px; margin:20px auto; background:#fff; padding:20px; border-radius:10px; box-shadow:0 4px 12px rgba(0,0,0,0.1);}
h2 {color:#2b7a0b; margin-bottom:10px;}
img {width:100%; max-height:300px; object-fit:cover; border-radius:6px; margin-bottom:15px;}
p {margin:6px 0;}
label {display:block; margin-top:10px; font-weight:bold;}
input, select {width:100%; padding:8px; margin-top:5px; border-radius:6px; border:1px solid #ccc;}
button {margin-top:15px; padding:10px 15px; background:#2b7a0b; color:white; border:none; border-radius:6px; cursor:pointer;}
button:hover {background:#1e5307;}
.message {background:#eaffea; border-left:5px solid #2b7a0b; padding:10px; margin-bottom:12px; border-radius:6px;}
</style>
</head>
<body>

<header>
    <div>Product Details</div>
    <nav>
        <a href="browse_products.php">Browse</a>
        <a href="customer_dashboard.php">Dashboard</a>
        <a href="customer_profile.php">Profile</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<div class="container">

    <?php if($message): ?>
        <div class="message"><?php echo $message; ?></div>
    <?php endif; ?>

    <h2><?php echo htmlspecialchars($product['name']); ?></h2>
    <img src="<?php echo (!empty($product['image']) && file_exists($product['image'])) ? $product['image'] : 'https://via.placeholder.com/400x300?text=No+image'; ?>" alt="Product Image">
    <p><strong>Vendor:</strong> <?php echo htmlspecialchars($product['vendor_name']); ?></p>
    <p><strong>Category:</strong> <?php echo htmlspecialchars($product['category']); ?></p>
    <p><strong>Price:</strong> $<?php echo number_format($product['price'],2); ?></p>
    <p><strong>Stock:</strong> <?php echo (int)$product['stock']; ?></p>
    <p><strong>Description:</strong> <?php echo htmlspecialchars($product['description']); ?></p>

    <form method="post">
        <label for="quantity">Quantity</label>
        <input type="number" name="quantity" min="1" max="<?php echo (int)$product['stock']; ?>" required>

        <label for="pickup_time">Pickup Time</label>
        <input type="datetime-local" name="pickup_time" required>

        <button type="submit" name="order">Place Order</button>
    </form>
</div>

</body>
</html>
