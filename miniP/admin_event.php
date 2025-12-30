<?php
session_start();
include "conn.php";

// Helper: check table exists
function table_exists($conn, $tableName) {
    $tbl = mysqli_real_escape_string($conn, $tableName);
    $res = mysqli_query($conn, "SHOW TABLES LIKE '$tbl'");
    return ($res && mysqli_num_rows($res) > 0);
}

// Ensure events table exists (optional safe check)
// If you prefer not to auto-create, remove the block below.
// This creates a simple events table when it's missing.
if (!table_exists($conn, 'events')) {
    $create = "CREATE TABLE IF NOT EXISTS events (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(200) NOT NULL,
        event_date DATE NOT NULL,
        location VARCHAR(255) DEFAULT NULL,
        description TEXT,
        image VARCHAR(255) DEFAULT NULL,
        status VARCHAR(50) DEFAULT 'Upcoming'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    @mysqli_query($conn, $create);
}

// Initialize messages
$success_msg = "";
$error_msg = "";

// Handle add event form
if (isset($_POST['add_event'])) {
    // Basic required validation
    if (empty($_POST['name']) || empty($_POST['event_date']) || empty($_POST['location']) || empty($_POST['description'])) {
        $error_msg = "Please fill all required fields.";
    } else {
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $event_date = mysqli_real_escape_string($conn, $_POST['event_date']);
        $location = mysqli_real_escape_string($conn, $_POST['location']);
        $description = mysqli_real_escape_string($conn, $_POST['description']);

        // Handle image upload (simple)
        $image = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            // Make sure uploads folder exists
            $targetDir = __DIR__ . DIRECTORY_SEPARATOR . "uploads" . DIRECTORY_SEPARATOR;
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }

            $filename = time() . '_' . basename($_FILES['image']['name']);
            $targetPath = $targetDir . $filename;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                $image = mysqli_real_escape_string($conn, $filename);
            } else {
                $error_msg = "Failed to upload image. The event will be added without an image.";
            }
        }

        // Insert into DB
        $sql = "INSERT INTO events (name, event_date, location, description, image)
                VALUES ('$name', '$event_date', '$location', '$description', '$image')";
        if (mysqli_query($conn, $sql)) {
            // Redirect to avoid form resubmission and show success
            header("Location: " . $_SERVER['PHP_SELF'] . "?added=1");
            exit;
        } else {
            $error_msg = "Database error: " . mysqli_error($conn);
        }
    }
}

// If redirected after add
if (isset($_GET['added']) && $_GET['added'] == '1') {
    $success_msg = "Event added successfully.";
}

// Handle delete (simple, beginner-friendly via GET ?delete=ID)
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    if ($del_id > 0) {
        // get image filename to remove
        $qimg = mysqli_query($conn, "SELECT image FROM events WHERE id = $del_id");
        if ($qimg && mysqli_num_rows($qimg) > 0) {
            $rowimg = mysqli_fetch_assoc($qimg);
            if (!empty($rowimg['image'])) {
                $file = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $rowimg['image'];
                if (file_exists($file)) @unlink($file);
            }
        }
        mysqli_query($conn, "DELETE FROM events WHERE id = $del_id");
        header("Location: " . $_SERVER['PHP_SELF'] . "?deleted=1");
        exit;
    }
}
if (isset($_GET['deleted']) && $_GET['deleted'] == '1') {
    $success_msg = "Event deleted.";
}

