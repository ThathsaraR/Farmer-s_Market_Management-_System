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
<link rel="stylesheet" href="/FMSys/styles/ui.css" />
</head>
<body>
<header class="header">
    <div class="brand"><div class="logo">🌾 Admin Event Management</div></div>
    <nav>
        <a class="btn-ghost" href="admin_dashboard.php">Dashboard</a>
        <a class="btn-ghost" href="index.php">Logout</a>
    </nav>
</header>

<div class="container card card-lg">
    <h2>Add New Event</h2>

    <?php if ($success_msg): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success_msg); ?></div>
    <?php endif; ?>
    <?php if ($error_msg): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error_msg); ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <label class="label">Event Name<input class="input" type="text" name="name" placeholder="Event Name" required></label>
        <label class="label">Date<input class="input" type="date" name="event_date" required></label>
        <label class="label">Location<input class="input" type="text" name="location" placeholder="Location" required></label>
        <label class="label">Description<textarea class="input" name="description" placeholder="Description" rows="3" required></textarea></label>
        <label class="label">Image<input class="input" type="file" name="image" accept="image/*"></label>
        <button class="btn btn-primary" type="submit" name="add_event">Add Event</button>
    </form>

    <h2>Event List</h2>
    <div class="table-wrap">
    <table class="table" aria-describedby="event-list">
        <thead>
        <tr>
            <th>Event Name</th>
            <th>Date</th>
            <th>Location</th>
            <th>Description</th>
            <th>Image</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>

        <?php if (empty($events)): ?>
            <tr>
                <td colspan="7">No events found.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($events as $row): ?>
                <tr>
                    <td data-label="Event Name"><?php echo htmlspecialchars($row['name']); ?></td>
                    <td data-label="Date"><?php echo htmlspecialchars($row['event_date']); ?></td>
                    <td data-label="Location"><?php echo htmlspecialchars($row['location']); ?></td>
                    <td data-label="Description"><?php echo htmlspecialchars($row['description']); ?></td>
                    <td data-label="Image">
                        <?php if (!empty($row['image']) && file_exists(__DIR__ . '/uploads/' . $row['image'])): ?>
                            <img class="event-thumb" src="uploads/<?php echo htmlspecialchars($row['image']); ?>" alt="Event Image">
                        <?php else: ?>
                            N/A
                        <?php endif; ?>
                    </td>
                    <td data-label="Status"><?php echo htmlspecialchars($row['status'] ?? ''); ?></td>
                    <td data-label="Actions">
                        <a class="btn btn-secondary" href="admin_event_edit.php?id=<?php echo (int)$row['id']; ?>">Edit</a>
                        <a class="btn btn-danger" href="?delete=<?php echo (int)$row['id']; ?>" onclick="return confirm('Delete this event?');">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
    </div>

    <div class="links">
        <a href="admin_dashboard.php">Dashboard</a> |
        <a href="index.php">Logout</a>
    </div>
</div>
</body>
</html>
