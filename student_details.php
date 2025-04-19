<?php
session_start();
require_once 'config.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch username and role if not already stored
if (!isset($_SESSION['username']) && isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT username, role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    if ($user) {
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role']; // Store the role in session
    }
}

// Get the selected class, month, and year
$selectedClass = isset($_GET['class']) ? $_GET['class'] : '';
$selectedMonth = isset($_GET['month']) ? $_GET['month'] : date('m');
$selectedYear = isset($_GET['year']) ? $_GET['year'] : date('Y');

// Modify the query to include a condition for class
$sql = "
    SELECT s.*, 
           COUNT(a.id) AS days_present
    FROM students s
    LEFT JOIN attendance a 
        ON s.id = a.student_id 
        AND a.status = 'present'
        AND MONTH(a.date) = ? 
        AND YEAR(a.date) = ? 
";

// If a class is selected, add the condition to the query
if (!empty($selectedClass)) {
    $sql .= " WHERE s.class = ? ";
} else {
    $sql .= " WHERE 1 ";  // Include this line to fetch all classes when no class is selected
}

$sql .= " GROUP BY s.id
          ORDER BY s.class, s.name";  // Sort by class first, then by name

$stmt = $pdo->prepare($sql);

// Execute the query with the appropriate parameters
if (!empty($selectedClass)) {
    $stmt->execute([$selectedMonth, $selectedYear, $selectedClass]);
} else {
    $stmt->execute([$selectedMonth, $selectedYear]);
}

$students = $stmt->fetchAll();

// Get available classes (semesters)
$classStmt = $pdo->query("SELECT DISTINCT class FROM students ORDER BY class");
$classes = $classStmt->fetchAll();
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
            <a href="dashboard.php" class="nav-btn">Dashboard</a>
            <a href="attendance_report.php" class="nav-btn">View Reports</a>
            <a href="student_details.php" class="nav-btn active">Student Details</a>
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <a href="manage_students.php" class="nav-btn">Manage Students</a>
                <a href="manage_teachers.php" class="nav-btn">Manage Teachers</a>
            <?php endif; ?>
        </div>

        <div class="student-list">
            <h2>Student Attendance Summary</h2>

            <form method="GET" class="filter-form">
                <label for="class">Select Class (Semester):</label>
                <select name="class" id="class">
                    <option value="">All Classes</option>
                    <?php foreach ($classes as $class): ?>
                        <option value="<?php echo htmlspecialchars($class['class']); ?>" <?php echo ($class['class'] == $selectedClass) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($class['class']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

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
                        <th>Class (Semester)</th>
                        <th>Days Present (<?php echo date("F Y", strtotime("$selectedYear-$selectedMonth-01")); ?>)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($student['name']); ?></td>
                            <td><?php echo htmlspecialchars($student['roll_number']); ?></td>
                            <td><?php echo htmlspecialchars($student['email']); ?></td>
                            <td><?php echo htmlspecialchars($student['class']); ?></td>
                            <td><?php echo $student['days_present']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
