<?php
session_start();
include "conn.php";

// Check login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: index.html");
    exit;
}

$customer_id = $_SESSION['user_id'];
$message = "";

// -------- FETCH CUSTOMER DATA -------- //
$sql = "SELECT * FROM customers WHERE customer_id='$customer_id'";
$result = mysqli_query($conn, $sql);
$customer = mysqli_fetch_assoc($result);

if (!$customer) {
    die("Customer not found!");
}

// -------- UPDATE CUSTOMER -------- //
if (isset($_POST['update'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $contact = $_POST['contact'];
    $address = $_POST['address'];
    $password = $_POST['password']; // In production: use password_hash()

    $update = "UPDATE customers SET 
                name='$name',
                email='$email',
                contact='$contact',
                address='$address',
                password='$password'
               WHERE customer_id='$customer_id'";

    if (mysqli_query($conn, $update)) {
        $message = "Profile updated successfully!";

        // update values displayed without reloading page
        $customer['name'] = $name;
        $customer['email'] = $email;
        $customer['contact'] = $contact;
        $customer['address'] = $address;
        $customer['password'] = $password;

    } else {
        $message = "Update failed: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Customer Profile</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
* {margin:0; padding:0; box-sizing:border-box; font-family:Arial;}
body {background:#eef1f2; display:flex; justify-content:center; align-items:center; height:100vh;}
.container {
    width:420px; background:#fff; padding:25px; border-radius:12px;
    box-shadow:0 4px 15px rgba(0,0,0,0.15);
}
h2 {text-align:center; color:#2b7a0b; margin-bottom:18px;}
.row {display:flex; justify-content:space-between; margin-bottom:10px;}
.label {font-weight:bold; color:#555;}
.value {color:#333;}
input, textarea {
    width:100%; padding:10px; margin-bottom:12px;
    border:1px solid #ccc; border-radius:6px;
}
button {
    padding:10px; border:none; border-radius:6px; cursor:pointer;
    font-weight:bold; transition:.3s;
}
#editBtn {background:#2b7a0b; color:white; width:100%;}
#editBtn:hover {background:#1e5307;}
#saveBtn {background:#2b7a0b; color:white; margin-right:10px;}
#cancelBtn {background:#c0392b; color:white;}
.hidden {display:none;}
.message {
    background:#eaffea; border-left:5px solid #2b7a0b;
    padding:10px; margin-bottom:12px; border-radius:6px; text-align:center;
}
.links {text-align:center; margin-top:15px;}
.links a {color:#2b7a0b; text-decoration:none; margin:0 10px;}
.links a:hover {text-decoration:underline;}
</style>

<script>
function toggleEdit(){
    document.getElementById("view").classList.toggle("hidden");
    document.getElementById("form").classList.toggle("hidden");
}
</script>
</head>

<body>
<div class="container">
    <h2>Customer Profile</h2>

    <?php if ($message): ?>
        <div class="message"><?php echo $message; ?></div>
    <?php endif; ?>

    <!-- VIEW MODE -->
    <div id="view">
        <div class="row"><span class="label">Name:</span> <span class="value"><?php echo $customer['name']; ?></span></div>
        <div class="row"><span class="label">Email:</span> <span class="value"><?php echo $customer['email']; ?></span></div>
        <div class="row"><span class="label">Contact:</span> <span class="value"><?php echo $customer['contact']; ?></span></div>
        <div class="row"><span class="label">Address:</span> <span class="value"><?php echo $customer['address']; ?></span></div>
        <div class="row"><span class="label">Password:</span> <span class="value"><?php echo $customer['password']; ?></span></div>

        <button id="editBtn" onclick="toggleEdit()">Edit Profile</button>
    </div>

    <!-- EDIT FORM -->
    <form method="post" id="form" class="hidden">
        <input type="text" name="name" value="<?php echo $customer['name']; ?>" required>
        <input type="email" name="email" value="<?php echo $customer['email']; ?>" required>
        <input type="text" name="contact" value="<?php echo $customer['contact']; ?>" required>
        <textarea name="address" rows="3" required><?php echo $customer['address']; ?></textarea>
        <input type="text" name="password" value="<?php echo $customer['password']; ?>" required>

        <div style="display:flex; justify-content:flex-end;">
            <button type="submit" name="update" id="saveBtn">Save</button>
            <button type="button" id="cancelBtn" onclick="toggleEdit()">Cancel</button>
        </div>
    </form>

    <div class="links">
        <a href="customer_dashboard.php">Dashboard</a> |
        <a href="customer_orders.php">My Orders</a> |
        <a href="index.html">Logout</a>
    </div>
</div>
</body>
</html>
