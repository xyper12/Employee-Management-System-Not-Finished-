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

$userId = $_SESSION['user_id'];
$date = date('Y-m-d');


$stmt = $conn->prepare("SELECT * FROM attendance WHERE employee_id = ? AND date = ?");
$stmt->bind_param("is", $userId, $date);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $_SESSION['message'] = "Attendance already marked for today.";
} else {
    $stmt = $conn->prepare("INSERT INTO attendance (employee_id, date, status) VALUES (?, ?, 'Present')");
    $stmt->bind_param("is", $userId, $date);
    $stmt->execute();
    $_SESSION['message'] = "Attendance marked successfully.";
}

$stmt->close();
$conn->close();

header("Location: attendance.php");
exit();
?>

<table class="table">
    <thead>
        <tr>
            <th>Date</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = $attendance->fetch_assoc()): ?>
        <tr>
            <td><?php echo $row['date']; ?></td>
            <td><?php echo $row['status']; ?></td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>