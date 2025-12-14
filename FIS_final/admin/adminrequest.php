<?php
// admin/requests.php
session_start();
include "../database/dbcon.php";

// --- PREVENT CACHING ---
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

$db = new Database();
$conn = $db->connect();

// Check if Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header("Location: ../main/index.php");
    exit();
}

$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Admin';
$message = "";

// --- HANDLE ACCEPT ---
if (isset($_POST['accept'])) {
    $req_id = $_POST['request_id'];
    
    // Fetch request details
    $stmt = $conn->prepare("SELECT * FROM pending_registrations WHERE request_id = :id");
    $stmt->bindParam(':id', $req_id);
    $stmt->execute();
    $req = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($req) {
        try {
            $conn->beginTransaction();

            // 1. Insert into Users
            $sql_user = "INSERT INTO users (username, password_hash, role_id) VALUES (:u, :p, 2)";
            $stmt_u = $conn->prepare($sql_user);
            $stmt_u->execute([':u' => $req['username'], ':p' => $req['password_hash']]);
            $new_user_id = $conn->lastInsertId();

            // 2. Insert into Faculty Profiles
            $sql_prof = "INSERT INTO faculty_profiles (user_id, dept_id, first_name, last_name, email, phone_number, designation, hire_date) 
                         VALUES (:uid, :did, :fn, :ln, :em, :ph, :des, NOW())";
            $stmt_p = $conn->prepare($sql_prof);
            $stmt_p->execute([
                ':uid' => $new_user_id,
                ':did' => $req['dept_id'],
                ':fn' => $req['first_name'],
                ':ln' => $req['last_name'],
                ':em' => $req['email'],
                ':ph' => $req['phone'],
                ':des' => $req['designation']
            ]);

            // 3. Delete from Pending
            $conn->prepare("DELETE FROM pending_registrations WHERE request_id = :id")->execute([':id' => $req_id]);

            $conn->commit();
            $message = "<div class='success-msg'>User accepted successfully!</div>";
        } catch (Exception $e) {
            $conn->rollBack();
            $message = "<div class='error-msg'>Error: " . $e->getMessage() . "</div>";
        }
    }
}

// --- HANDLE REJECT ---
if (isset($_POST['reject'])) {
    $req_id = $_POST['request_id'];
    $conn->prepare("DELETE FROM pending_registrations WHERE request_id = :id")->execute([':id' => $req_id]);
    $message = "<div class='error-msg' style='background:#fff0f0; color:red;'>Request rejected and removed.</div>";
}

// Fetch Pending Requests
$pending = $conn->query("SELECT p.*, d.dept_name FROM pending_registrations p LEFT JOIN departments d ON p.dept_id = d.dept_id")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Requests - Admin</title>
    <link rel="stylesheet" href="../css/home.css">
    <link rel="stylesheet" href="../css/adminreq.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Adjust main content to account for sidebar */
        body {
            display: flex; /* Critical for sidebar layout */
            min-height: 100vh;
            background-color: #f4f6f9;
            padding-top: 0; /* Reset padding for sidebar layout */
        }
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            transition: margin-left 0.3s ease;
        }
        /* Header specific overrides for this page since adminreq.css might conflict */
        .top-header {
            position: sticky;
            top: 0;
            z-index: 900;
            background-color: white;
            padding: 15px 30px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .table-container {
            margin: 30px auto;
            max-width: 1200px;
            width: 95%;
        }
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
            <li>
                <a href="adminhome.php">
                    <i class="fas fa-chart-line"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <!-- ACTIVE LINK -->
            <li>
                <a href="#" class="active">
                    <i class="fas fa-user-plus"></i>
                    <span>Registration Requests</span>
                </a>
            </li>
            <li>
                <a href="managefaculty.php">
                    <i class="fas fa-users-cog"></i>
                    <span>Manage Faculty</span>
                </a>
            </li>

          <li><a href="adminannounce.php"><i class="fas fa-bullhorn"></i><span>Announcements</span></a></li>

            <li style="margin-top: auto;">
                <a href="../main/logout.php" class="logout-link">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content" id="main-content">
        
        <header class="top-header">
            <div style="display:flex; align-items:center; gap:15px;">
                <button class="burger-menu" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                <h2 style="margin:0; font-size:1.2rem; color:var(--text-dark);">Registration Requests</h2>
            </div>
            <div class="user-info">
                <span>Administrator</span>
                <div class="user-avatar" style="background: var(--maroon-primary); color: white; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-user-shield"></i>
                </div>
            </div>
        </header>

        <div class="table-container">
            <?php echo $message; ?>
            
            <?php if(count($pending) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Dept</th>
                            <th>Email</th>
                            <th>Designation</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($pending as $row): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['dept_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td><?php echo htmlspecialchars($row['designation']); ?></td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="request_id" value="<?php echo $row['request_id']; ?>">
                                        <button type="submit" name="accept" class="btn-sm btn-accept">Accept</button>
                                        <button type="submit" name="reject" class="btn-sm btn-reject" onclick="return confirm('Reject this user?')">Reject</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="padding: 40px; text-align: center; color: #666; background: white; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">No pending registration requests.</p>
            <?php endif; ?>
        </div>
        
        <!-- Simple footer to match layout -->
        <footer class="main-footer" style="background-color: #333; color: #ccc; text-align: center; padding: 15px; font-size: 0.85rem; margin-top: auto;">
            <p>&copy; <?php echo date("Y"); ?> Faculty Information System.</p>
        </footer>

    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
            document.getElementById('main-content').classList.toggle('shifted');
        }
    </script>
</body>
</html>