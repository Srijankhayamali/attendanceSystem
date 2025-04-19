<?php
session_start();
require_once 'config.php';

// Check admin login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Get username if not in session
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

// Get selected semester from dropdown
$selected_class = $_GET['class'] ?? '1st Semester';

// Fetch students based on semester
$stmt = $pdo->prepare("SELECT * FROM students WHERE class = ? ORDER BY name");
$stmt->execute([$selected_class]);
$students = $stmt->fetchAll();

// Handle attendance submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_attendance'])) {
    $date = $_POST['date'];
    $attendance = $_POST['attendance'];
    $attendanceUpdated = false;
    $user_id = $_SESSION['user_id'];

    foreach ($attendance as $student_id => $status) {
        $check_stmt = $pdo->prepare("SELECT id, status FROM attendance WHERE student_id = ? AND date = ?");
        $check_stmt->execute([$student_id, $date]);
        $existing_record = $check_stmt->fetch();

        if ($existing_record) {
            $attendance_id = $existing_record['id'];
            $old_status = $existing_record['status'];
            if ($old_status !== $status) {
                $stmt = $pdo->prepare("UPDATE attendance SET status = ?, user_id = ? WHERE id = ?");
                $stmt->execute([$status, $user_id, $attendance_id]);

                $log_stmt = $pdo->prepare("INSERT INTO attendance_log (attendance_id, user_id, old_status, new_status) VALUES (?, ?, ?, ?)");
                $log_stmt->execute([$attendance_id, $user_id, $old_status, $status]);
                $attendanceUpdated = true;
            }
        } else {
            $stmt = $pdo->prepare("INSERT INTO attendance (student_id, date, status, user_id) VALUES (?, ?, ?, ?)");
            $stmt->execute([$student_id, $date, $status, $user_id]);

            $attendance_id = $pdo->lastInsertId();
            $log_stmt = $pdo->prepare("INSERT INTO attendance_log (attendance_id, user_id, old_status, new_status) VALUES (?, ?, NULL, ?)");
            $log_stmt->execute([$attendance_id, $user_id, $status]);
        }
    }

    $_SESSION['message'] = $attendanceUpdated ? "Attendance updated successfully!" : "Attendance recorded successfully!";
    header("Location: admin_dashboard.php?class=" . urlencode($selected_class));
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Attendance</title>
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Admin Dashboard</h1>
        <div class="user-info">
            <span>Welcome, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></span>
            <a href="logout.php" class="btn">Logout</a>
        </div>
    </div>

    <?php if (isset($_SESSION['message'])): ?>
        <div class="message success">
            <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
        </div>
    <?php endif; ?>

    <div class="navigation">
        <a href="attendance_report.php" class="nav-btn">View Reports</a>
        <a href="student_details.php" class="nav-btn">Student Details</a>
        <a href="manage_students.php" class="nav-btn">Manage Students</a>
        <a href="manage_teachers.php" class="nav-btn">Manage Teachers</a>
    </div>

    <div class="attendance-form">
        <h2>Take Attendance - <?php echo htmlspecialchars($selected_class); ?></h2>

        <!-- Semester Filter Dropdown -->
        <form method="GET" style="margin-bottom: 20px;">
            <label for="class">Select Semester:</label>
            <select name="class" id="class" onchange="this.form.submit()">
                <?php
                $semesters = [
                    "1st Semester", "2nd Semester", "3rd Semester", "4th Semester",
                    "5th Semester", "6th Semester", "7th Semester", "8th Semester"
                ];
                foreach ($semesters as $sem) {
                    $selected = $sem === $selected_class ? "selected" : "";
                    echo "<option value=\"$sem\" $selected>$sem</option>";
                }
                ?>
            </select>
        </form>

        <form method="POST">
            <label for="date">Date:</label>
            <input type="date" id="date" name="date" value="<?php echo date('Y-m-d'); ?>" required>

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
