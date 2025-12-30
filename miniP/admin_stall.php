<?php
session_start();
include 'conn.php'; // Make sure $conn is your mysqli connection

// ---------- Message holder ----------
$msg = '';
$msg_type = 'success';

// ---------- Handle Add Location ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_location') {
    $name = trim($_POST['name']);
    $total = (int)$_POST['total_units'];
    $desc = trim($_POST['description']);

    if ($name === '' || $total <= 0) {
        $msg = "Please enter a valid location name and total units (> 0).";
        $msg_type = 'error';
    } else {
        $avail = $total;
        $stmt = $conn->prepare("INSERT INTO locations (name, total_units, available_units, description) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('siis', $name, $total, $avail, $desc);
        if ($stmt->execute()) {
            $stmt->close();
            header("Location: admin_stall.php");
            exit;
        } else {
            $msg = "DB error adding location: " . $stmt->error;
            $msg_type = 'error';
            $stmt->close();
        }
    }
}

// ---------- Handle Edit Location ----------
$editing = false;
$edit_loc = null;
if (isset($_GET['edit_loc'])) {
    $editing = true;
    $edit_id = (int)$_GET['edit_loc'];
    $res = $conn->query("SELECT * FROM locations WHERE location_id = $edit_id");
    $edit_loc = $res ? $res->fetch_assoc() : null;
    if (!$edit_loc) {
        $msg = "Location not found for editing.";
        $msg_type = 'error';
        $editing = false;
    }
}

// ---------- Process Edit Form ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_location') {
    $loc_id = (int)$_POST['location_id'];
    $new_name = trim($_POST['name']);
    $new_total = (int)$_POST['total_units'];
    $new_desc = trim($_POST['description']);

    if ($loc_id <= 0 || $new_name === '' || $new_total <= 0) {
        $msg = "Please provide valid values for name and total units (>0).";
        $msg_type = 'error';
    } else {
        $loc_q = $conn->query("SELECT total_units, available_units FROM locations WHERE location_id = $loc_id");
        if (!$loc_q || $loc_q->num_rows === 0) {
            $msg = "Location not found.";
            $msg_type = 'error';
        } else {
            $loc_row = $loc_q->fetch_assoc();
            $old_total = (int)$loc_row['total_units'];
            $old_avail = (int)$loc_row['available_units'];
            $reserved = $old_total - $old_avail;

            if ($new_total < $reserved) {
                $msg = "Cannot set total units to $new_total because $reserved unit(s) already reserved.";
                $msg_type = 'error';
            } else {
                $new_avail = $new_total - $reserved;
                $stmt = $conn->prepare("UPDATE locations SET name = ?, total_units = ?, available_units = ?, description = ? WHERE location_id = ?");
                $stmt->bind_param('siisi', $new_name, $new_total, $new_avail, $new_desc, $loc_id);
                if ($stmt->execute()) {
                    $msg = "Location updated successfully.";
                    $msg_type = 'success';
                } else {
                    $msg = "Error updating location: " . $stmt->error;
                    $msg_type = 'error';
                }
                $stmt->close();
            }
        }
    }
    header("Location: admin_stall.php?msg=" . urlencode($msg) . "&type=" . urlencode($msg_type));
    exit;
}

// ---------- Handle Delete ----------
if (isset($_GET['delete_loc'])) {
    $del_id = (int)$_GET['delete_loc'];
    $r = $conn->query("SELECT COUNT(*) AS c FROM reservations WHERE location_id = $del_id");
    $count = $r ? (int)$r->fetch_assoc()['c'] : 0;
    if ($count > 0) {
        $msg = "Cannot delete location: there are $count reservation(s).";
        $msg_type = 'error';
    } else {
        $stmt = $conn->prepare("DELETE FROM locations WHERE location_id = ?");
        $stmt->bind_param('i', $del_id);
        if ($stmt->execute()) {
            $msg = "Location deleted successfully.";
            $msg_type = 'success';
        } else {
            $msg = "Failed to delete location: " . $stmt->error;
            $msg_type = 'error';
        }
        $stmt->close();
    }
    header("Location: admin_stall.php?msg=" . urlencode($msg) . "&type=" . urlencode($msg_type));
    exit;
}

