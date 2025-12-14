<?php
// index.php
session_start();
include "database/dbcon.php";

// Initialize Database Connection
$db = new Database();
$conn = $db->connect();

$message = "";
$msg_type = ""; 

// --- FETCH DEPARTMENTS FOR DROPDOWN ---
try {
    $dept_stmt = $conn->prepare("SELECT * FROM departments ORDER BY dept_name ASC");
    $dept_stmt->execute();
    $departments = $dept_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $departments = [];
}

// --- HANDLE SIGNUP (Submit to Pending) ---
if (isset($_POST['signup'])) {
    // 1. Collect Data
    $username = $_POST['username'];
    $password = $_POST['password'];
    $confirm_pass = $_POST['confirm_password'];
    
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $dept_id = $_POST['dept_id'];
    $designation = $_POST['designation'];

    // 2. Validations
    if ($password !== $confirm_pass) {
        $message = "Passwords do not match!";
        $msg_type = "error";
    } else {
        try {
            // Check if username or email exists in Users, Profiles, OR Pending
            // We check all 3 to prevent duplicates
            $check_sql = "
                SELECT username FROM users WHERE username = :u 
                UNION 
                SELECT username FROM pending_registrations WHERE username = :u
                UNION
                SELECT email FROM faculty_profiles WHERE email = :e
                UNION
                SELECT email FROM pending_registrations WHERE email = :e
            ";
            
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bindParam(':u', $username);
            $check_stmt->bindParam(':e', $email);
            $check_stmt->execute();

            if ($check_stmt->rowCount() > 0) {
                $message = "Username or Email already taken/pending!";
                $msg_type = "error";
            } else {
                // 3. Insert into Pending Registrations Table
                $hashed_pass = password_hash($password, PASSWORD_DEFAULT);
                
                // Aligning with database columns: 
                // username, password_hash, first_name, last_name, email, phone, dept_id, designation
                $sql = "INSERT INTO pending_registrations 
                        (username, password_hash, first_name, last_name, email, phone, dept_id, designation) 
                        VALUES (:u, :p, :fn, :ln, :em, :ph, :did, :desig)";
                
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    ':u' => $username,
                    ':p' => $hashed_pass,
                    ':fn' => $first_name,
                    ':ln' => $last_name,
                    ':em' => $email,
                    ':ph' => $phone,
                    ':did' => $dept_id,
                    ':desig' => $designation
                ]);
                
                $message = "Registration submitted! Please wait for Admin approval.";
                $msg_type = "success";
            }
        } catch (PDOException $e) {
            $message = "Database Error: " . $e->getMessage();
            $msg_type = "error";
        }
    }
}

