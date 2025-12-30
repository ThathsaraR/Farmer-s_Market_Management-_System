<?php


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Farmer's Market</title>
     <link rel="stylesheet" href="/FMSys/styles/ui.css" /> 
</head>
    
<body>
    <div class="container">
        <h2>Login</h2>
        <form action="login.php" method="post">
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <select name="role" required>
                <option value="">Select Role</option>
                <option value="admin">Admin</option>
                <option value="vendor">Vendor</option>
                <option value="customer">Customer</option>
            </select>

            <button type="submit">Login</button>
        </form>

        <div class="links">
            <a href="#">Forgot Password</a> <br><br>
            <a href="register.html">Create Account</a>
        </div>
    </div>
</body>
</html>