// ---------- Handle reservation actions via POST ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['reservation_id'])) {
    $action = $_POST['action'];
    $res_id = (int)$_POST['reservation_id'];
    $msg = '';
    $msg_type = 'success';

    if ($action === 'approve') {
        $conn->begin_transaction();
        $res_q = $conn->query("SELECT location_id, area_required, status FROM reservations WHERE reservation_id = $res_id FOR UPDATE");
        $res = $res_q ? $res_q->fetch_assoc() : null;

        if (!$res) {
            $msg = "Reservation not found.";
            $msg_type = 'error';
            $conn->rollback();
        } elseif ($res['status'] !== 'Pending') {
            $msg = "Only pending reservations can be approved.";
            $msg_type = 'error';
            $conn->rollback();
        } else {
            $loc_id = (int)$res['location_id'];
            $need = (int)$res['area_required'];
            $loc_q = $conn->query("SELECT available_units FROM locations WHERE location_id = $loc_id FOR UPDATE");
            $loc = $loc_q ? $loc_q->fetch_assoc() : null;

            if (!$loc || $loc['available_units'] < $need) {
                $msg = "Not enough units available.";
                $msg_type = 'error';
                $conn->rollback();
            } else {
                $newAvail = $loc['available_units'] - $need;
                $now = date('Y-m-d H:i:s');
                $ok1 = $conn->query("UPDATE locations SET available_units = $newAvail WHERE location_id = $loc_id");
                $ok2 = $conn->query("UPDATE reservations SET status='Approved', updated_at='$now' WHERE reservation_id = $res_id");

                if ($ok1 && $ok2) {
                    $conn->commit();
                    $msg = "Reservation #$res_id approved.";
                } else {
                    $conn->rollback();
                    $msg = "Failed to approve reservation.";
                    $msg_type = 'error';
                }
            }
        }
    } elseif ($action === 'reject') {
        $now = date('Y-m-d H:i:s');
        $ok = $conn->query("UPDATE reservations SET status='Rejected', updated_at='$now' WHERE reservation_id = $res_id AND status = 'Pending'");
        if ($ok && $conn->affected_rows > 0) {
            $msg = "Reservation rejected.";
        } else {
            $msg = "Failed to reject reservation.";
            $msg_type = 'error';
        }
    } elseif ($action === 'cancel') {
        $conn->begin_transaction();
        $res_q = $conn->query("SELECT location_id, area_required, status FROM reservations WHERE reservation_id = $res_id FOR UPDATE");
        $res = $res_q ? $res_q->fetch_assoc() : null;

        if (!$res || $res['status'] !== 'Approved') {
            $msg = "Only approved reservations can be cancelled.";
            $msg_type = 'error';
            $conn->rollback();
        } else {
            $loc_id = (int)$res['location_id'];
            $area = (int)$res['area_required'];
            $loc_q = $conn->query("SELECT available_units FROM locations WHERE location_id = $loc_id FOR UPDATE");
            $loc = $loc_q ? $loc_q->fetch_assoc() : null;

            if (!$loc) {
                $msg = "Location not found.";
                $msg_type = 'error';
                $conn->rollback();
            } else {
                $newAvail = $loc['available_units'] + $area;
                $ok1 = $conn->query("UPDATE locations SET available_units = $newAvail WHERE location_id = $loc_id");
                $ok2 = $conn->query("UPDATE reservations SET status='Cancelled', updated_at=NOW() WHERE reservation_id = $res_id");
                if ($ok1 && $ok2) {
                    $conn->commit();
                    $msg = "Reservation cancelled.";
                } else {
                    $conn->rollback();
                    $msg = "Failed to cancel reservation.";
                    $msg_type = 'error';
                }
            }
        }
    }

    header("Location: admin_stall.php?msg=" . urlencode($msg) . "&type=" . urlencode($msg_type));
    exit;
}

