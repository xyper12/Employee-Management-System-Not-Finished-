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


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['approve_leave'])) {
    $leaveId = $_POST['leaveId'];
    $stmt = $conn->prepare("UPDATE leave_requests SET status = 'approved' WHERE id = ?");
    $stmt->bind_param("i", $leaveId);
    $stmt->execute();
    $stmt->close();
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['mark_attendance'])) {
    $employeeId = $_POST['employeeId'];
    $date = date('Y-m-d');
    $stmt = $conn->prepare("INSERT INTO attendance (employee_id, date) VALUES (?, ?)");
    $stmt->bind_param("is", $employeeId, $date);
    $stmt->execute();
    $stmt->close();
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_employee'])) {
    $employeeId = $_POST['employeeId'];
    $stmt = $conn->prepare("DELETE FROM employees WHERE id = ?");
    $stmt->bind_param("i", $employeeId);
    $stmt->execute();
    $stmt->close();
    header("Location: admin-dashboard.php");
    exit();
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_employee'])) {
    $name = $_POST['employeeName'];
    $email = $_POST['employeeEmail'];
    $address = $_POST['employeeAddress'];
    $contact = $_POST['employeeContact'];
    $birthdate = $_POST['employeeBirthdate'];
    $gender = $_POST['employeeGender'];
    $position = $_POST['employeePosition'];
    $salary = $_POST['employeeSalary'];
    $password = password_hash($_POST['employeePassword'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO employees (name, email, address, contact, birthdate, gender, position, salary, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssssis", $name, $email, $address, $contact, $birthdate, $gender, $position, $salary, $password);
    $stmt->execute();
    $stmt->close();
    header("Location: admin-dashboard.php");
    exit();
}


$result = $conn->query("SELECT employee_id, COUNT(*) as missed_days FROM attendance WHERE status = 'missed' GROUP BY employee_id");
while ($row = $result->fetch_assoc()) {
    $employeeId = $row['employee_id'];
    $missedDays = $row['missed_days'];
    $penalty = $missedDays * 0.1; 
    $stmt = $conn->prepare("UPDATE employees SET salary = salary - (salary * ?) WHERE id = ?");
    $stmt->bind_param("di", $penalty, $employeeId);
    $stmt->execute();
    $stmt->close();
}


$leaveRequests = $conn->query("
    SELECT lr.id AS leave_id, lr.employee_id, e.name AS employee_name, lr.leave_date, lr.reason, lr.status
    FROM leave_requests lr
    JOIN employees e ON lr.employee_id = e.id
");

$result = $conn->query("SELECT gender, COUNT(*) as count FROM employees GROUP BY gender");
$genderCounts = ['male' => 0, 'female' => 0, 'other' => 0];
$totalEmployees = 0;
while ($row = $result->fetch_assoc()) {
    $genderCounts[strtolower($row['gender'])] = $row['count'];
    $totalEmployees += $row['count'];
}

$search = isset($_POST['search']) ? $_POST['search'] : '';
$employees = $conn->query("SELECT * FROM employees WHERE name LIKE '%$search%'");

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['approve_leave'])) {
    $leaveId = $_POST['leaveId'];
    $stmt = $conn->prepare("UPDATE leave_requests SET status = 'approved' WHERE id = ?");
    $stmt->bind_param("i", $leaveId);
    $stmt->execute();
    $stmt->close();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['disapprove_leave'])) {
    $leaveId = $_POST['leaveId'];
    $stmt = $conn->prepare("UPDATE leave_requests SET status = 'disapproved' WHERE id = ?");
    $stmt->bind_param("i", $leaveId);
    $stmt->execute();
    $stmt->close();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_leave'])) {
    $leaveId = $_POST['leaveId'];
    $stmt = $conn->prepare("DELETE FROM leave_requests WHERE id = ?");
    $stmt->bind_param("i", $leaveId);
    $stmt->execute();
    $stmt->close();
    header("Location: admin-dashboard.php");
    exit();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            display: flex;
        }
        .sidebar {
            width: 250px;
            background-color: #343a40;
            color: white;
            padding: 15px;
            height: 100vh;
        }
        .sidebar h2 {
            text-align: center;
        }
        .sidebar a {
            color: white;
            text-decoration: none;
            display: block;
            padding: 10px;
            margin: 5px 0;
            border-radius: 4px;
        }
        .sidebar a:hover {
            background-color: #495057;
        }
        .content {
            flex: 1;
            padding: 20px;
        }
        .form-container {
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .form-container h3 {
            margin-bottom: 15px;
        }
        .form-container label {
            display: block;
            margin-bottom: 5px;
        }
        .form-container input, .form-container select {
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
        #genderChart {
            width: 100%;
            height: 400px;
        }

.modal-header {
    background-color: #007bff;
    color: white;
    border-top-left-radius: 10px;
    border-top-right-radius: 10px;
    padding: 15px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header .close {
    color: white;
    font-size: 24px;
    cursor: pointer;
}

.modal-header .close:hover {
    color: #ff5b5b;
}

.modal-body {
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 15px; 
}

.modal-body label {
    font-weight: bold;
    margin-bottom: 5px;
}

.modal-body input,
.modal-body select {
    width: 100%;
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 4px;
    box-sizing: border-box;
}


.modal-body button {
    background-color: #007bff;
    color: white;
    border: none;
    padding: 10px 15px;
    border-radius: 4px;
    cursor: pointer;
    width: 100%;
    transition: background-color 0.3s ease, transform 0.2s;
}

.modal-body button:hover {
    background-color: #0056b3;
    transform: scale(1.02); 
}

.modal-body button:active {
    background-color: #004085;
    transform: scale(1); 
}

input[type="text"], input[type="email"], input[type="password"], input[type="number"], select {
    margin-bottom: 10px; 
}

        
    </style>
</head>
<body>

    <div class="sidebar">
        <h2>Admin Dashboard</h2>
        <a href="#" id="addEmployeeLink" data-toggle="modal" data-target="#addEmployeeModal">Add Employee</a>
        <a href="salary-details.php" data-toggle="modal" data-target="#salaryModal">Salary</a>
        <a href="#" data-toggle="modal" data-target="#attendanceModal">Attendance</a>
        <a href="#" data-toggle="modal" data-target="#leaveRequestModal">Leave Request</a>
        <a href="#">Penalty</a>
        <a href="admin-login.php">Logout</a>
    </div>

    <div class="content">
        <div class="form-container">
            <h3>Employee Gender Analytics (Total: <?php echo $totalEmployees; ?>)</h3>
            <canvas id="genderChart"></canvas>
        </div>

        <h3>Employee List</h3>
        <form method="POST" action="admin-dashboard.php">
            <div class="form-group">
                <label for="search">Search Employees:</label>
                <input type="text" id="search" name="search" class="form-control" value="<?php echo $search; ?>">
            </div>
            <button type="submit" class="btn btn-primary">Search</button>
        </form>
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Gender</th>
                    <th>Salary</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $employees->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['name']; ?></td>
                    <td><?php echo $row['email']; ?></td>
                    <td><?php echo $row['gender']; ?></td>
                    <td><?php echo $row['salary']; ?></td>
                    <td>
                        <button class="btn btn-warning" data-toggle="modal" data-target="#editEmployeeModal" data-id="<?php echo $row['id']; ?>" data-name="<?php echo $row['name']; ?>" data-email="<?php echo $row['email']; ?>" data-gender="<?php echo $row['gender']; ?>" data-salary="<?php echo $row['salary']; ?>">Edit</button>
                        <button class="btn btn-danger" onclick="confirmDelete(<?php echo $row['id']; ?>)">Delete</button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <h3>All Leave Requests</h3>
        <?php if ($leaveRequests->num_rows > 0): ?>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Employee ID</th>
                        <th>Employee Name</th>
                        <th>Leave Date</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $leaveRequests->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['employee_id']; ?></td>
                            <td><?php echo $row['employee_name']; ?></td>
                            <td><?php echo $row['leave_date']; ?></td>
                            <td><?php echo $row['reason']; ?></td>
                            <td><?php echo ucfirst($row['status']); ?></td>
                            <td>
                                <?php if ($row['status'] === 'pending'): ?>
                                    <form method="POST" action="admin-dashboard.php" style="display:inline;">
                                        <input type="hidden" name="leaveId" value="<?php echo $row['leave_id']; ?>">
                                        <button type="submit" name="approve_leave" class="btn btn-success">Approve</button>
                                    </form>
                                    <form method="POST" action="admin-dashboard.php" style="display:inline;">
                                        <input type="hidden" name="leaveId" value="<?php echo $row['leave_id']; ?>">
                                        <button type="submit" name="disapprove_leave" class="btn btn-danger">Disapprove</button>
                                    </form>
                                <?php endif; ?>
                                <form method="POST" action="admin-dashboard.php" style="display:inline;">
                                    <input type="hidden" name="leaveId" value="<?php echo $row['leave_id']; ?>">
                                    <button type="submit" name="delete_leave" class="btn btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No leave requests found.</p>
        <?php endif; ?>

    </div>

   
    <div class="modal fade" id="editEmployeeModal" tabindex="-1" role="dialog" aria-labelledby="editEmployeeModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editEmployeeModalLabel">Edit Employee</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="editEmployeeForm" method="POST" action="edit-employee.php">
                        <input type="hidden" id="editEmployeeId" name="employeeId">
                        <label for="editEmployeeName">Name:</label>
                        <input type="text" id="editEmployeeName" name="employeeName" required>
                        <label for="editEmployeeEmail">Email:</label>
                        <input type="email" id="editEmployeeEmail" name="employeeEmail" required>
                        <label for="editEmployeeGender">Gender:</label>
                        <select id="editEmployeeGender" name="employeeGender" required>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                            
                        </select>
                        <label for="editEmployeeSalary">Salary:</label>
                        <input type="number" id="editEmployeeSalary" name="employeeSalary" required>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

  
    <div class="modal fade" id="addEmployeeModal" tabindex="-1" role="dialog" aria-labelledby="addEmployeeModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addEmployeeModalLabel">Add Employee</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="addEmployeeForm" method="POST" action="admin-dashboard.php">
                        <input type="hidden" name="add_employee" value="1">
                        <label for="employeeName">Name:</label>
                        <input type="text" id="employeeName" name="employeeName" required>
                        
                        <label for="employeeEmail">Email:</label>
                        <input type="email" id="employeeEmail" name="employeeEmail" required>
                        
                        <label for="employeeAddress">Address:</label>
                        <input type="text" id="employeeAddress" name="employeeAddress" required>
                        
                        <label for="employeeContact">Contact:</label>
                        <input type="text" id="employeeContact" name="employeeContact" required>
                        
                        <label for="employeeBirthdate">Birthdate:</label>
                        <input type="date" id="employeeBirthdate" name="employeeBirthdate" required>
                        
                        <label for="employeeGender">Gender:</label>
                        <select id="employeeGender" name="employeeGender" required>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                        
                        <label for="employeePosition">Position:</label>
                        <input type="text" id="employeePosition" name="employeePosition" required>
                        
                        <label for="employeeSalary">Salary:</label>
                        <input type="number" id="employeeSalary" name="employeeSalary" required>
                        
                        <label for="employeePassword">Password:</label>
                        <input type="password" id="employeePassword" name="employeePassword" required>
                        
                        <button type="submit" class="btn btn-primary">Add Employee</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

  
    <div class="modal fade" id="salaryModal" tabindex="-1" role="dialog" aria-labelledby="salaryModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="salaryModalLabel">Salary Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="salaryForm" method="GET" action="salary-details.php">
                        <label for="searchQuery">Search by Employee ID or Name:</label>
                        <input type="text" id="searchQuery" name="searchQuery" class="form-control" placeholder="Enter ID or Name" required>
                        <button type="submit" class="btn btn-primary mt-3">View Salary</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="attendanceModal" tabindex="-1" role="dialog" aria-labelledby="attendanceModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="attendanceModalLabel">Attendance Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="attendanceForm" method="POST" action="attendance-details.php">
                        <label for="employeeId">Employee ID:</label>
                        <input type="number" id="employeeId" name="employeeId" required>
                        <button type="submit" class="btn btn-primary">View Attendance</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="leaveRequestModal" tabindex="-1" role="dialog" aria-labelledby="leaveRequestModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="leaveRequestModalLabel">Leave Request Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="leaveRequestForm" method="POST" action="admin-dashboard.php">
                        <label for="employeeId">Employee ID:</label>
                        <input type="number" id="employeeId" name="employeeId" required>
                        <button type="submit" class="btn btn-primary">View Leave Requests</button>
                    </form>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Employee ID</th>
                                <th>Leave Date</th>
                                <th>Reason</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $leaveRequests->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['employee_id']; ?></td>
                                <td><?php echo $row['leave_date']; ?></td>
                                <td><?php echo $row['reason']; ?></td>
                                <td><?php echo $row['status']; ?></td>
                                <td>
                                    <form method="POST" action="admin-dashboard.php" style="display:inline;">
                                        <input type="hidden" name="leaveId" value="<?php echo $row['id']; ?>">
                                        <button type="submit" name="approve_leave" class="btn btn-success">Approve</button>
                                    </form>
                                    <form method="POST" action="admin-dashboard.php" style="display:inline;">
                                        <input type="hidden" name="leaveId" value="<?php echo $row['id']; ?>">
                                        <button type="submit" name="disapprove_leave" class="btn btn-danger">Disapprove</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        $('#editEmployeeModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var id = button.data('id');
            var name = button.data('name');
            var email = button.data('email');
            var gender = button.data('gender');
            var salary = button.data('salary');

            var modal = $(this);
            modal.find('#editEmployeeId').val(id);
            modal.find('#editEmployeeName').val(name);
            modal.find('#editEmployeeEmail').val(email);
            modal.find('#editEmployeeGender').val(gender);
            modal.find('#editEmployeeSalary').val(salary);
        });

        $(document).ready(function() {
            $('#editEmployeeModal').modal('show');
        });


        const xValues = ["Male", "Female", "Other"];
        const yValues = [<?php echo $genderCounts['male']; ?>, <?php echo $genderCounts['female']; ?>, <?php echo $genderCounts['other']; ?>];
        const barColors = ["blue", "red", "green"];

        new Chart("genderChart", {
            type: "pie", // Change to "pie" for a pie chart
            data: {
                labels: xValues,
                datasets: [{
                    backgroundColor: barColors,
                    data: yValues
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top', // Position the legend at the top
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.raw !== null) {
                                    label += context.raw;
                                }
                                return label;
                            }
                        }
                    }
                }
            }
        });

        function confirmDelete(employeeId) {
            if (confirm("Are you sure you want to delete this employee?")) {
                $.ajax({
                    url: 'admin-dashboard.php',
                    type: 'POST',
                    data: { employeeId: employeeId, delete_employee: true },
                    success: function(response) {
                        alert("Successfully deleted");
                        location.reload();
                    },
                    error: function() {
                        alert("Error deleting employee");
                    }
                });
            }
        }
    </script>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>