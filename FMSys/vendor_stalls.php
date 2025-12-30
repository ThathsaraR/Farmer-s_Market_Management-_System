<?php
session_start();
include 'conn.php'; 

// Simple login check for vendor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'vendor') {
    header('Location: login.php');
    exit;
}

$vendor_id = $_SESSION['user_id'];
$message = "";

// --- Make Reservation (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reserve'])) {

    $location_id = intval($_POST['location_id']);
    $area_required = intval($_POST['area_required']);
    $purpose = htmlspecialchars($_POST['purpose']);

    // Check available units
    $stmt = $conn->prepare("SELECT total_units, available_units FROM locations WHERE location_id=? LIMIT 1");
    $stmt->bind_param("i", $location_id);
    $stmt->execute();
    $loc = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($loc && $loc['available_units'] >= $area_required) {
        // Insert reservation
        $stmt = $conn->prepare("INSERT INTO reservations (vendor_id, location_id, area_required, reserve_date, purpose, status, created_at) VALUES (?, ?, ?, CURDATE(), ?, 'pending', NOW())");
        $stmt->bind_param("iiis", $vendor_id, $location_id, $area_required, $purpose);
        $stmt->execute();
        $stmt->close();

        // Update available units
        $stmt = $conn->prepare("UPDATE locations SET available_units = available_units - ? WHERE location_id=?");
        $stmt->bind_param("ii", $area_required, $location_id);
        $stmt->execute();
        $stmt->close();

        $message = "Reservation submitted successfully! Waiting for admin approval.";

        // Prevent form resubmission on refresh
        header("Location: vendor_stalls.php?success=1");
        exit;
    } else {
        $message = "Not enough available units for this location.";
    }
}

// --- Cancel Reservation ---
if (isset($_GET['cancel'])) {
    $res_id = intval($_GET['cancel']);
    $stmt = $conn->prepare("SELECT location_id, area_required FROM reservations WHERE reservation_id=? AND vendor_id=? AND status='pending'");
    $stmt->bind_param("ii", $res_id, $vendor_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($res) {
        $location_id = $res['location_id'];
        $area = $res['area_required'];

        // Delete reservation
        $stmt = $conn->prepare("DELETE FROM reservations WHERE reservation_id=?");
        $stmt->bind_param("i", $res_id);
        $stmt->execute();
        $stmt->close();

        // Restore available units
        $stmt = $conn->prepare("UPDATE locations SET available_units = available_units + ? WHERE location_id=?");
        $stmt->bind_param("ii", $area, $location_id);
        $stmt->execute();
        $stmt->close();

        $message = "Reservation cancelled successfully.";
        header("Location: vendor_stalls.php?cancelled=1");
        exit;
    }
}

// --- Fetch all locations ---
$locations = $conn->query("SELECT * FROM locations ORDER BY location_id ASC");

// --- Fetch vendor reservations ---
$stmt = $conn->prepare("SELECT r.*, l.name as location_name FROM reservations r JOIN locations l ON r.location_id=l.location_id WHERE r.vendor_id=? ORDER BY r.reserve_date DESC");
$stmt->bind_param("i", $vendor_id);
$stmt->execute();
$reservations = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Stall Reservations</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, Helvetica, sans-serif;
        }

        body {
            background: #f0f9f0;
            min-height: 100vh;
            color: #333;
        }

        header {
            width: 100%;
            background: #2b7a0b;
            padding: 20px;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        header a {
            color: white;
            text-decoration: none;
            margin-left: 15px;
            font-weight: bold;
        }

        header a:hover {
            text-decoration: underline;
        }

        .container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            max-width: 1000px;
            margin: 20px auto;
        }

        h2 {
            text-align: center;
            color: #2b7a0b;
            margin-bottom: 15px;
        }

        .message {
            padding: 10px;
            background: #ddffdd;
            border-left: 5px solid #2b7a0b;
            margin-bottom: 15px;
            border-radius: 5px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            padding: 10px;
            border: 1px solid #ccc;
            text-align: center;
        }

        th {
            background: #2b7a0b;
            color: white;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 5px;
            margin: 3px 0;
            border-radius: 4px;
            border: 1px solid #ccc;
        }

        button {
            padding: 6px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            background: #2b7a0b;
            color: white;
        }

        button:hover {
            background: #1e5307;
        }

        .cancel-btn {
            background: #dc3545;
            color: white;
        }

        .cancel-btn:hover {
            background: #a71d2a;
        }
    </style>
</head>

<body>

    <header>
        <div>Stall Reservations</div>
        <nav>
            <a href="vendor_dashboard.php">Dashboard</a>
            <a href="vendor_profile.php">Profile</a>
            <a href="index.php">Logout</a>
        </nav>
    </header>

    <div class="container">
        <h2>Available Locations</h2>

        <?php if(isset($_GET['success']) || isset($_GET['cancelled'])): ?>
        <div class="message">
            <?= isset($_GET['success']) ? "Reservation submitted successfully!" : "Reservation cancelled." ?>
        </div>
        <?php elseif($message): ?>
        <div class="message">
            <?= $message ?>
        </div>
        <?php endif; ?>

        <form method="POST">
            <table>
                <tr>
                    <th>Location</th>
                    <th>Description</th>
                    <th>Total Units</th>
                    <th>Available Units</th>
                    <th>Reserve Units</th>
                    <th>Purpose</th>
                    <th>Action</th>
                </tr>
                <?php while($loc = $locations->fetch_assoc()): ?>
                <tr>
                    <td>
                        <?= htmlspecialchars($loc['name']) ?>
                    </td>
                    <td>
                        <?= htmlspecialchars($loc['description']) ?>
                    </td>
                    <td>
                        <?= $loc['total_units'] ?>
                    </td>
                    <td>
                        <?= $loc['available_units'] ?>
                    </td>
                    <td>
                        <?php if($loc['available_units']>0): ?>
                        <input type="number" name="area_required" min="1" max="<?= $loc['available_units'] ?>" required>
                        <input type="hidden" name="location_id" value="<?= $loc['location_id'] ?>">
                        <?php else: ?>Full
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if($loc['available_units']>0): ?>
                        <input type="text" name="purpose" placeholder="Purpose" required>
                        <?php else: ?>-
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if($loc['available_units']>0): ?>
                        <button type="submit" name="reserve">Reserve</button>
                        <?php else: ?>-
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </table>
        </form>

        <h2>My Reservations</h2>
        <table>
            <tr>
                <th>ID</th>
                <th>Location</th>
                <th>Units</th>
                <th>Date</th>
                <th>Purpose</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
            <?php while($r = $reservations->fetch_assoc()): ?>
            <tr>
                <td>
                    <?= $r['reservation_id'] ?>
                </td>
                <td>
                    <?= htmlspecialchars($r['location_name']) ?>
                </td>
                <td>
                    <?= $r['area_required'] ?>
                </td>
                <td>
                    <?= $r['reserve_date'] ?>
                </td>
                <td>
                    <?= htmlspecialchars($r['purpose']) ?>
                </td>
                <td>
                    <?= ucfirst($r['status']) ?>
                </td>
                <td>
                    <?php if($r['status']=='pending'): ?>
                    <a href="?cancel=<?= $r['reservation_id'] ?>" onclick="return confirm('Cancel this reservation?')">
                        <button class="cancel-btn">Cancel</button>
                    </a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>

</body>

</html>