// ---------- Display message ----------
if (isset($_GET['msg'])) {
    $msg = $_GET['msg'];
    $msg_type = $_GET['type'] ?? 'success';
}

// ---------- Fetch data ----------
$locations = $conn->query("SELECT * FROM locations ORDER BY name ASC");
$reservations = $conn->query("
  SELECT r.*, v.name AS vendor_name, l.name AS location_name
  FROM reservations r
  LEFT JOIN locations l ON r.location_id = l.location_id
  LEFT JOIN vendors v ON r.vendor_id = v.vendor_id
  ORDER BY r.created_at DESC
");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Stall Management</title>
    <style>
        /* Reset & basic */
        * { margin:0; padding:0; box-sizing:border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background:#eef2f7; color:#333; line-height:1.6; }
        a { text-decoration:none; }
        button { cursor:pointer; }

        /* Header */
        header { background:#2c7a0b; color:white; padding:15px 30px; display:flex; justify-content:space-between; align-items:center; }
        header nav a { color:white; margin-left:20px; font-weight:bold; }
        header nav a:hover { text-decoration:underline; }

        /* Wrapper */
        .wrap { max-width:1200px; margin:25px auto; background:white; padding:20px; border-radius:10px; box-shadow:0 2px 10px rgba(0,0,0,0.1); }

        /* Titles */
        h1,h2 { color:#2c7a0b; margin-bottom:15px; }

        /* Messages */
        .msg { padding:12px; border-radius:5px; margin-bottom:20px; font-weight:bold; }
        .success { background:#e6ffea; border:1px solid #b6efc2; }
        .error { background:#ffe6e6; border:1px solid #f1b6b6; }

        /* Forms */
        form input, form textarea { width:100%; padding:10px; margin-bottom:10px; border:1px solid #ccc; border-radius:5px; }
        form button { background:#2c7a0b; color:white; border:none; padding:10px 15px; border-radius:5px; font-weight:bold; }
        form button:hover { background:#1e5307; }
        .cancel { background:#c0392b; }
        .cancel:hover { background:#922b21; }

        /* Tables */
        table { width:100%; border-collapse:collapse; margin-bottom:25px; }
        th, td { padding:12px; text-align:center; border:1px solid #ddd; }
        th { background:#2c7a0b; color:white; }
        tr:nth-child(even) { background:#f9f9f9; }
        .actions form { display:inline-block; margin:0 2px; }

        /* Buttons inside table */
        .btn { padding:6px 10px; border-radius:4px; border:none; font-size:0.9rem; color:white; }
        .btn-approve { background:#27ae60; }
        .btn-approve:hover { background:#1e8449; }
        .btn-reject { background:#c0392b; }
        .btn-reject:hover { background:#922b21; }
        .btn-cancel { background:#f39c12; }
        .btn-cancel:hover { background:#d68910; }

        /* Responsive */
        @media (max-width:768px) {
            table, thead, tbody, th, td, tr { display:block; }
            th { position:absolute; top:-9999px; left:-9999px; }
            td { position:relative; padding-left:50%; text-align:left; }
            td:before { content: attr(data-label); position:absolute; left:15px; font-weight:bold; }
        }
    </style>
</head>
<body>
<header>
    <div>🌾 Admin Stall Management</div>
    <nav>
        <a href="admin_dashboard.php">Dashboard</a>
        <a href="index.html">Logout</a>
    </nav>
</header>

<div class="wrap">
    <h1>Admin Stall Management</h1>

    <?php if($msg): ?>
    <div class="msg <?php echo ($msg_type==='error')?'error':'success'; ?>">
        <?php echo htmlspecialchars($msg); ?>
    </div>
    <?php endif; ?>

    <!-- Add Location Form -->
    <h2>Add New Location</h2>
    <form method="post">
        <input type="hidden" name="action" value="add_location">
        <input type="text" name="name" placeholder="Location Name" required>
        <input type="number" name="total_units" min="1" placeholder="Total Units" required>
        <textarea name="description" placeholder="Description (optional)"></textarea>
        <button type="submit">Add Location</button>
    </form>

    <!-- Edit Location Form -->
    <?php if($editing && $edit_loc): ?>
    <h2>Edit Location: <?php echo htmlspecialchars($edit_loc['name']); ?></h2>
    <form method="post">
        <input type="hidden" name="action" value="edit_location">
        <input type="hidden" name="location_id" value="<?php echo (int)$edit_loc['location_id']; ?>">
        <input type="text" name="name" value="<?php echo htmlspecialchars($edit_loc['name']); ?>" required>
        <input type="number" name="total_units" value="<?php echo (int)$edit_loc['total_units']; ?>" min="1" required>
        <textarea name="description"><?php echo htmlspecialchars($edit_loc['description']); ?></textarea>
        <button type="submit">Save Changes</button>
        <a class="cancel" href="admin_stall.php">Cancel</a>
    </form>
    <?php endif; ?>

    <!-- Locations Table -->
    <h2>Locations</h2>
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Total</th>
                <th>Available</th>
                <th>Description</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while($loc=$locations->fetch_assoc()): ?>
            <tr>
                <td data-label="Name"><?php echo htmlspecialchars($loc['name']); ?></td>
                <td data-label="Total"><?php echo (int)$loc['total_units']; ?></td>
                <td data-label="Available"><?php echo (int)$loc['available_units']; ?></td>
                <td data-label="Description"><?php echo htmlspecialchars($loc['description']); ?></td>
                <td data-label="Actions" class="actions">
                    <a href="?edit_loc=<?php echo (int)$loc['location_id']; ?>">Edit</a>
                    <?php
                    $loc_id = (int)$loc['location_id'];
                    $rq = $conn->query("SELECT COUNT(*) AS c FROM reservations WHERE location_id = $loc_id");
                    $rc = $rq ? (int)$rq->fetch_assoc()['c'] : 0;
                    if($rc===0): ?>
                        <a href="?delete_loc=<?php echo $loc_id; ?>" onclick="return confirm('Delete this location?')">Delete</a>
                    <?php else: ?>
                        <span><?php echo $rc; ?> reservation(s)</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <!-- Reservations Table -->
    <h2>Reservations</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Vendor</th>
                <th>Location</th>
                <th>Area</th>
                <th>Date</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if($reservations->num_rows===0): ?>
            <tr><td colspan="7">No reservations yet.</td></tr>
            <?php else: ?>
            <?php while($r=$reservations->fetch_assoc()): ?>
            <tr>
                <td data-label="ID"><?php echo (int)$r['reservation_id']; ?></td>
                <td data-label="Vendor"><?php echo htmlspecialchars($r['vendor_name'] ?? 'Vendor#'.$r['vendor_id']); ?></td>
                <td data-label="Location"><?php echo htmlspecialchars($r['location_name'] ?? 'Location#'.$r['location_id']); ?></td>
                <td data-label="Area"><?php echo (int)$r['area_required']; ?></td>
                <td data-label="Date"><?php echo htmlspecialchars($r['reserve_date']); ?></td>
                <td data-label="Status"><?php echo htmlspecialchars($r['status']); ?></td>
                <td data-label="Actions" class="actions">
                    <?php if($r['status']==='Pending'): ?>
                        <form method="post">
                            <input type="hidden" name="action" value="approve">
                            <input type="hidden" name="reservation_id" value="<?php echo (int)$r['reservation_id']; ?>">
                            <button type="submit" class="btn btn-approve">Approve</button>
                        </form>
                        <form method="post">
                            <input type="hidden" name="action" value="reject">
                            <input type="hidden" name="reservation_id" value="<?php echo (int)$r['reservation_id']; ?>">
                            <button type="submit" class="btn btn-reject">Reject</button>
                        </form>
                    <?php elseif($r['status']==='Approved'): ?>
                        <form method="post">
                            <input type="hidden" name="action" value="cancel">
                            <input type="hidden" name="reservation_id" value="<?php echo (int)$r['reservation_id']; ?>">
                            <button type="submit" class="btn btn-cancel">Cancel</button>
                        </form>
                    <?php else: ?>
                        <span>-</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>
