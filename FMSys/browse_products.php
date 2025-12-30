<?php
session_start();
include 'conn.php';

// Optional: require customer login (uncomment if you use sessions for customers)
// if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'customer') {
//     header('Location: login.php');
//     exit;
//}

// --- Get filter values from GET ---
$filter_category = isset($_GET['category']) ? trim($_GET['category']) : '';
$filter_vendor   = isset($_GET['vendor']) ? trim($_GET['vendor']) : '';
$filter_price    = isset($_GET['max_price']) ? floatval($_GET['max_price']) : 0;

// --- Load distinct categories and vendors for filters ---
$categories = [];
$vendors = [];

// categories
$res = $conn->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category <> '' ORDER BY category ASC");
if ($res) {
    while ($r = $res->fetch_assoc()) $categories[] = $r['category'];
    $res->free();
}

// vendors (only vendors that have products)
$res = $conn->query("
    SELECT DISTINCT v.vendor_id, v.name 
    FROM vendors v
    JOIN products p ON p.vendor_id = v.vendor_id
    WHERE v.name IS NOT NULL
    ORDER BY v.name ASC
");
if ($res) {
    while ($r = $res->fetch_assoc()) $vendors[] = $r;
    $res->free();
}

// --- Build product query (simple and beginner-friendly) ---
// base where: only show active products with stock > 0
$where = ["p.status = 'active'", "p.stock > 0"];

// apply filters
if ($filter_category !== '') {
    $cat = $conn->real_escape_string($filter_category);
    $where[] = "p.category = '{$cat}'";
}
if ($filter_vendor !== '') {
    // vendor may be passed as id or name; we expect vendor_id
    $vid = (int)$filter_vendor;
    if ($vid > 0) {
        $where[] = "p.vendor_id = {$vid}";
    }
}
if ($filter_price > 0) {
    $maxp = (float)$filter_price;
    $where[] = "p.price <= {$maxp}";
}

$where_sql = '';
if (!empty($where)) $where_sql = ' WHERE ' . implode(' AND ', $where);

// final query
$sql = "
    SELECT p.*, v.name AS vendor_name
    FROM products p
    LEFT JOIN vendors v ON p.vendor_id = v.vendor_id
    {$where_sql}
    ORDER BY p.name ASC
";

$products = [];
$res = $conn->query($sql);
if ($res) {
    while ($row = $res->fetch_assoc()) $products[] = $row;
    $res->free();
}

$conn->close();
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Browse Products</title>
    <link rel="stylesheet" href="/FMSys/styles/ui.css" />
</head>

<body>

    <header class="header">
        <div class="logo">Browse Products</div>
        <nav>
            <a class="btn-ghost" href="customer_dashboard.php">Dashboard</a>
            <a class="btn-ghost" href="customer_profile.php">Profile</a>
            <a class="btn-ghost" href="logout.php">Logout</a>
        </nav>
    </header>

    <div class="container card">
        <h2>Products</h2>

        <form class="filters" method="get" action="browse_products.php" aria-label="Filters">
            <select name="category" class="input" style="width:auto;">
                <option value="">All categories</option>
                <?php foreach($categories as $c): ?>
                <option value="<?= htmlspecialchars($c) ?>" <?=$filter_category===$c ? 'selected' : '' ?>>
                    <?= htmlspecialchars($c) ?>
                </option>
                <?php endforeach; ?>
            </select>

            <select name="vendor" class="input" style="width:auto;">
                <option value="">All vendors</option>
                <?php foreach($vendors as $v): ?>
                <option value="<?= (int)$v['vendor_id'] ?>" <?=((string)$filter_vendor===(string)$v['vendor_id'])
                    ? 'selected' : '' ?>>
                    <?= htmlspecialchars($v['name'] ?? 'Vendor') ?>
                </option>
                <?php endforeach; ?>
            </select>

            <input type="number" name="max_price" step="0.01" placeholder="Max price" class="input" value="<?= $filter_price > 0 ? htmlspecialchars($filter_price) : '' ?>">

            <button type="submit" class="btn btn-primary">Apply</button>
            <a href="browse_products.php" class="btn btn-secondary" style="margin-left:6px;display:inline-block;">Reset</a>
        </form>

        <?php if(empty($products)): ?>
        <div class="no-products">No products found.</div>
        <?php else: ?>
        <div class="products">
            <?php foreach($products as $p): ?>
            <?php
          $img = !empty($p['image']) && file_exists($p['image']) ? $p['image'] : 'https://via.placeholder.com/400x300?text=No+image';
          $vendor_name = $p['vendor_name'] ?? 'Vendor';
        ?>
            <div class="card">
                <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($p['name'] ?? 'Product') ?>">
                <h3>
                    <?= htmlspecialchars($p['name'] ?? '') ?>
                </h3>
                <p>Vendor:
                    <?= htmlspecialchars($vendor_name) ?>
                </p>
                <div class="price">Rs. <?= number_format((float)($p['price'] ?? 0), 2) ?></div>
                <p class="product-desc">
                    <?= htmlspecialchars(mb_strimwidth($p['description'] ?? '', 0, 80, '...')) ?>
                </p>

                <!-- link to product details or reserve -->
                <div>
                    <a href="product_details.php?id=<?= (int)$p['product_id'] ?>" class="btn btn-secondary">View Details</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="links">
            <a href="customer_dashboard.php">Dashboard</a> |
            <a href="customer_profile.php">Profile</a> |
            <a href="index.html">Logout</a>
        </div>
    </div>

</body>

</html>