<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();  
}

// Only admin can access
if ($_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_student'])) {
        $name = $_POST['name'];
        $roll_number = $_POST['roll_number'];
        $email = $_POST['email'];
        $class = $_POST['class'];

        try {
            $stmt = $pdo->prepare("INSERT INTO students (name, roll_number, email, class) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $roll_number, $email, $class]);
            $_SESSION['message'] = "Student added successfully!";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error adding student: " . $e->getMessage();
        }
    } elseif (isset($_POST['update_student'])) {
        $id = $_POST['student_id'];
        $name = $_POST['name'];
        $roll_number = $_POST['roll_number'];
        $email = $_POST['email'];
        $class = $_POST['class'];

        try {
            $stmt = $pdo->prepare("UPDATE students SET name = ?, roll_number = ?, email = ?, class = ? WHERE id = ?");
            $stmt->execute([$name, $roll_number, $email, $class, $id]);
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

// Fetch students
$stmt = $pdo->query("SELECT * FROM students ORDER BY name");
$students = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Students</title>
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/modal.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Manage Students</h1>
            <div class="user-info">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <a href="admin_dashboard.php" class="btn">Back to Dashboard</a>
                <a href="logout.php" class="btn">Logout</a>
            </div>
        </div>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="message success"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="message error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <button onclick="document.getElementById('addStudentModal').style.display='block'" class="btn">Add New Student</button>

        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Roll Number</th>
                    <th>Email</th>
                    <th>Class</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $student): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($student['name']); ?></td>
                        <td><?php echo htmlspecialchars($student['roll_number']); ?></td>
                        <td><?php echo htmlspecialchars($student['email']); ?></td>
                        <td><?php echo htmlspecialchars($student['class']); ?></td>
                        <td>
                            <button onclick="editStudent(<?php echo htmlspecialchars(json_encode($student)); ?>)" class="btn">Edit</button>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                                <button type="submit" name="delete_student" class="btn" onclick="return confirm('Delete this student?')">Delete</button>
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
            <h2>Add Student</h2>
            <form method="POST" onsubmit="return validateAddForm();">
                <div id="add-error" class="error" style="display:none;"></div>
                <input type="text" name="name" id="add_name" placeholder="Name" required>
                <input type="text" name="roll_number" id="add_roll" placeholder="Roll Number" required>
                <input type="email" name="email" id="add_email" placeholder="Email" required>
                <select name="class" required>
                    <option value="">Select Class</option>
                    <?php for ($i = 1; $i <= 8; $i++): ?>
                        <option value="<?= $i ?>th Semester"><?= $i ?>th Semester</option>
                    <?php endfor; ?>
                </select>
                <button type="submit" name="add_student" class="btn">Add Student</button>
            </form>
        </div>
    </div>

    <!-- Edit Student Modal -->
    <div id="editStudentModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="document.getElementById('editStudentModal').style.display='none'">&times;</span>
            <h2>Edit Student</h2>
            <form method="POST" onsubmit="return validateEditForm();">
                <div id="edit-error" class="error" style="display:none;"></div>
                <input type="hidden" name="student_id" id="edit_id">
                <input type="text" name="name" id="edit_name" required>
                <input type="text" name="roll_number" id="edit_roll" required>
                <input type="email" name="email" id="edit_email" required>
                <select name="class" id="edit_class" required>
                    <?php for ($i = 1; $i <= 8; $i++): ?>
                        <option value="<?= $i ?>th Semester"><?= $i ?>th Semester</option>
                    <?php endfor; ?>
                </select>
                <button type="submit" name="update_student" class="btn">Update Student</button>
            </form>
        </div>
    </div>

    <script>
        function editStudent(student) {
            document.getElementById('edit_id').value = student.id;
            document.getElementById('edit_name').value = student.name;
            document.getElementById('edit_roll').value = student.roll_number;
            document.getElementById('edit_email').value = student.email;
            document.getElementById('edit_class').value = student.class;
            document.getElementById('editStudentModal').style.display = 'block';
        }

        function isValidEmail(email) {
            const allowedDomains = ['gmail.com', 'yahoo.com', 'outlook.com'];
            const pattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            const domain = email.split('@')[1];
            return pattern.test(email) && allowedDomains.includes(domain);
        }

        function validateName(name) {
            return /^[A-Za-z\s]+$/.test(name);
        }

        function validateAddForm() {
            const name = document.getElementById('add_name').value.trim();
            const roll = document.getElementById('add_roll').value.trim();
            const email = document.getElementById('add_email').value.trim();
            let errors = [];

            if (!validateName(name)) errors.push("Name must contain only letters and spaces.");
            if (!roll) errors.push("Roll number is required.");
            if (!isValidEmail(email)) errors.push("Enter a valid email (e.g., gmail.com, yahoo.com).");

            if (errors.length > 0) {
                document.getElementById('add-error').style.display = 'block';
                document.getElementById('add-error').innerHTML = errors.join('<br>');
                return false;
            }

            return true;
        }

        function validateEditForm() {
            const name = document.getElementById('edit_name').value.trim();
            const roll = document.getElementById('edit_roll').value.trim();
            const email = document.getElementById('edit_email').value.trim();
            let errors = [];

            if (!validateName(name)) errors.push("Name must contain only letters and spaces.");
            if (!roll) errors.push("Roll number is required.");
            if (!isValidEmail(email)) errors.push("Enter a valid email (e.g., gmail.com, yahoo.com).");

            if (errors.length > 0) {
                document.getElementById('edit-error').style.display = 'block';
                document.getElementById('edit-error').innerHTML = errors.join('<br>');
                return false;
            }

            return true;
        }

        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>
