<?php
// admin/edit_faculty.php
session_start();
include "../database/dbcon.php";

// --- PREVENT CACHING ---
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

$db = new Database();
$conn = $db->connect();

// --- SECURITY CHECK (Admin Only) ---
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header("Location: ../main/index.php");
    exit();
}

$message = "";
$msg_type = "";

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: managefaculty.php");
    exit();
}

$faculty_id = $_GET['id'];

// --- HANDLE FORM SUBMISSION ---
if (isset($_POST['update_faculty'])) {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $dept_id = $_POST['dept_id'];
    $designation = $_POST['designation'];
    
    try {
        // Update Faculty Profile
        $sql_update = "UPDATE faculty_profiles 
                       SET first_name = :fn, last_name = :ln, email = :em, 
                           phone_number = :ph, dept_id = :did, designation = :des 
                       WHERE faculty_id = :fid";
        
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->execute([
            ':fn' => $first_name,
            ':ln' => $last_name,
            ':em' => $email,
            ':ph' => $phone,
            ':did' => $dept_id,
            ':des' => $designation,
            ':fid' => $faculty_id
        ]);

        $message = "Faculty profile updated successfully!";
        $msg_type = "success";
        
    } catch (PDOException $e) {
        $message = "Error updating profile: " . $e->getMessage();
        $msg_type = "error";
    }
}

// --- FETCH FACULTY DATA ---
$stmt = $conn->prepare("SELECT * FROM faculty_profiles WHERE faculty_id = :fid");
$stmt->bindParam(':fid', $faculty_id);
$stmt->execute();
$faculty = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$faculty) {
    echo "Faculty member not found.";
    exit();
}

// --- FETCH DEPARTMENTS (For Dropdown) ---
$dept_stmt = $conn->query("SELECT * FROM departments ORDER BY dept_name ASC");
$departments = $dept_stmt->fetchAll(PDO::FETCH_ASSOC);

