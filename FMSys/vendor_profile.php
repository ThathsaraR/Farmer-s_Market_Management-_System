<?php
session_start();
include 'conn.php';

// Check if vendor is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'vendor') {
    header('Location: index.html');
    exit;
}

$vendor_id = $_SESSION['user_id'];
$message = "";

// Fetch vendor details
$result = mysqli_query($conn, "SELECT * FROM vendors WHERE vendor_id='$vendor_id'");
$vendor = mysqli_fetch_assoc($result);

if (!$vendor) {
    die("Vendor not found!");
}

// Handle update
if (isset($_POST['update'])) {
    $name = $_POST['name'];
    $nic = $_POST['nic'];
    $contact = $_POST['contact'];
    $address = $_POST['address'];
    $category = $_POST['category'];
    $email = $_POST['email'];
    $password = $_POST['password']; // In real apps, hash passwords

    $sql = "UPDATE vendors SET 
            name='$name', 
            nic='$nic', 
            contact='$contact', 
            address='$address', 
            category='$category', 
            email='$email', 
            password='$password' 
            WHERE vendor_id='$vendor_id'";

    if (mysqli_query($conn, $sql)) {
        $message = "Profile updated successfully!";
        // Update $vendor array so page shows new values
        $vendor['name'] = $name;
        $vendor['nic'] = $nic;
        $vendor['contact'] = $contact;
        $vendor['address'] = $address;
        $vendor['category'] = $category;
        $vendor['email'] = $email;
        $vendor['password'] = $password;
    } else {
        $message = "Update failed: " . mysqli_error($conn);
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Vendor Profile</title>
<style>
* {margin:0; padding:0; box-sizing:border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;}
body {
    background: #f4f7f8;
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
}
.profile-card {
    background: #fff;
    padding: 30px;
    border-radius: 15px;
    width: 450px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}
h2 {
    text-align:center;
    color:#2b7a0b;
    margin-bottom:20px;
}
.profile-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
}
.profile-label {
    font-weight: bold;
    color:#555;
}
.profile-value {
    color:#333;
}
button {
    padding: 10px 20px;
    border-radius: 5px;
    border:none;
    cursor:pointer;
    font-weight:bold;
    transition: all 0.3s ease;
}
#editBtn {background:#2b7a0b; color:white; width:100%; margin-top:15px;}
#editBtn:hover {background:#1e5307;}
#saveBtn {background:#2b7a0b; color:white; margin-right:10px;}
#cancelBtn {background:#c0392b; color:white;}
form input, form textarea {
    width:100%;
    padding:10px;
    margin:5px 0 15px 0;
    border-radius:5px;
    border:1px solid #ccc;
}
.hidden {display:none;}
.message {
    background: #e6ffea;
    border-left: 5px solid #2b7a0b;
    padding:10px;
    margin-bottom:15px;
    border-radius:5px;
    text-align:center;
}
.links {
    text-align:center;
    margin-top:15px;
}
.links a {
    text-decoration:none;
    color:#2b7a0b;
    margin:0 10px;
}
.links a:hover {text-decoration:underline;}
</style>
<script>
function toggleEdit() {
    document.getElementById('profileView').classList.toggle('hidden');
    document.getElementById('profileForm').classList.toggle('hidden');
}
</script>
</head>
<body>

<div class="profile-card">
    <h2>Vendor Profile</h2>

    <?php if($message): ?>
    <div class="message"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <!-- Display View -->
    <div id="profileView">
        <div class="profile-row"><span class="profile-label">Shop Name:</span> <span class="profile-value"><?php echo htmlspecialchars($vendor['name'] ?? ''); ?></span></div>
        <div class="profile-row"><span class="profile-label">NIC:</span> <span class="profile-value"><?php echo htmlspecialchars($vendor['nic'] ?? ''); ?></span></div>
        <div class="profile-row"><span class="profile-label">Contact:</span> <span class="profile-value"><?php echo htmlspecialchars($vendor['contact'] ?? ''); ?></span></div>
        <div class="profile-row"><span class="profile-label">Address:</span> <span class="profile-value"><?php echo htmlspecialchars($vendor['address'] ?? ''); ?></span></div>
        <div class="profile-row"><span class="profile-label">Category:</span> <span class="profile-value"><?php echo htmlspecialchars($vendor['category'] ?? ''); ?></span></div>
        <div class="profile-row"><span class="profile-label">Email:</span> <span class="profile-value"><?php echo htmlspecialchars($vendor['email'] ?? ''); ?></span></div>
        <div class="profile-row"><span class="profile-label">Password:</span> <span class="profile-value"><?php echo htmlspecialchars($vendor['password'] ?? ''); ?></span></div>
        <button id="editBtn" onclick="toggleEdit()">Edit Profile</button>
    </div>

    <!-- Edit Form -->
    <form method="POST" id="profileForm" class="hidden">
        <input type="text" name="name" value="<?php echo htmlspecialchars($vendor['name'] ?? ''); ?>" placeholder="Shop Name" required>
        <input type="text" name="nic" value="<?php echo htmlspecialchars($vendor['nic'] ?? ''); ?>" placeholder="NIC" required>
        <input type="text" name="contact" value="<?php echo htmlspecialchars($vendor['contact'] ?? ''); ?>" placeholder="Contact" required>
        <textarea name="address" rows="3" placeholder="Address" required><?php echo htmlspecialchars($vendor['address'] ?? ''); ?></textarea>
        <input type="text" name="category" value="<?php echo htmlspecialchars($vendor['category'] ?? ''); ?>" placeholder="Category">
        <input type="email" name="email" value="<?php echo htmlspecialchars($vendor['email'] ?? ''); ?>" placeholder="Email" required>
        <input type="text" name="password" value="<?php echo htmlspecialchars($vendor['password'] ?? ''); ?>" placeholder="Password" required>
        <div style="display:flex; justify-content:flex-end;">
            <button type="submit" name="update" id="saveBtn">Save</button>
            <button type="button" id="cancelBtn" onclick="toggleEdit()">Cancel</button>
        </div>
    </form>

    <div class="links">
        <a href="vendor_dashboard.php">Dashboard</a> |
        <a href="vendor_orders.php">My Orders</a> |
        <a href="index.html">Logout</a>
    </div>
</div>

</body>
</html>
