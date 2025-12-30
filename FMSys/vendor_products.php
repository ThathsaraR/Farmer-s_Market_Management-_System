<?php
session_start();
include 'conn.php'; 

// ----------------- Simple access check -----------------
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'vendor') {
    header('Location: index.html');
    exit;
}

$vendor_id = (int) $_SESSION['user_id'];

// Simple flash message helper (stored in session)
$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);

// ---------- Helpers ----------

function safe($val) {
    return htmlspecialchars((string)($val ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function upload_image($fileFieldName) {
    if (empty($_FILES[$fileFieldName]['name'])) return '';

    $allowed = ['jpg','jpeg','png','gif'];
    $maxSize = 2 * 1024 * 1024; // 2MB
    $ext = strtolower(pathinfo($_FILES[$fileFieldName]['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) {
        return ['error' => 'Invalid image type. Allowed: jpg,jpeg,png,gif'];
    }
    if ($_FILES[$fileFieldName]['size'] > $maxSize) {
        return ['error' => 'Image too large. Max 2MB.'];
    }

    $target_dir = "uploads/";
    if (!file_exists($target_dir)) mkdir($target_dir, 0755, true);

    // create a safe unique filename
    $base = preg_replace('/[^A-Za-z0-9\-_\.]/','_', basename($_FILES[$fileFieldName]['name']));
    $filename = time() . "_" . $base;
    $path = $target_dir . $filename;

    if (!move_uploaded_file($_FILES[$fileFieldName]['tmp_name'], $path)) {
        return ['error' => 'Failed to move uploaded file.'];
    }

    return ['path' => $path];
}

// ---------- POST: Delete product ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = intval($_POST['delete_id']);

    $stmt = $conn->prepare("DELETE FROM products WHERE product_id = ? AND vendor_id = ?");
    if (!$stmt) {
        $_SESSION['message'] = "DB error (prepare): " . safe($conn->error);
        header("Location: vendor_products.php");
        exit;
    }
    $stmt->bind_param("ii", $delete_id, $vendor_id);
    if ($stmt->execute()) {
        $_SESSION['message'] = "Product deleted successfully.";
    } else {
        $_SESSION['message'] = "Delete failed: " . safe($stmt->error);
    }
    $stmt->close();

    // redirect (PRG)
    header("Location: vendor_products.php");
    exit;
}

// ---------- POST: Save (Add or Edit) product ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_product'])) {
    // get inputs (basic trimming)
    $name = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $stock = intval($_POST['stock'] ?? 0);
    $description = trim($_POST['description'] ?? '');

    // very simple validation
    if ($name === '' || $category === '' || $price <= 0) {
        $_SESSION['message'] = "Please enter a product name, category and a valid price (> 0).";
        header("Location: vendor_products.php");
        exit;
    }

    // handle image upload (optional)
    $image = '';
    $uploadResult = upload_image('image');
    if (is_array($uploadResult) && isset($uploadResult['error'])) {
        $_SESSION['message'] = $uploadResult['error'];
        header("Location: vendor_products.php");
        exit;
    }
    if (is_array($uploadResult) && isset($uploadResult['path'])) {
        $image = $uploadResult['path'];
    }

    // If edit_id present -> update, else insert
    if (!empty($_POST['edit_id'])) {
        $edit_id = intval($_POST['edit_id']);

        if ($image !== '') {
            // update including image
            $sql = "UPDATE products SET name=?, category=?, price=?, stock=?, description=?, image=? WHERE product_id=? AND vendor_id=?";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                $_SESSION['message'] = "DB error (prepare): " . safe($conn->error);
                header("Location: vendor_products.php");
                exit;
            }
            // types: s s d i s s i i
            $stmt->bind_param("ssdissii", $name, $category, $price, $stock, $description, $image, $edit_id, $vendor_id);
        } else {
            // update without changing image
            $sql = "UPDATE products SET name=?, category=?, price=?, stock=?, description=? WHERE product_id=? AND vendor_id=?";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                $_SESSION['message'] = "DB error (prepare): " . safe($conn->error);
                header("Location: vendor_products.php");
                exit;
            }
            // types: s s d i s i i
            $stmt->bind_param("ssdisii", $name, $category, $price, $stock, $description, $edit_id, $vendor_id);
        }

        if ($stmt->execute()) {
            // affected_rows > 0 means a row was changed
            if ($stmt->affected_rows > 0) {
                $_SESSION['message'] = "Product updated successfully.";
            } else {
                // either values identical or where clause didn't match
                $_SESSION['message'] = "No change detected (maybe you didn't change any field).";
            }
        } else {
            $_SESSION['message'] = "Update failed: " . safe($stmt->error);
        }
        $stmt->close();
        header("Location: vendor_products.php");
        exit;
    } else {
        // INSERT new product
        $sql = "INSERT INTO products (vendor_id, name, category, price, stock, description, image, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'active')";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $_SESSION['message'] = "DB error (prepare): " . safe($conn->error);
            header("Location: vendor_products.php");
            exit;
        }
        // types: i s s d i s s
        $stmt->bind_param("issdiss", $vendor_id, $name, $category, $price, $stock, $description, $image);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Product added successfully.";
        } else {
            $_SESSION['message'] = "Insert failed: " . safe($stmt->error);
        }
        $stmt->close();
        header("Location: vendor_products.php");
        exit;
    }
}