// --- FETCH CURRENT USER (For Header) ---
$admin_id = $_SESSION['user_id'];
// (Optional: fetch specific admin details if needed, otherwise just use 'Administrator')
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Faculty - Admin</title>
    <link rel="stylesheet" href="../css/home.css">
    <link rel="stylesheet" href="../css/managefaculty.css"> <!-- Reusing form/layout styles -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Specific tweaks for the Edit Form */
        .edit-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 10px;
            border-top: 5px solid var(--maroon-primary);
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        .form-header {
            margin-bottom: 30px;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
        }
        .form-header h2 {
            color: var(--maroon-primary);
            font-size: 1.5rem;
            margin: 0;
        }
        
        /* Form Layout */
        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        .form-group {
            flex: 1;
            margin-bottom: 20px;
        }
        label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #444;
            font-size: 0.9rem;
        }
        input[type="text"], 
        input[type="email"], 
        select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 0.95rem;
            outline: none;
            transition: border-color 0.3s;
        }
        input:focus, select:focus {
            border-color: var(--maroon-primary);
            box-shadow: 0 0 0 3px var(--maroon-light);
        }
        
        /* Buttons */
        .btn-save {
            background-color: var(--maroon-primary);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-save:hover {
            background-color: var(--maroon-dark);
        }
        .btn-back {
            text-decoration: none;
            color: #666;
            padding: 12px 20px;
            border: 1px solid #ccc;
            border-radius: 6px;
            margin-right: 10px;
            transition: all 0.2s;
        }
        .btn-back:hover {
            background-color: #f8f9fa;
            color: #333;
        }
        
        .button-group {
            margin-top: 30px;
            text-align: right;
        }
        
        /* Message Box */
        .alert-box {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 0.95rem;
        }
        .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>

    <!-- SIDEBAR -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2>FIS Admin</h2>
            <button class="close-btn" onclick="toggleSidebar()"><i class="fas fa-times"></i></button>
        </div>
        <ul class="nav-links">
            <li><a href="adminhome.php"><i class="fas fa-chart-line"></i><span>Dashboard</span></a></li>
            <li><a href="requests.php"><i class="fas fa-user-plus"></i><span>Registration Requests</span></a></li>
            <!-- Active Parent Link -->
            <li><a href="managefaculty.php" class="active"><i class="fas fa-users-cog"></i><span>Manage Faculty</span></a></li>
            <li><a href="adminannounce.php"><i class="fas fa-bullhorn"></i><span>Announcements</span></a></li>
            <li style="margin-top: auto;"><a href="../main/logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a></li>
        </ul>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content" id="main-content">
        
        <header class="top-header">
            <div style="display:flex; align-items:center; gap:15px;">
                <button class="burger-menu" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
                <h2 style="margin:0; font-size:1.2rem; color:var(--text-dark);">Edit Faculty Profile</h2>
            </div>
            <div class="user-info">
                <span>Administrator</span>
                <div class="user-avatar" style="background: var(--maroon-primary); color: white; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-user-shield"></i>
                </div>
            </div>
        </header>

        <div class="content-wrapper" style="margin-top: 40px;">
            
            <div class="edit-container">
                
                <div class="form-header">
                    <h2>Update Information</h2>
                    <p style="color:#666; margin-top:5px;">Editing profile for: <strong><?php echo htmlspecialchars($faculty['first_name'] . ' ' . $faculty['last_name']); ?></strong></p>
                </div>

                <?php if($message): ?>
                    <div class="alert-box <?php echo ($msg_type == 'success') ? 'alert-success' : 'alert-error'; ?>">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    
                    <!-- Name Fields -->
                    <div class="form-row">
                        <div class="form-group">
                            <label>First Name</label>
                            <input type="text" name="first_name" value="<?php echo htmlspecialchars($faculty['first_name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Last Name</label>
                            <input type="text" name="last_name" value="<?php echo htmlspecialchars($faculty['last_name']); ?>" required>
                        </div>
                    </div>

                    <!-- Academic Details -->
                    <div class="form-row">
                        <div class="form-group">
                            <label>Department</label>
                            <select name="dept_id" required>
                                <option value="" disabled>Select Department</option>
                                <?php foreach($departments as $dept): ?>
                                    <option value="<?php echo $dept['dept_id']; ?>" <?php echo ($faculty['dept_id'] == $dept['dept_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dept['dept_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Designation</label>
                            <select name="designation" required>
                                <option value="Instructor I" <?php echo ($faculty['designation'] == 'Instructor I') ? 'selected' : ''; ?>>Instructor I</option>
                                <option value="Instructor II" <?php echo ($faculty['designation'] == 'Instructor II') ? 'selected' : ''; ?>>Instructor II</option>
                                <option value="Asst. Professor" <?php echo ($faculty['designation'] == 'Asst. Professor') ? 'selected' : ''; ?>>Asst. Professor</option>
                                <option value="Assoc. Professor" <?php echo ($faculty['designation'] == 'Assoc. Professor') ? 'selected' : ''; ?>>Assoc. Professor</option>
                                <option value="Professor" <?php echo ($faculty['designation'] == 'Professor') ? 'selected' : ''; ?>>Professor</option>
                            </select>
                        </div>
                    </div>

                    <!-- Contact Info -->
                    <div class="form-row">
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($faculty['email']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="text" name="phone" value="<?php echo htmlspecialchars($faculty['phone_number']); ?>" placeholder="09XX-XXX-XXXX">
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="button-group">
                        <a href="managefaculty.php" class="btn-back">Cancel</a>
                        <button type="submit" name="update_faculty" class="btn-save">Save Changes</button>
                    </div>

                </form>
            </div>

        </div>
        
        <footer class="main-footer"><p>&copy; <?php echo date("Y"); ?> Faculty Information System.</p></footer>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
            document.getElementById('main-content').classList.toggle('shifted');
        }
    </script>
</body>
</html>