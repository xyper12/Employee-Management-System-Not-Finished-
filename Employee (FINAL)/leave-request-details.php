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

$employeeId = isset($_POST['employeeId']) ? $_POST['employeeId'] : '';

if ($employeeId) {
    $stmt = $conn->prepare("SELECT * FROM leave_requests WHERE employee_id = ?");
    $stmt->bind_param("i", $employeeId);
    $stmt->execute();
    $result = $stmt->get_result();
    $leaveRequests = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $leaveRequests = [];
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Request Details</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <h2>Leave Request Details</h2>
        <form method="POST" action="leave-request-details.php" class="mb-4">
            <div class="form-group">
                <label for="employeeId">Employee ID:</label>
                <input type="number" id="employeeId" name="employeeId" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">View Leave Requests</button>
        </form>
        <?php if ($leaveRequests): ?>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Reason</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($leaveRequests as $leaveRequest): ?>
                        <tr>
                            <td><?php echo $leaveRequest['start_date']; ?></td>
                            <td><?php echo $leaveRequest['end_date']; ?></td>
                            <td><?php echo $leaveRequest['reason']; ?></td>
                            <td><?php echo $leaveRequest['status']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No leave request records found for the given Employee ID.</p>
        <?php endif; ?>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