// Fetch events safely (if table exists)
$events = [];
if (table_exists($conn, 'events')) {
    $res = mysqli_query($conn, "SELECT * FROM events ORDER BY event_date ASC");
    if ($res) {
        while ($r = mysqli_fetch_assoc($res)) {
            $events[] = $r;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Event Management</title>
<style>
    /* keep your exact CSS (unchanged layout) */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: Arial, Helvetica, sans-serif;
    }

    body {
        background: #f0f0f0;
        min-height: 100vh;
        color: #333;
    }

    header {
        width: 100%;
        background: #2b7a0b;
        padding: 15px 40px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        color: white;
    }

    header .logo {
        font-size: 24px;
        font-weight: bold;
    }

    header nav a {
        color: white;
        text-decoration: none;
        margin-left: 20px;
        font-weight: bold;
    }

    header nav a:hover {
        text-decoration: underline;
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

    form input,
    form textarea {
        width: 100%;
        padding: 10px;
        margin: 8px 0;
        border: 1px solid #ccc;
        border-radius: 5px;
    }

    form button {
        padding: 10px 15px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        background: #2b7a0b;
        color: white;
    }

    form button:hover {
        background: #1e5307;
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
        vertical-align: middle;
    }

    table th {
        background: #2b7a0b;
        color: white;
    }

    .edit-btn {
        background: #1e90ff;
        color: white;
        border: none;
        padding: 5px 10px;
        border-radius: 5px;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
    }

    .delete-btn {
        background: red;
        color: white;
        border: none;
        padding: 5px 10px;
        border-radius: 5px;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
    }

    .links {
        text-align: center;
        margin-top: 15px;
    }

    .links a {
        text-decoration: none;
        color: #2b7a0b;
        margin: 0 10px;
    }

    .links a:hover {
        text-decoration: underline;
    }

    .message {
        padding: 10px;
        margin-bottom: 12px;
        border-radius: 6px;
    }

    .success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .error {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    img.event-thumb {
        max-width: 80px;
        display: block;
        margin: 0 auto;
    }
</style>
</head>
<body>
<header>
    <div class="logo">🌾 Admin Event Management</div>
    <nav>
        <a href="admin_dashboard.php">Dashboard</a>
        <a href="index.php">Logout</a>
    </nav>
</header>

<div class="container">
    <h2>Add New Event</h2>

    <?php if ($success_msg): ?>
        <div class="message success"><?php echo htmlspecialchars($success_msg); ?></div>
    <?php endif; ?>
    <?php if ($error_msg): ?>
        <div class="message error"><?php echo htmlspecialchars($error_msg); ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <input type="text" name="name" placeholder="Event Name" required>
        <input type="date" name="event_date" required>
        <input type="text" name="location" placeholder="Location" required>
        <textarea name="description" placeholder="Description" rows="3" required></textarea>
        <input type="file" name="image" accept="image/*">
        <button type="submit" name="add_event">Add Event</button>
    </form>

    <h2>Event List</h2>
    <table>
        <tr>
            <th>Event Name</th>
            <th>Date</th>
            <th>Location</th>
            <th>Description</th>
            <th>Image</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>

        <?php if (empty($events)): ?>
            <tr>
                <td colspan="7">No events found.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($events as $row): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                    <td><?php echo htmlspecialchars($row['event_date']); ?></td>
                    <td><?php echo htmlspecialchars($row['location']); ?></td>
                    <td><?php echo htmlspecialchars($row['description']); ?></td>
                    <td>
                        <?php if (!empty($row['image']) && file_exists(__DIR__ . '/uploads/' . $row['image'])): ?>
                            <img class="event-thumb" src="uploads/<?php echo htmlspecialchars($row['image']); ?>" alt="Event Image">
                        <?php else: ?>
                            N/A
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($row['status'] ?? ''); ?></td>
                    <td>
                        <!-- Edit could link to an edit page (not implemented here) -->
                        <a class="edit-btn" href="admin_event_edit.php?id=<?php echo (int)$row['id']; ?>">Edit</a>
                        <a class="delete-btn" href="?delete=<?php echo (int)$row['id']; ?>" onclick="return confirm('Delete this event?');">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </table>

    <div class="links">
        <a href="admin_dashboard.php">Dashboard</a> |
        <a href="index.php">Logout</a>
    </div>
</div>
</body>
</html>
