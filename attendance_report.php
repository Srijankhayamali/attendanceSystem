<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get filter parameters
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$student_id = isset($_GET['student_id']) ? $_GET['student_id'] : null;
$class = isset($_GET['class']) ? $_GET['class'] : '';

// Fetch attendance records based on filters
$query = "SELECT a.*, s.name as student_name, s.roll_number, s.class, u.username as marked_by
          FROM attendance a 
          JOIN students s ON a.student_id = s.id 
          LEFT JOIN users u ON a.user_id = u.id 
          WHERE 1=1";

$params = [];

if ($date) {
    $query .= " AND a.date = ?";
    $params[] = $date;
}

if ($student_id) {
    $query .= " AND a.student_id = ?";
    $params[] = $student_id;
}

if (!empty($class)) {
    $query .= " AND s.class = ?";
    $params[] = $class;
}

$query .= " ORDER BY a.date DESC, s.name ASC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$attendance_records = $stmt->fetchAll();

// Get all students for the filter dropdown
$students = $pdo->query("SELECT id, name, roll_number FROM students ORDER BY name")->fetchAll();

// Get all distinct classes from students table
$classes = $pdo->query("SELECT DISTINCT class FROM students ORDER BY class")->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Report - Attendance System</title>
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/report.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Attendance Report</h1>
            <div class="user-info">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <a href="<?php echo ($_SESSION['role'] === 'admin') ? 'admin_dashboard.php' : 'dashboard.php'; ?>" class="btn">Back to Dashboard</a>
                <a href="logout.php" class="btn">Logout</a>
            </div>
        </div>

        <div class="filters">
            <form method="GET">
                <div class="form-group">
                    <label for="date">Date:</label>
                    <input type="date" id="date" name="date" value="<?php echo htmlspecialchars($date); ?>">
                </div>

                <div class="form-group">
                    <label for="student_id">Student:</label>
                    <select id="student_id" name="student_id">
                        <option value="">All Students</option>
                        <?php foreach ($students as $student): ?>
                            <option value="<?php echo $student['id']; ?>" 
                                    <?php echo ($student_id == $student['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($student['name'] . ' (' . $student['roll_number'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="class">Class (Semester):</label>
                    <select id="class" name="class">
                        <option value="">All Classes</option>
                        <?php foreach ($classes as $cls): ?>
                            <option value="<?php echo htmlspecialchars($cls); ?>" 
                                    <?php echo ($class == $cls) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cls); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit">Filter</button>
            </form>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Student Name</th>
                    <th>Roll Number</th>
                    <th>Class</th>
                    <th>Status</th>
                    <th>Marked By</th> 
                </tr>
            </thead>

            <tbody>
                <?php if (count($attendance_records) > 0): ?>
                    <?php foreach ($attendance_records as $record): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($record['date']); ?></td>
                            <td><?php echo htmlspecialchars($record['student_name']); ?></td>
                            <td><?php echo htmlspecialchars($record['roll_number']); ?></td>
                            <td><?php echo htmlspecialchars($record['class']); ?></td>
                            <td class="status-<?php echo $record['status']; ?>">
                                <?php echo ucfirst($record['status']); ?>
                            </td>
                            <td><?php echo htmlspecialchars($record['marked_by'] ?? 'Unknown'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center;">No attendance records found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
