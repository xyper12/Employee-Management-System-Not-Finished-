<?php
session_start();
$servername = "localhost:3308"; 
$username = "root"; 
$password = ""; 
$dbname = "admin_dashboard";

$conn = new mysqli('localhost:3308', 'root', '', 'admin_dashboard');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['employeeId'])) {
    $employeeId = $_POST['employeeId'];
    $stmt = $conn->prepare("DELETE FROM employees WHERE id = ?");
    $stmt->bind_param("i", $employeeId);
    $stmt->execute();
    $stmt->close();
}

$conn->close();
header("Location: admin-dashboard.php");
exit();
?>
