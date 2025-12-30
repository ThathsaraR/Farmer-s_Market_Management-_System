<?php
session_start();
include "conn.php";  // Make sure this connects to your database

$message = "";

// ------------------- VENDOR REGISTRATION -------------------
if (isset($_POST['register_vendor'])) {
    $name     = $_POST['v_name'];
    $nic      = $_POST['v_nic'];
    $contact  = $_POST['v_contact'];
    $address  = $_POST['v_address'];
    $category = $_POST['v_category'];
    $password = $_POST['v_password'];

    // Simple SQL insert
    $sql = "INSERT INTO vendors (name, nic, contact, address, category, password, status)
            VALUES ('$name', '$nic', '$contact', '$address', '$category', '$password', 'Pending')";

    if (mysqli_query($conn, $sql)) {
        $message = "Vendor registered successfully! Wait for admin approval.";
        header("Location: index.html");
    } else {
        $message = "Error: " . mysqli_error($conn);
    }
}

// ------------------- CUSTOMER REGISTRATION -------------------
if (isset($_POST['register_customer'])) {
    $name     = $_POST['c_name'];
    $contact  = $_POST['c_contact'];
    $email    = $_POST['c_email'];
    $password = $_POST['c_password'];

    // Simple SQL insert
    $sql = "INSERT INTO customers (name, contact, email, password)
            VALUES ('$name', '$contact', '$email', '$password')";

    if (mysqli_query($conn, $sql)) {
        $message = "Customer registered successfully! You can now login.";
        header("Location: index.html");
    } else {
        $message = "Error: " . mysqli_error($conn);
    }
}
?>