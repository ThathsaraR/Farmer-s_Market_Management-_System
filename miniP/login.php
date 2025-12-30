<?php
session_start();
include "conn.php";

$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = $_POST['email'];
    $pass = $_POST['password'];
    $role = $_POST['role'];

    if (empty($email) || empty($pass) || empty($role)) {
        $error_message = "Please fill all fields.";
    } else {

        // Decide table and id column based on role
        if ($role == "admin") {
            $table = "admin";
            $id_col = "admin_id";
        }
        if ($role == "vendor") {
            $table = "vendors";
            $id_col = "vendor_id";
        }
        if ($role == "customer") {
            $table = "customers";
            $id_col = "customer_id";
        }

        // SQL Query
        $sql = "SELECT $id_col, password, name FROM $table WHERE email='$email' LIMIT 1";
        $result = mysqli_query($conn, $sql);

        if ($result && mysqli_num_rows($result) == 1) {
            $row = mysqli_fetch_assoc($result);

            // Password check (plain or hashed)
            if (password_verify($pass, $row['password']) || $pass == $row['password']) {

                $_SESSION['user_id'] = $row[$id_col];
                $_SESSION['name'] = $row['name'];
                $_SESSION['role'] = $role;

                // Redirect to dashboard
                if ($role == "admin") header("Location: admin_dashboard.php");
                if ($role == "vendor") header("Location: vendor_dashboard.php");
                if ($role == "customer") header("Location: customer_dashboard.php");
                exit;

            } else {
                $error_message = "Incorrect password.";
                header("Location: index.html?error=" . urlencode($error_message));
                exit;
            }

        } else {
            $error_message = "Email does not exist.";
            header("Location: index.html?error=" . urlencode($error_message));
            exit;
        }
    }
}
?>
