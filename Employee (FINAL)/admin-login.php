<?php
session_start();
$servername = "localhost:3308";
$username = "root"; 
$password = ""; 
$dbname = "admin_db"; 
$conn = new mysqli('localhost:3308', 'root', '', 'admin_db');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_POST['login_admin'])) {
    $adminEmail = $_POST['adminEmail'];
    $adminPassword = $_POST['adminPassword'];

    $sql = "SELECT * FROM admins WHERE email='$adminEmail'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $admin = $result->fetch_assoc();
      
        if (password_verify($adminPassword, $admin['password'])) {
            $_SESSION['admin'] = $adminEmail;
            header("Location: admin-dashboard.php"); 
            exit(); 
        } else {
            echo "Invalid email or password.";
        }
    } else {
        echo "Invalid email or password.";
    }
}

if (isset($_POST['register_admin'])) {
    $newAdminEmail = $_POST['newAdminEmail'];
    $newAdminPassword = $_POST['newAdminPassword'];

    $checkSql = "SELECT * FROM admins WHERE email='$newAdminEmail'";
    $checkResult = $conn->query($checkSql);

    if ($checkResult->num_rows > 0) {
        echo "Admin with this email already exists.";
    } else {
        $hashedPassword = password_hash($newAdminPassword, PASSWORD_DEFAULT);
        $insertSql = "INSERT INTO admins (email, password) VALUES ('$newAdminEmail', '$hashedPassword')";
        
        if ($conn->query($insertSql) === TRUE) {
            echo "New admin registered successfully.";
        } else {
            echo "Error: " . $conn->error;
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="styles.css">
   
    <link rel="stylesheet" href="dashboard.css">
    <title>Admin Login and Registration</title>
</head>
<style>
    body {
    font-family: Arial, sans-serif;
    background-color: #f8f9fa;
    margin: 0;
    padding: 20px;
}

.form-container {
    background-color: #ffffff;
    border-radius: 5px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    padding: 30px;
    margin: 20px auto;
    max-width: 400px;
}

.form-container h3 {
    margin-bottom: 20px;
    color: #343a40;
}

.form-container label {
    font-weight: bold;
    margin-bottom: 5px;
    display: block;
}

.form-container input[type="email"],
.form-container input[type="password"] {
    width: 100%;
    padding: 10px;
    margin-bottom: 15px;
    border: 1px solid #ced4da;
    border-radius: 5px;
}

.form-container button {
    background-color: #007bff;
    color: white;
    border: none;
    padding: 10px;
    border-radius: 5px;
    cursor: pointer;
    width: 100%;
}

.button:hover {
    background-color: #0056b3;
}

.modal-header {
    background-color: #007bff;
    color: white;
}

.modal-header .close {
    color: white;
}

.modal-body {
    padding: 20px;
}

.modal-body .form-group {
    margin-bottom: 15px;
}
.register-link {
    display: inline-block;
    margin-top: 10px;
    color: #007bff;
    text-decoration: none;
    font-weight: bold;
}

.register-link:hover {
    text-decoration: underline;
}
</style>
<body>
    <div class="form-container">
        <h3>Admin Login</h3>
        <form method="POST" action=""> 
            <label for="adminEmail">Email:</label>
            <input type="email" id="adminEmail" name="adminEmail" required>
            <label for="adminPassword">Password:</label>
            <input type="password" id="adminPassword" name="adminPassword" required>
            <button type="submit" name="login_admin">Login</button>
        </form>
        <a href="#" class="register-link" data-toggle="modal" data-target="#registerModal">Register as Admin</a>
    </div>

    <div class="modal fade" id="registerModal" tabindex="-1" role="dialog" aria-labelledby="registerModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="registerModalLabel">Admin Registration</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="newAdminEmail">Email:</label>
                            <input type="email" class="form-control" id="newAdminEmail" name="newAdminEmail" required>
                        </div>
                        <div class="form-group">
                            <label for="newAdminPassword">Password:</label>
                            <input type="password" class="form-control" id="newAdminPassword" name="newAdminPassword" required>
                        </div>
                        <button type="submit" name="register_admin" class="btn btn-primary">Register</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>