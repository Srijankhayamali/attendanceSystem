<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_student'])) {
        $name = $_POST['name'];
        $roll_number = $_POST['roll_number'];
        $email = $_POST['email'];
        
        try {
            $stmt = $pdo->prepare("INSERT INTO students (name, roll_number, email) VALUES (?, ?, ?)");
            $stmt->execute([$name, $roll_number, $email]);
            $_SESSION['message'] = "Student added successfully!";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error adding student: " . $e->getMessage();
        }
    } elseif (isset($_POST['update_student'])) {
        $id = $_POST['student_id'];
        $name = $_POST['name'];
        $roll_number = $_POST['roll_number'];
        $email = $_POST['email'];
        
        try {
            $stmt = $pdo->prepare("UPDATE students SET name = ?, roll_number = ?, email = ? WHERE id = ?");
            $stmt->execute([$name, $roll_number, $email, $id]);
            $_SESSION['message'] = "Student updated successfully!";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error updating student: " . $e->getMessage();
        }
    } elseif (isset($_POST['delete_student'])) {
        $id = $_POST['student_id'];
        
        try {
            $stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['message'] = "Student deleted successfully!";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error deleting student: " . $e->getMessage();
        }
    }
    
    header("Location: manage_students.php");
    exit();
}

// Get all students
$stmt = $pdo->query("SELECT * FROM students ORDER BY name");
$students = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students - Attendance System</title>
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/modal.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Manage Students</h1>
            <div class="user-info">
                <span>Welcome, <?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'User'; ?></span>
                <!-- <a href="dashboard.php" class="btn">Back to Dashboard</a> -->
                <a href="<?php echo ($_SESSION['role'] === 'admin') ? 'admin_dashboard.php' : 'teacher_dashboard.php'; ?>" class="btn">Back to Dashboard</a>

                <a href="logout.php" class="btn">Logout</a>
            </div>
        </div>
        <!-- //navigation test -->
        <div class="navigation">
            <a href="dashboard.php" class="nav-btn active">Take Attendance</a>
            <a href="attendance_report.php" class="nav-btn">View Reports</a>
            <!-- <a href="manage_students.php" class="nav-btn">Manage Students</a> -->
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
                <a href="manage_teachers.php" class="nav-btn">Manage Teachers</a>
            <?php endif; ?>
        </div>
        <?php if (isset($_SESSION['message'])): ?>
            <div class="message success">
                <?php 
                echo $_SESSION['message'];
                unset($_SESSION['message']);
                ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="message error">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>
        
        <button onclick="document.getElementById('addStudentModal').style.display='block'" class="btn">Add New Student</button>
        
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
                <?php foreach ($students as $student): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($student['name']); ?></td>
                        <td><?php echo htmlspecialchars($student['roll_number']); ?></td>
                        <td><?php echo htmlspecialchars($student['email']); ?></td>
                        <td>
                            <button onclick="editStudent(<?php echo htmlspecialchars(json_encode($student)); ?>)" class="btn">Edit</button>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                                <button type="submit" name="delete_student" class="btn" onclick="return confirm('Are you sure you want to delete this student?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Add Student Modal -->
    <div id="addStudentModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="document.getElementById('addStudentModal').style.display='none'">&times;</span>
            <h2>Add New Student</h2>
            <form method="POST">
                <div class="form-group">
                    <label for="name">Name:</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="roll_number">Roll Number:</label>
                    <input type="text" id="roll_number" name="roll_number" required>
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <button type="submit" name="add_student" class="btn">Add Student</button>
            </form>
        </div>
    </div>
    
    <!-- Edit Student Modal -->
    <div id="editStudentModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="document.getElementById('editStudentModal').style.display='none'">&times;</span>
            <h2>Edit Student</h2>
            <form method="POST">
                <input type="hidden" name="student_id" id="edit_student_id">
                <div class="form-group">
                    <label for="edit_name">Name:</label>
                    <input type="text" id="edit_name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="edit_roll_number">Roll Number:</label>
                    <input type="text" id="edit_roll_number" name="roll_number" required>
                </div>
                <div class="form-group">
                    <label for="edit_email">Email:</label>
                    <input type="email" id="edit_email" name="email" required>
                </div>
                <button type="submit" name="update_student" class="btn">Update Student</button>
            </form>
        </div>
    </div>
    
    <script>
        function editStudent(student) {
            document.getElementById('edit_student_id').value = student.id;
            document.getElementById('edit_name').value = student.name;
            document.getElementById('edit_roll_number').value = student.roll_number;
            document.getElementById('edit_email').value = student.email;
            document.getElementById('editStudentModal').style.display = 'block';
        }
        
        // Close modals when clicking outside
        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html> 