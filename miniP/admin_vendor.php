<?php
session_start();
include "conn.php";

// Handle Approve / Reject via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['vendor_id'])) {
    $vendor_id = (int)$_POST['vendor_id'];
    $action = $_POST['action'];

    // Check if vendor exists
    $check = $conn->query("SELECT * FROM vendors WHERE vendor_id=$vendor_id");
    if ($check->num_rows > 0) {
        $vendor = $check->fetch_assoc();
        if ($vendor['status'] === 'Pending') { // Only pending vendors
            if ($action === 'approve') {
                $conn->query("UPDATE vendors SET status='Approved' WHERE vendor_id=$vendor_id");
            } elseif ($action === 'reject') {
                $conn->query("UPDATE vendors SET status='Rejected' WHERE vendor_id=$vendor_id");
            }
        }
    }

    // Redirect to prevent form resubmission
    header("Location: admin_vendor.php");
    exit;
}

// Fetch all vendors
$vendors = $conn->query("SELECT * FROM vendors ORDER BY name ASC");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Vendor Management</title>
    <style>
        /* Basic styles */
        body {
            font-family: Arial, sans-serif;
            background: #f0f0f0;
            color: #333;
            margin: 0;
            padding: 0;
        }

        header {
            background: #2b7a0b;
            color: white;
            padding: 15px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        header nav a {
            color: white;
            text-decoration: none;
            margin-left: 20px;
            font-weight: bold;
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

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table th,
        table td {
            padding: 10px;
            border: 1px solid #ccc;
            text-align: center;
        }

        table th {
            background: #2b7a0b;
            color: white;
        }

        .btn {
            padding: 5px 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin: 2px;
            color: white;
            text-decoration: none;
        }

        .approve {
            background: green;
        }

        .reject {
            background: red;
        }

        .status {
            font-weight: bold;
        }
    </style>
</head>

<body>

    <header>
        <div class="logo">Admin Vendor Management</div>
        <nav>
            <a href="admin_dashboard.php">Dashboard</a>
            <a href="admin_products.php">Products</a>
            <a href="index.html">Logout</a>
        </nav>
    </header>

    <div class="container">
        <h2>Vendors List</h2>
        <table>
            <tr>
                <th>Vendor Name</th>
                <th>Contact</th>
                <th>Category</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
            <?php if($vendors->num_rows > 0): ?>
            <?php while($vendor = $vendors->fetch_assoc()): ?>
            <tr>
                <td>
                    <?= htmlspecialchars($vendor['name'] ?? '') ?>
                </td>
                <td>
                    <?= htmlspecialchars($vendor['contact'] ?? '') ?>
                </td>
                <td>
                    <?= htmlspecialchars($vendor['category'] ?? '') ?>
                </td>
                <td class="status">
                    <?= htmlspecialchars($vendor['status'] ?? '') ?>
                </td>
                <td>
                    <?php if(($vendor['status'] ?? '') === 'Pending'): ?>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="vendor_id" value="<?= $vendor['vendor_id'] ?>">
                        <button type="submit" name="action" value="approve" class="btn approve">Approve</button>
                    </form>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="vendor_id" value="<?= $vendor['vendor_id'] ?>">
                        <button type="submit" name="action" value="reject" class="btn reject">Reject</button>
                    </form>
                    <?php else: ?>
                    <?= htmlspecialchars($vendor['status'] ?? '') ?>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
            <?php else: ?>
            <tr>
                <td colspan="5">No vendors found.</td>
            </tr>
            <?php endif; ?>
        </table>
    </div>

</body>

</html>