// ---------- Fetch products for display ----------
$products = [];
$stmt = $conn->prepare("SELECT * FROM products WHERE vendor_id = ? ORDER BY name ASC");
if ($stmt) {
    $stmt->bind_param("i", $vendor_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        // ensure defaults (avoid nulls)
        $row['name'] = $row['name'] ?? '';
        $row['category'] = $row['category'] ?? '';
        $row['price'] = isset($row['price']) ? (float)$row['price'] : 0.0;
        $row['stock'] = isset($row['stock']) ? (int)$row['stock'] : 0;
        $row['description'] = $row['description'] ?? '';
        $row['image'] = $row['image'] ?? '';
        $products[] = $row;
    }
    $stmt->close();
}

// ---------- Pre-fill edit product data if requested via GET ----------
$edit_product = ['product_id'=>'','name'=>'','category'=>'','price'=>'','stock'=>'','description'=>'','image'=>''];
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    foreach ($products as $p) {
        if ((int)$p['product_id'] === $edit_id) {
            $edit_product = $p;
            break;
        }
    }
}

?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Vendor Products</title>
    <style>
        /* your styles unchanged */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, Helvetica, sans-serif; }
        body { background: #e6f2d6; min-height: 100vh; color: #333; }
        header { background: #2b7a0b; color: white; padding: 25px 30px; display: flex; justify-content: space-between; align-items: center; }
        header a { color: white; text-decoration: none; margin-left: 20px; font-weight: bold; }
        header a:hover { text-decoration: underline; }
        .container { background: rgba(255, 255, 255, 0.95); padding: 20px; border-radius: 10px; max-width: 1000px; margin: 20px auto; }
        h2 { text-align: center; color: #2b7a0b; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        th, td { padding: 10px; border: 1px solid #ccc; text-align: center; }
        th { background: #2b7a0b; color: white; }
        img { width: 60px; height: 60px; object-fit: cover; border-radius: 5px; }
        button { padding: 8px 15px; border: none; border-radius: 5px; cursor: pointer; margin: 2px; color: white; }
        .edit-btn { background: #1e90ff; }
        .delete-btn { background: #dc3545; }
        form input, form textarea, form select { width: 100%; padding: 10px; margin: 8px 0; border: 1px solid #ccc; border-radius: 5px; }
        form button { width: 100%; padding: 12px; background: #2b7a0b; color: white; border: none; border-radius: 5px; font-size: 16px; cursor: pointer; margin-top: 10px; }
        form button:hover { background: #1e5307; }
        .message { padding: 10px; background: #ddffdd; border-left: 6px solid #2b7a0b; margin-bottom: 20px; border-radius: 5px; }
    </style>
</head>

<body>

    <header>
        <div>My Products</div>
        <nav>
            <a href="vendor_dashboard.php">Dashboard</a>
            <a href="vendor_profile.php">Profile</a>
            <a href="index.html">Logout</a>
        </nav>
    </header>

    <div class="container">
        <h2>My Products</h2>

        <?php if($message): ?>
        <div class="message">
            <?= safe($message) ?>
        </div>
        <?php endif; ?>

        <table>
            <tr>
                <th>Image</th>
                <th>Name</th>
                <th>Category</th>
                <th>Price</th>
                <th>Stock</th>
                <th>Actions</th>
            </tr>
            <?php if(!empty($products)): ?>
            <?php foreach($products as $p): ?>
            <tr>
                <?php
                    // choose image to display: if path exists locally, use it, else placeholder
                    $img = !empty($p['image']) && file_exists($p['image']) ? $p['image'] : 'https://via.placeholder.com/60';
                ?>
                <td><img src="<?= safe($img) ?>" alt="product"></td>
                <td><?= safe($p['name']) ?></td>
                <td><?= safe($p['category']) ?></td>
                <td>$<?= number_format((float)($p['price'] ?? 0), 2) ?></td>
                <td><?= (int)($p['stock'] ?? 0) ?></td>
                <td>
                    <a href="?edit=<?= (int)$p['product_id'] ?>"><button class="edit-btn">Edit</button></a>
                    <!-- convert delete to POST for safety (example button below) -->
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure?');">
                        <input type="hidden" name="delete_id" value="<?= (int)$p['product_id'] ?>">
                        <button type="submit" class="delete-btn">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php else: ?>
            <tr>
                <td colspan="6">No products found.</td>
            </tr>
            <?php endif; ?>
        </table>

        <h2>Add / Edit Product</h2>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="edit_id" value="<?= safe($edit_product['product_id']) ?>">
            <input type="text" name="name" placeholder="Product Name" value="<?= safe($edit_product['name']) ?>" required>
            <input type="text" name="category" placeholder="Category" value="<?= safe($edit_product['category']) ?>" required>
            <input type="number" step="0.01" name="price" placeholder="Unit Price" value="<?= safe($edit_product['price']) ?>" required>
            <input type="number" name="stock" placeholder="Stock / Quantity" value="<?= safe($edit_product['stock']) ?>" required>
            <textarea name="description" placeholder="Product Description" rows="3"><?= safe($edit_product['description']) ?></textarea>
            <input type="file" name="image" accept="image/*">
            <button type="submit" name="save_product">Save Product</button>
        </form>

    </div>
</body>

</html>
