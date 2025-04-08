<?php
session_start();
require_once 'config.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch username if not already stored
if (!isset($_SESSION['username']) && isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    if ($user) {
        $_SESSION['username'] = $user['username'];
    }
}

// Default to current month and year if not selected
$selectedMonth = isset($_GET['month']) ? $_GET['month'] : date('m');
$selectedYear = isset($_GET['year']) ? $_GET['year'] : date('Y');

// Fetch students and count present days in the selected month
$stmt = $pdo->prepare("
    SELECT s.*, 
           COUNT(a.id) AS days_present
    FROM students s
    LEFT JOIN attendance a 
        ON s.id = a.student_id 
        AND a.status = 'present'
        AND MONTH(a.date) = ? 
        AND YEAR(a.date) = ?
    GROUP BY s.id
    ORDER BY s.name
");
$stmt->execute([$selectedMonth, $selectedYear]);
$students = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Details - Attendance</title>
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/student_details.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Student Details</h1>
            <div class="user-info">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></span>
                <a href="logout.php" class="btn">Logout</a>
            </div>
        </div>

        <div class="navigation">
            <a href="dashboard.php" class="nav-btn">Take Attendance</a>
            <a href="attendance_report.php" class="nav-btn">View Reports</a>
            <a href="student_details.php" class="nav-btn active">Student Details</a>
        </div>

        <div class="student-list">
            <h2>Student Attendance Summary</h2>

            <form method="GET" class="filter-form">
                <label for="month">Select Month:</label>
                <select name="month" id="month">
                    <?php
                    for ($m = 1; $m <= 12; $m++) {
                        $monthVal = str_pad($m, 2, '0', STR_PAD_LEFT);
                        $selected = ($monthVal == $selectedMonth) ? 'selected' : '';
                        echo "<option value='$monthVal' $selected>" . date("F", mktime(0, 0, 0, $m, 10)) . "</option>";
                    }
                    ?>
                </select>

                <label for="year">Year:</label>
                <select name="year" id="year">
                    <?php
                    $currentYear = date('Y');
                    for ($y = $currentYear; $y >= $currentYear - 5; $y--) {
                        $selected = ($y == $selectedYear) ? 'selected' : '';
                        echo "<option value='$y' $selected>$y</option>";
                    }
                    ?>
                </select>

                <button type="submit" class="btn">Filter</button>
            </form>

            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Roll Number</th>
                        <th>Email</th>
                        <th>Days Present (<?php echo date("F Y", strtotime("$selectedYear-$selectedMonth-01")); ?>)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($student['name']); ?></td>
                            <td><?php echo htmlspecialchars($student['roll_number']); ?></td>
                            <td><?php echo htmlspecialchars($student['email']); ?></td>
                            <td><?php echo $student['days_present']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