// --- HANDLE LOGIN ---
if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $expected_role = isset($_POST['expected_role']) ? $_POST['expected_role'] : null;

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = :username");
    $stmt->bindParam(':username', $username);
    $stmt->execute();

    if ($stmt->rowCount() === 1) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (password_verify($password, $row['password_hash'])) {
            
            if ($expected_role && $row['role_id'] != $expected_role) {
                $role_name = ($expected_role == 1) ? "Admin" : "Faculty";
                $message = "Access Denied: You are not an $role_name.";
                $msg_type = "error";
            } else {
                $_SESSION['user_id'] = $row['user_id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['role_id'] = $row['role_id'];

                if($row['role_id'] == 1) {
                     header("Location: admin/adminhome.php"); 
                     exit();
                } else {
                     header("Location: main/home.php");
                     exit();
                }
            }

        } else {
            $message = "Incorrect password.";
            $msg_type = "error";
        }
    } else {
        $message = "User not found.";
        $msg_type = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FIS Login</title>
    <link rel="stylesheet" href="css/index.css">
    <style>
        /* Specific tweaks for the expanded signup form */
        #signup-form { text-align: left; }
        .form-row { display: flex; gap: 10px; }
        .form-row .form-group { flex: 1; }
        select { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 5px; background: #fff; outline: none; }
        select:focus { border-color: var(--maroon-primary); }
    </style>
</head>
<body>
    <header class="main-header"><h1>Faculty Information System</h1></header>
    <div class="container">
        <?php if($message): ?>
            <div class="<?php echo ($msg_type == 'error') ? 'error-msg' : 'success-msg'; ?>"><?php echo $message; ?></div>
        <?php endif; ?>

        <!-- FACULTY LOGIN -->
        <div id="faculty-login-form">
            <h2>Faculty Login</h2>
            <form action="index.php" method="POST">
                <input type="hidden" name="expected_role" value="2">
                <div class="form-group"><label>Username</label><input type="text" name="username" required></div>
                <div class="form-group"><label>Password</label><input type="password" name="password" required></div>
                <button type="submit" name="login" class="btn">Login</button>
            </form>
            <div class="toggle-group">
                <a onclick="showForm('signup')">Register Profile</a>
                <a onclick="showForm('admin')" class="admin-link">Admin Login</a>
            </div>
        </div>

        <!-- ADMIN LOGIN -->
        <div id="admin-login-form" class="hidden">
            <h2 style="color: #dc3545;">Admin Login</h2>
            <form action="index.php" method="POST">
                <input type="hidden" name="expected_role" value="1">
                <div class="form-group"><label>Admin Username</label><input type="text" name="username" required></div>
                <div class="form-group"><label>Password</label><input type="password" name="password" required></div>
                <button type="submit" name="login" class="btn" style="background-color: #dc3545;">Login as Admin</button>
            </form>
            <div class="toggle-group"><a onclick="showForm('faculty')">← Back to Faculty Login</a></div>
        </div>

        <!-- SIGNUP FORM -->
        <div id="signup-form" class="hidden">
            <h2>Register Faculty Profile</h2>
            <form action="index.php" method="POST">
                
                <!-- Personal Details -->
                <div class="form-row">
                    <div class="form-group">
                        <label>First Name</label>
                        <input type="text" name="first_name" required>
                    </div>
                    <div class="form-group">
                        <label>Last Name</label>
                        <input type="text" name="last_name" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Department</label>
                        <select name="dept_id" required>
                            <option value="" disabled selected>Select Dept</option>
                            <?php foreach($departments as $dept): ?>
                                <option value="<?php echo $dept['dept_id']; ?>"><?php echo $dept['dept_name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Designation</label>
                        <select name="designation" required>
                            <option value="" disabled selected>Select</option>
                            <option value="Instructor I">Instructor I</option>
                            <option value="Instructor II">Instructor II</option>
                            <option value="Asst. Professor">Asst. Professor</option>
                            <option value="Assoc. Professor">Assoc. Professor</option>
                            <option value="Professor">Professor</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" name="phone" placeholder="09XX-XXX-XXXX">
                </div>

                <hr style="margin: 15px 0; border: 0; border-top: 1px solid #eee;">

                <!-- Account Credentials -->
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label>Confirm</label>
                        <input type="password" name="confirm_password" required>
                    </div>
                </div>

                <button type="submit" name="signup" class="btn">Submit for Approval</button>
            </form>
            <div class="toggle-group"><a onclick="showForm('faculty')">Already have an account? Login</a></div>
        </div>
    </div>
    <footer class="main-footer"><p>&copy; <?php echo date("Y"); ?> Faculty Information System.</p></footer>
    <script>
        function showForm(formName) {
            document.getElementById('faculty-login-form').classList.add('hidden');
            document.getElementById('admin-login-form').classList.add('hidden');
            document.getElementById('signup-form').classList.add('hidden');
            if (formName === 'faculty') document.getElementById('faculty-login-form').classList.remove('hidden');
            else if (formName === 'admin') document.getElementById('admin-login-form').classList.remove('hidden');
            else if (formName === 'signup') document.getElementById('signup-form').classList.remove('hidden');
        }
    </script>
</body>
</html>