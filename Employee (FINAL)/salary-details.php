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

$searchQuery = isset($_GET['searchQuery']) ? $_GET['searchQuery'] : '';

if ($searchQuery) {
    $sql = "
        SELECT 
            e.id, 
            e.name, 
            e.salary, 
            COUNT(a.id) AS total_working_days,
            SUM(CASE WHEN a.status = 'Absent' THEN 1 ELSE 0 END) AS total_absences,
            SUM(CASE WHEN a.status = 'Absent' THEN e.salary / 30 ELSE 0 END) AS total_salary_deductions
        FROM employees e
        LEFT JOIN attendance a ON e.id = a.employee_id
        WHERE e.id = ? OR e.name LIKE ?
        GROUP BY e.id, e.name, e.salary
    ";
    $stmt = $conn->prepare($sql);
    $searchTerm = "%" . $searchQuery . "%";
    $stmt->bind_param("is", $searchQuery, $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    $salaries = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $sql = "
        SELECT 
            e.id, 
            e.name, 
            e.salary, 
            COUNT(a.id) AS total_working_days,
            SUM(CASE WHEN a.status = 'Absent' THEN 1 ELSE 0 END) AS total_absences,
            SUM(CASE WHEN a.status = 'Absent' THEN e.salary / 30 ELSE 0 END) AS total_salary_deductions
        FROM employees e
        LEFT JOIN attendance a ON e.id = a.employee_id
        GROUP BY e.id, e.name, e.salary
    ";
    $result = $conn->query($sql);
    $salaries = $result->num_rows > 0 ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salary Details</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <a href="admin-dashboard.php">Back</a>
    <div class="container mt-5">
        <h2>Salary Details</h2>
        <form method="GET" action="salary-details.php" class="mb-4">
            <div class="form-group">
                <label for="searchQuery">Search by Employee ID or Name:</label>
                <input type="text" id="searchQuery" name="searchQuery" class="form-control" placeholder="Enter ID or Name" value="<?php echo htmlspecialchars($searchQuery); ?>">
            </div>
            <button type="submit" class="btn btn-primary">Search</button>
            <button type="button" class="btn btn-secondary" onclick="window.location.href='salary-details.php';">Show All</button>
        </form>
        <?php if ($salaries): ?>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Employee ID</th>
                        <th>Employee Name</th>
                        <th>Salary Amount</th>
                        <th>Total Working Days</th>
                        <th>Total Absences</th>
                        <th>Total Salary Deductions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($salaries as $salary): ?>
                        <tr>
                            <td><?php echo $salary['id']; ?></td>
                            <td><?php echo $salary['name']; ?></td>
                            <td><?php echo $salary['salary']; ?></td>
                            <td><?php echo $salary['total_working_days']; ?></td>
                            <td><?php echo $salary['total_absences']; ?></td>
                            <td><?php echo number_format($salary['total_salary_deductions'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No salary records found for the given search criteria.</p>
        <?php endif; ?>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
