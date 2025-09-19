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

$searchDate = isset($_POST['searchDate']) ? $_POST['searchDate'] : '';
$searchEmployee = isset($_POST['searchEmployee']) ? $_POST['searchEmployee'] : '';

$query = "
    SELECT a.date, a.status, e.name 
    FROM attendance a
    JOIN employees e ON a.employee_id = e.id
";
$conditions = [];
$params = [];
$types = "";

if ($searchDate) {
    $conditions[] = "a.date = ?";
    $params[] = $searchDate;
    $types .= "s";
}
if ($searchEmployee) {
    $conditions[] = "(e.name LIKE ? OR e.id = ?)";
    $params[] = "%$searchEmployee%";
    $params[] = $searchEmployee;
    $types .= "si";
}
if ($conditions) {
    $query .= " WHERE " . implode(" AND ", $conditions);
}
$query .= " ORDER BY a.date ASC, e.name ASC"; // Order by date and employee name

$stmt = $conn->prepare($query);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$attendances = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Details</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <a href="admin-dashboard.php" class="btn btn-secondary mb-3">Back to Dashboard</a> <!-- Back button -->
        <h2>Attendance Details</h2>
        <form method="POST" action="attendance-details.php" class="mb-4">
            <div class="form-group">
                <label for="searchEmployee">Search by Name or ID:</label>
                <input type="text" id="searchEmployee" name="searchEmployee" class="form-control" value="<?php echo htmlspecialchars($searchEmployee); ?>" placeholder="Enter Name or ID">
            </div>
            <div class="form-group">
                <label for="searchDate">Search by Date:</label>
                <input type="date" id="searchDate" name="searchDate" class="form-control" value="<?php echo htmlspecialchars($searchDate); ?>">
            </div>
            <button type="submit" class="btn btn-primary">Search</button>
        </form>
        <?php if ($attendances): ?>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Employee Name</th>
                        <th>Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($attendances as $attendance): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($attendance['name']); ?></td>
                            <td><?php echo htmlspecialchars($attendance['date']); ?></td>
                            <td><?php echo htmlspecialchars($attendance['status']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No attendance records found for the given criteria.</p>
        <?php endif; ?>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
