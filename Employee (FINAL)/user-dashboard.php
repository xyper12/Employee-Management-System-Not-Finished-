<?php
session_start();
$servername = "localhost:3308"; 
$username = "root"; 
$password = ""; 
$dbname = "admin_dashboard";

// Set the timezone to the Philippines
date_default_timezone_set('Asia/Manila');

$conn = new mysqli('localhost:3308', 'root', '', 'admin_dashboard');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$id = $_SESSION['user_id'];

$message = ''; // Variable to store feedback messages

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['mark_attendance'])) {
    $date = date('Y-m-d');

    $currentTime = date('H:i:s');
    $status = (strtotime($currentTime) > strtotime('08:00:00')) ? 'Late' : 'Present';
    $stmt = $conn->prepare("INSERT INTO attendance (employee_id, date, status, time_in) VALUES (?, ?, ?, ?)
                            ON DUPLICATE KEY UPDATE status = ?, time_in = ?");
    $stmt->bind_param("isssss", $id, $date, $status, $currentTime, $status, $currentTime);
    if ($stmt->execute()) {
        $message = ($status === 'Late') ? "Attendance marked as Late." : "Attendance marked successfully.";
    } else {
        $message = "Failed to mark attendance.";
    }
    $stmt->close();
}

// Mark employees as "Absent" if no time_in and time_out are recorded for the day
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['mark_absent'])) {
    $date = date('Y-m-d');
    $stmt = $conn->prepare("UPDATE attendance SET status = 'Absent' WHERE date = ? AND time_in IS NULL AND time_out IS NULL");
    $stmt->bind_param("s", $date);
    if ($stmt->execute()) {
        $message = "Employees with no attendance records have been marked as Absent.";
    } else {
        $message = "Failed to mark absentees.";
    }
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['request_leave'])) {
    $leaveReason = $_POST['leaveReason'];
    $date = date('Y-m-d');
    $status = 'Pending';
    $stmt = $conn->prepare("INSERT INTO leave_requests (employee_id, leave_date, reason, status) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $id, $date, $leaveReason, $status);
    if ($stmt->execute()) {
        $message = "Leave request submitted successfully.";
    } else {
        $message = "Failed to submit leave request.";
    }
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['mark_time_in'])) {
    $date = date('Y-m-d');
    $time_in = date('H:i:s');

    // Check if time_in is already recorded for the day
    $stmt = $conn->prepare("SELECT time_in FROM attendance WHERE employee_id = ? AND date = ?");
    $stmt->bind_param("is", $id, $date);
    $stmt->execute();
    $stmt->bind_result($existingTimeIn);
    $stmt->fetch();
    $stmt->close();

    if ($existingTimeIn) {
        $message = "Time In has already been recorded for today.";
    } else {
        $status = (strtotime($time_in) >= strtotime('07:00:00') && strtotime($time_in) <= strtotime('08:00:00')) ? 'On Time' : 'Late';
        $stmt = $conn->prepare("INSERT INTO attendance (employee_id, date, status, time_in) VALUES (?, ?, ?, ?)
                                ON DUPLICATE KEY UPDATE time_in = ?, status = ?");
        $stmt->bind_param("isssss", $id, $date, $status, $time_in, $time_in, $status);
        if ($stmt->execute()) {
            $message = "Time In marked successfully as $status.";
        } else {
            $message = "Failed to mark Time In.";
        }
        $stmt->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['mark_time_out'])) {
    $date = date('Y-m-d');
    $time_out = date('H:i:s');

    // Check if time_in exists for the day
    $stmt = $conn->prepare("SELECT time_in FROM attendance WHERE employee_id = ? AND date = ?");
    $stmt->bind_param("is", $id, $date);
    $stmt->execute();
    $stmt->bind_result($existingTimeIn);
    $stmt->fetch();
    $stmt->close();

    if (!$existingTimeIn) {
        $message = "Cannot mark Time Out without a Time In.";
    } else {
        if (strtotime($time_out) < strtotime('16:00:00')) {
            $status = 'Undertime';
        } elseif (strtotime($time_out) >= strtotime('16:00:00') && strtotime($time_out) <= strtotime('17:00:00')) {
            $status = 'On Time';
        } else {
            $status = 'Overtime';
        }

        $stmt = $conn->prepare("UPDATE attendance SET time_out = ?, status = ? WHERE employee_id = ? AND date = ?");
        $stmt->bind_param("ssis", $time_out, $status, $id, $date);
        if ($stmt->execute()) {
            $message = "Time Out marked successfully as $status.";
        } else {
            $message = "Failed to mark Time Out.";
        }
        $stmt->close();
    }
}

$stmt = $conn->prepare("SELECT name, salary FROM employees WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($name, $salary);
$stmt->fetch();
$stmt->close();


$attendance = $conn->query("SELECT date, status, time_in, time_out FROM attendance WHERE employee_id = $id");


$leaveRequests = $conn->query("SELECT * FROM leave_requests WHERE employee_id = $id");


$genderDistribution = $conn->query("SELECT gender, COUNT(*) as count FROM employees GROUP BY gender");


// Calculate total working days and hours based on attendance records
$totalDaysResult = $conn->query("SELECT COUNT(*) as total_days FROM attendance WHERE employee_id = $id AND time_in IS NOT NULL");
$totalDays = $totalDaysResult->fetch_assoc()['total_days'] ?? 0;

$totalHoursResult = $conn->query("SELECT SUM(TIMESTAMPDIFF(HOUR, time_in, time_out)) as total_hours 
                                  FROM attendance WHERE employee_id = $id AND time_in IS NOT NULL AND time_out IS NOT NULL");
$totalHours = $totalHoursResult->fetch_assoc()['total_hours'] ?? 0;

// Calculate missed hours and salary deductions
$totalWorkingDaysInMonth = 30; 
$hourlyRate = $salary / (30 * 8);
$absentDaysResult = $conn->query("SELECT COUNT(*) as absent_days FROM attendance WHERE employee_id = $id AND status = 'Absent'");
$absentDays = $absentDaysResult->fetch_assoc()['absent_days'] ?? 0;

// Missed hours are based on absent days only
$missedHours = $absentDays * 8; // Assuming 8 working hours per day for each absent day

$salaryDeductions = $absentDays * ($salary / $totalWorkingDaysInMonth);

// Calculate remaining salary after deductions
$remainingSalary = max(0, $salary - $salaryDeductions);

// Automatically determine absences for days with no attendance records
$dateToday = date('Y-m-d');
$absentDaysQuery = $conn->query("
    SELECT DISTINCT date 
    FROM (
        SELECT CURDATE() - INTERVAL seq DAY AS date
        FROM (SELECT 0 AS seq UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6) AS days
    ) AS all_dates
    WHERE date <= '$dateToday'
    AND date NOT IN (SELECT date FROM attendance WHERE employee_id = $id)
    ORDER BY date DESC
");

$absentDays = [];
while ($row = $absentDaysQuery->fetch_assoc()) {
    $absentDays[] = $row['date'];
}

$conn->close(); 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="styles.css"> 
    <link rel="stylesheet" href="dashboard.css"> 
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        body {
            display: flex;
        }
        .sidebar {
            width: 200px;
            background-color: grey;
            padding: 15px;
            height: 100vh;
        }
        .content {
            flex-grow: 1;
            padding: 20px;
        }
        .status-on-time {
            color: green;
            font-weight: bold;
        }
        .status-late {
            color: red;
            font-weight: bold;
        }
        .status-undertime {
            color: orange;
            font-weight: bold;
        }
        .status-approved {
            color: green;
            font-weight: bold;
        }
        .status-pending {
            color: orange;
            font-weight: bold;
        }
        .status-denied {
            color: red;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h3>Welcome Employee</h3>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link" href="user-dashboard.php">Dashboard</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#" data-toggle="modal" data-target="#attendanceModal">Mark Attendance</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#" data-toggle="modal" data-target="#leaveRequestModal">Request Leave</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="logout.php">Logout</a>
            </li>
        </ul>
    </div>
    <div class="content">
        <!-- Display session message -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-success">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Attendance Modal -->
        <div class="modal fade" id="attendanceModal" tabindex="-1" role="dialog" aria-labelledby="attendanceModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <form method="POST">
                        <div class="modal-header">
                            <h5 class="modal-title" id="attendanceModalLabel">Mark Attendance</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <p>Use the buttons below to mark your attendance for today:</p>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" name="mark_time_in" class="btn btn-success">Mark Time In</button>
                            <button type="submit" name="mark_time_out" class="btn btn-primary">Mark Time Out</button>
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Leave Request Modal -->
        <div class="modal fade" id="leaveRequestModal" tabindex="-1" role="dialog" aria-labelledby="leaveRequestModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <form method="POST">
                        <div class="modal-header">
                            <h5 class="modal-title" id="leaveRequestModalLabel">Request Leave</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label for="leaveReason">Reason for Leave</label>
                                <textarea name="leaveReason" id="leaveReason" class="form-control" required></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            <button type="submit" name="request_leave" class="btn btn-primary">Submit Request</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <h1>Welcome, <?php echo $name; ?></h1>
        <h2>Original Salary: $<?php echo $salary; ?></h2>
        <h2>Remaining Salary: $<?php echo $remainingSalary; ?></h2>
        <h3>Total Working Days: <?php echo $totalDays; ?></h3>
        <h3>Total Working Hours: <?php echo $totalHours; ?></h3>
        <h3>Missed Hours: <?php echo $missedHours; ?></h3>
        <h3>Salary Deductions: $<?php echo $salaryDeductions; ?></h3>
        <h3>Attendance</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Time In</th>
                    <th>Time In Status</th>
                    <th>Time Out</th>
                    <th>Time Out Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $attendance->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['date']; ?></td>
                    <td><?php echo $row['time_in'] ? $row['time_in'] : 'N/A'; ?></td>
                    <td>
                        <?php
                        if ($row['time_in']) {
                            $timeInStatus = (strtotime($row['time_in']) >= strtotime('07:00:00') && strtotime($row['time_in']) <= strtotime('08:00:00')) ? 'On Time' : 'Late';
                            $timeInClass = ($timeInStatus === 'On Time') ? 'status-on-time' : 'status-late';
                            echo "<span class='$timeInClass'>$timeInStatus</span>";
                        } else {
                            echo 'N/A';
                        }
                        ?>
                    </td>
                    <td><?php echo $row['time_out'] ? $row['time_out'] : 'N/A'; ?></td>
                    <td>
                        <?php
                        if ($row['time_out']) {
                            if (strtotime($row['time_out']) < strtotime('16:00:00')) {
                                echo "<span class='status-undertime'>Undertime</span>";
                            } elseif (strtotime($row['time_out']) >= strtotime('16:00:00') && strtotime($row['time_out']) <= strtotime('17:00:00')) {
                                echo "<span class='status-on-time'>On Time</span>";
                            } else {
                                echo "<span class='status-on-time'>Overtime</span>";
                            }
                        } else {
                            echo 'N/A';
                        }
                        ?>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php foreach ($absentDays as $absentDate): ?>
                <tr>
                    <td><?php echo $absentDate; ?></td>
                    <td>N/A</td>
                    <td><span class="status-late">Absent</span></td>
                    <td>N/A</td>
                    <td><span class="status-late">Absent</span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <h3>Leave Requests</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Reason</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $leaveRequests->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['leave_date']; ?></td>
                    <td><?php echo $row['reason']; ?></td>
                    <td>
                        <?php
                        $statusClass = '';
                        if ($row['status'] === 'Approved') {
                            $statusClass = 'status-approved';
                        } elseif ($row['status'] === 'Pending') {
                            $statusClass = 'status-pending';
                        } elseif ($row['status'] === 'Denied') {
                            $statusClass = 'status-denied';
                        }
                        echo "<span class='$statusClass'>{$row['status']}</span>";
                        ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>