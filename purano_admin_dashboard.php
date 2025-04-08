<?php
session_start();
require_once 'config.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Handle teacher management
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_teacher'])) {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $email = $_POST['email'];
    
    $stmt = $pdo->prepare("INSERT INTO users (username, password, role, email) VALUES (?, ?, 'teacher', ?)");
    $stmt->execute([$username, $password, $email]);
}

// Handle student management
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_student'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $roll_number = $_POST['roll_number'];
    
    $stmt = $pdo->prepare("INSERT INTO students (name, email, roll_number) VALUES (?, ?, ?)");
    $stmt->execute([$name, $email, $roll_number]);
}

// Get all teachers
$teachers = $pdo->query("SELECT * FROM users WHERE role = 'teacher'")->fetchAll();

// Get all students
$students = $pdo->query("SELECT * FROM students")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Attendance System</title>
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <div class="container">
        <div class="nav">
            <a href="dashboard.php">Attendance</a>
            <!-- <a href="admin_dashboard.php">Admin Panel</a> -->
            <a href="logout.php">Logout</a>
        </div>
        
        <h1>Admin Dashboard</h1>
        
        <!-- Teacher Management Section
        <div class="section">
            <h2>Teacher Management</h2>
            <form method="POST">
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <button type="submit" name="add_teacher">Add Teacher</button>
            </form> -->
            
            <!-- <h3>Existing Teachers</h3>
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php //foreach ($teachers as $teacher): ?>
                    <tr>
                        <td><?php //echo htmlspecialchars($teacher['username']); ?></td>
                        <td><?php// echo htmlspecialchars($teacher['email']); ?></td>
                        <td>
                            <a href="edit_teacher.php?id=<?php //echo $teacher['id']; ?>">Edit</a> |
                            <a href="delete_teacher.php?id=<?php //echo $teacher['id']; ?>" onclick="return confirm('Are you sure?')">Delete</a>
                        </td>
                    </tr>
                    <?php //endforeach; ?>
                </tbody>
            </table>
        </div> -->
        
        <!-- Student Management Section -->
        <!-- <div class="section">
            <h2>Student Management</h2>
            <form method="POST">
                <div class="form-group">
                    <label for="name">Name:</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="roll_number">Roll Number:</label>
                    <input type="text" id="roll_number" name="roll_number" required>
                </div>
                <button type="submit" name="add_student">Add Student</button>
            </form> -->
            
            <!-- <h3>Existing Students</h3>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Roll Number</th>
                        <th>Email</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php //foreach ($students as $student): ?>
                    <tr>
                        <td><?php// echo htmlspecialchars($student['name']); ?></td>
                        <td><?php// echo htmlspecialchars($student['roll_number']); ?></td>
                        <td><?php //echo htmlspecialchars($student['email']); ?></td>
                        <td>
                            <a href="edit_student.php?id=<?php //echo $student['id']; ?>">Edit</a> |
                            <a href="delete_student.php?id=<?php //echo $student['id']; ?>" onclick="return confirm('Are you sure?')">Delete</a>
                        </td>
                    </tr>
                    <?php// endforeach; ?>
                </tbody>
            </table>
        </div> -->
    </div>
</body>
</html> 