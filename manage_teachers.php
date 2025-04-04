<?php
session_start();
require_once 'config.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_teacher'])) {
        $username = $_POST['username'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $email = $_POST['email'];
        
        try {
            $stmt = $pdo->prepare("INSERT INTO users (username, password, role, email) VALUES (?, ?, 'teacher', ?)");
            $stmt->execute([$username, $password, $email]);
            $_SESSION['message'] = "Teacher added successfully!";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error adding teacher: " . $e->getMessage();
        }
    } elseif (isset($_POST['update_teacher'])) {
        $id = $_POST['teacher_id'];
        $username = $_POST['username'];
        $email = $_POST['email'];
        $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : null;
        
        try {
            if ($password) {
                $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, password = ? WHERE id = ?");
                $stmt->execute([$username, $email, $password, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
                $stmt->execute([$username, $email, $id]);
            }
            $_SESSION['message'] = "Teacher updated successfully!";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error updating teacher: " . $e->getMessage();
        }
    } elseif (isset($_POST['delete_teacher'])) {
        $id = $_POST['teacher_id'];
        
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'teacher'");
            $stmt->execute([$id]);
            $_SESSION['message'] = "Teacher deleted successfully!";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error deleting teacher: " . $e->getMessage();
        }
    }
    
    header("Location: manage_teachers.php");
    exit();
}

// Get all teachers
$stmt = $pdo->query("SELECT * FROM users WHERE role = 'teacher' ORDER BY username");
$teachers = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Teachers - Attendance System</title>
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/modal.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Manage Teachers</h1>
            <div class="user-info">
                <span>Welcome, <?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'User'; ?></span>
                <a href="dashboard.php" class="btn">Back to Dashboard</a>
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
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="message error">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>
        
        <button onclick="document.getElementById('addTeacherModal').style.display='block'" class="btn">Add New Teacher</button>
        
        <table>
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($teachers as $teacher): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($teacher['username']); ?></td>
                        <td><?php echo htmlspecialchars($teacher['email']); ?></td>
                        <td>
                            <button onclick="editTeacher(<?php echo htmlspecialchars(json_encode($teacher)); ?>)" class="btn">Edit</button>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="teacher_id" value="<?php echo $teacher['id']; ?>">
                                <button type="submit" name="delete_teacher" class="btn" onclick="return confirm('Are you sure you want to delete this teacher?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Add Teacher Modal -->
    <div id="addTeacherModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="document.getElementById('addTeacherModal').style.display='none'">&times;</span>
            <h2>Add New Teacher</h2>
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
                <button type="submit" name="add_teacher" class="btn">Add Teacher</button>
            </form>
        </div>
    </div>
    
    <!-- Edit Teacher Modal -->
    <div id="editTeacherModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="document.getElementById('editTeacherModal').style.display='none'">&times;</span>
            <h2>Edit Teacher</h2>
            <form method="POST">
                <input type="hidden" name="teacher_id" id="edit_teacher_id">
                <div class="form-group">
                    <label for="edit_username">Username:</label>
                    <input type="text" id="edit_username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="edit_password">Password:</label>
                    <input type="password" id="edit_password" name="password" placeholder="Leave blank to keep current password">
                </div>
                <div class="form-group">
                    <label for="edit_email">Email:</label>
                    <input type="email" id="edit_email" name="email" required>
                </div>
                <button type="submit" name="update_teacher" class="btn">Update Teacher</button>
            </form>
        </div>
    </div>
    
    <script>
        function editTeacher(teacher) {
            document.getElementById('edit_teacher_id').value = teacher.id;
            document.getElementById('edit_username').value = teacher.username;
            document.getElementById('edit_email').value = teacher.email;
            document.getElementById('edit_password').value = ''; // Don't populate password field for security
            document.getElementById('editTeacherModal').style.display = 'block';
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