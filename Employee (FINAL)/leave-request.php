<?php
session_start();
if (!isset($_SESSION['employee_id'])) {
    header("Location: leave-request.php");
    exit();
}

$servername = "localhost:3308"; 
$username = "root"; 
$password = ""; 
$dbname = "admin_dashboard";


$conn = new mysqli('localhost:3308', 'root', '', 'admin_dashboard');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_leave_request'])) {
    $userId = $_SESSION['user_id'];
    $reason = $_POST['leaveReason'];
    $date = date('Y-m-d');

    $stmt = $conn->prepare("INSERT INTO leave_requests (employee_id, leave_date, reason, status) VALUES (?, ?, ?, 'pending')");
    $stmt->bind_param("iss", $userId, $date, $reason);
    $stmt->execute();
    $stmt->close();
    header("Location: user-dashboard.php"); 
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Request</title>
    <link rel="stylesheet" href="styles.css"> 
    <link rel="stylesheet" href="dashboard.css"> 
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
        .form-container input, .form-container textarea {
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
        <h3>Leave Request</h3>
        <form method="POST" action="">
            <label for="leaveDate">Leave Date:</label>
            <input type="date" id="leaveDate" name="leaveDate" required>
            <label for="leaveReason">Reason:</label>
            <textarea id="leaveReason" name="leaveReason" required></textarea>
            <button type="submit" name="submit_leave_request">Submit Request</button>
        </form>
    </div>

    <a href="employee-dashboard.php" class="btn">Back to Dashboard</a>

</body>
</html>

<?php
$conn->close();
?>