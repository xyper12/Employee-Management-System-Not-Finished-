<?php

$servername = "localhost:3308"; 
$username = "root"; 
$password = ""; 
$dbname = "admin_dashboard";


$conn = new mysqli('localhost:3308', 'root', '', 'admin_dashboard');


if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register_employee'])) {
    $name = $_POST['employeeName'];
    $email = $_POST['employeeEmail'];
    $address = $_POST['employeeAddress'];
    $contact = $_POST['employeeContact'];
    $birthdate = $_POST['employeeBirthdate'];
    $password = password_hash($_POST['employeePassword'], PASSWORD_DEFAULT); 

    $stmt = $conn->prepare("INSERT INTO employees (name, email, address, contact, birthdate, password) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $name, $email, $address, $contact, $birthdate, $password);
    $stmt->execute();
    $stmt->close();
    header("Location: employee-login.php"); 
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Registration</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .form-container {
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 20px;
        }
        .form-container h3 {
            margin-bottom: 15px;
        }
        .form-container label {
            display: block;
            margin-bottom: 5px;
        }
        .form-container input {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .form-container button {
            background-color: #007bff;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .form-container button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>

    <div class="form-container">
        <h3>Employee Registration</h3>
        <form method="POST" action="">
            <label for="employeeName">Name:</label>
            <input type="text" id="employeeName" name="employeeName" required>
            <label for="employeeEmail">Email:</label>
            <input type="email" id="employeeEmail" name="employeeEmail" required>
            <label for="employeeAddress">Address:</label>
            <input type="text" id="employeeAddress" name="employeeAddress" required>
            <label for="employeeContact">Contact Number:</label>
            <input type="tel" id="employeeContact" name="employeeContact" required>
            <label for="employeeBirthdate">Birthdate:</label>
            <input type="date" id="employeeBirthdate" name="employeeBirthdate" required>
            <label for="employeePassword">Password:</label>
            <input type="password" id="employeePassword" name="employeePassword" required>
            <button type="submit" name="register_employee">Register</button>
        </form>
    </div>

</body>
</html>

<?php
$conn->close();
?>