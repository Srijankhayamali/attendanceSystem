<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// If username is not set in session, get it from the database
if (!isset($_SESSION['username']) && isset($_SESSION['user_id'])) {
    try {
        $userStmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
        $userStmt->execute([$_SESSION['user_id']]);
        $userData = $userStmt->fetch();
        
        if ($userData && isset($userData['username'])) {
            $_SESSION['username'] = $userData['username'];
        }
    } catch (PDOException $e) {
        error_log("Failed to fetch username: " . $e->getMessage());
    }
}

// Get all students
$stmt = $pdo->query("SELECT * FROM students ORDER BY name");
$students = $stmt->fetchAll();

// Handle attendance submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_attendance'])) {
    $date = $_POST['date'];
    $attendance = $_POST['attendance'];
    // $success = true;
    $user_id = $_SESSION['user_id']; // Get current logged-in user's ID
    $attendanceUpdated = false; // Flag to track if any attendance is updated

    foreach ($attendance as $student_id => $status) {
        // Check if attendance already exists
        $check_stmt = $pdo->prepare("SELECT id, status FROM attendance WHERE student_id = ? AND date = ?");
        $check_stmt->execute([$student_id, $date]);
        $existing_record = $check_stmt->fetch();

        if ($existing_record) {
            $attendance_id = $existing_record['id'];
            $old_status = $existing_record['status'];

            // Only update if status has changed
            if ($old_status !== $status) {
                // Update attendance record
                $stmt = $pdo->prepare("UPDATE attendance SET status = ?, user_id = ? WHERE id = ?");
                $stmt->execute([$status, $user_id, $attendance_id]);

                // Log the change
                $log_stmt = $pdo->prepare("INSERT INTO attendance_log (attendance_id, user_id, old_status, new_status) VALUES (?, ?, ?, ?)");
                $log_stmt->execute([$attendance_id, $user_id, $old_status, $status]);

                $attendanceUpdated = true; // Set the flag to true if an update was made
            }
        } else {
            // Insert new attendance record
            $stmt = $pdo->prepare("INSERT INTO attendance (student_id, date, status, user_id) VALUES (?, ?, ?, ?)");
            $stmt->execute([$student_id, $date, $status, $user_id]);

            // Optionally log creation as well (with NULL old status)
            $attendance_id = $pdo->lastInsertId();
            $log_stmt = $pdo->prepare("INSERT INTO attendance_log (attendance_id, user_id, old_status, new_status) VALUES (?, ?, NULL, ?)");
            $log_stmt->execute([$attendance_id, $user_id, $status]);
        }
    }

    // Set success message based on whether attendance was updated or recorded
    if ($attendanceUpdated) {
        $_SESSION['message'] = "Attendance updated successfully!";
    } else {
        $_SESSION['message'] = "Attendance recorded successfully!";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Management System</title>
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Attendance Management System</h1>
            <div class="user-info">
                <span>Welcome, <?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'User'; ?></span>
                <a href="logout.php" class="btn">Logout</a>
            </div>
        </div>
        
        <?php if (isset($_SESSION['message'])): ?>
            <div class="message success">
                <?php 
                echo $_SESSION['message'];
                unset($_SESSION['message']);
                ?>
            </div>
        <?php endif; ?>
        
        <div class="navigation">
            <a href="dashboard.php" class="nav-btn active">Take Attendance</a>
            <a href="attendance_report.php" class="nav-btn">View Reports</a>
        </div>
        
        <div class="attendance-form">
            <h2>Take Attendance</h2>
            <form method="POST">
                <div style="margin-bottom: 20px;">
                    <label for="date">Date:</label>
                    <input type="date" id="date" name="date" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th>Student Name</th>
                            <th>Roll Number</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['name']); ?></td>
                                <td><?php echo htmlspecialchars($student['roll_number']); ?></td>
                                <td>
                                    <select name="attendance[<?php echo $student['id']; ?>]" required>
                                        <option value="present">Present</option>
                                        <option value="absent">Absent</option>
                                    </select>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <button type="submit" name="submit_attendance" class="btn" style="margin-top: 20px;">Submit Attendance</button>
            </form>
        </div>
    </div>
</body>
</html>
