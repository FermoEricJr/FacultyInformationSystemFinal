<?php
// admin/managefaculty.php
session_start();
include "../database/dbcon.php";

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

$db = new Database();
$conn = $db->connect();

if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header("Location: ../main/index.php");
    exit();
}

$username = $_SESSION['username'];
$message = "";
$msg_type = "";

// --- HANDLE DELETE ACTION ---
if (isset($_POST['delete_faculty'])) {
    $fac_id = $_POST['faculty_id'];
    $uid = $_POST['user_id'];

    try {
        $conn->beginTransaction();
        $conn->prepare("DELETE FROM faculty_profiles WHERE faculty_id = :fid")->execute([':fid' => $fac_id]);
        $conn->prepare("DELETE FROM users WHERE user_id = :uid")->execute([':uid' => $uid]);
        $conn->commit();
        $message = "Faculty member removed successfully.";
        $msg_type = "success";
    } catch (Exception $e) {
        $conn->rollBack();
        $message = "Error deleting faculty: " . $e->getMessage();
        $msg_type = "error";
    }
}

// --- FETCH FILTER OPTIONS ---
$dept_stmt = $conn->prepare("SELECT * FROM departments ORDER BY dept_name ASC");
$dept_stmt->execute();
$departments = $dept_stmt->fetchAll(PDO::FETCH_ASSOC);

$desig_stmt = $conn->prepare("SELECT DISTINCT designation FROM faculty_profiles WHERE designation IS NOT NULL AND designation != '' ORDER BY designation ASC");
$desig_stmt->execute();
$designations = $desig_stmt->fetchAll(PDO::FETCH_COLUMN);

// --- SEARCH & FILTER LOGIC ---
$search = isset($_GET['search']) ? $_GET['search'] : '';
$filter_dept = isset($_GET['dept']) ? $_GET['dept'] : '';
$filter_desig = isset($_GET['designation']) ? $_GET['designation'] : '';

$search_query = "%$search%";

// Updated Query to JOIN 'users' table for credentials
$sql = "SELECT f.faculty_id, f.user_id, f.first_name, f.last_name, f.email, f.designation, f.profile_photo_url, d.dept_name, u.username 
        FROM faculty_profiles f 
        LEFT JOIN departments d ON f.dept_id = d.dept_id 
        LEFT JOIN users u ON f.user_id = u.user_id
        WHERE (f.first_name LIKE :s OR f.last_name LIKE :s OR f.email LIKE :s OR u.username LIKE :s)"; 

$params = [':s' => $search_query];

if (!empty($filter_dept)) {
    $sql .= " AND f.dept_id = :dept";
    $params[':dept'] = $filter_dept;
}

if (!empty($filter_desig)) {
    $sql .= " AND f.designation = :desig";
    $params[':desig'] = $filter_desig;
}

$sql .= " ORDER BY f.last_name ASC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Faculty - Admin</title>
    <link rel="stylesheet" href="../css/home.css">
    <link rel="stylesheet" href="../css/managefaculty.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
            <li><a href="adminrequest.php"><i class="fas fa-user-plus"></i><span>Registration Requests</span></a></li>
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
                <h2 style="margin:0; font-size:1.2rem; color:var(--text-dark);">Faculty Management</h2>
            </div>
            <div class="user-info">
                <span>Administrator</span>
                <div class="user-avatar" style="background: var(--maroon-primary); color: white; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-user-shield"></i>
                </div>
            </div>
        </header>

        <div class="content-wrapper" style="margin-top: 30px;">
            <div class="container">
                
                <?php if($message): ?>
                    <div class="<?php echo ($msg_type == 'error') ? 'error-msg' : 'success-msg'; ?>" style="margin-bottom: 20px; padding: 15px; border-radius: 5px; background-color: <?php echo ($msg_type == 'error') ? '#f8d7da' : '#d4edda'; ?>; color: <?php echo ($msg_type == 'error') ? '#721c24' : '#155724'; ?>;">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="section-title">
                        Registered Faculty Members
                        
                        <form method="GET" class="search-container">
                            <select name="designation" class="filter-select">
                                <option value="">All Designations</option>
                                <?php foreach($designations as $desig): ?>
                                    <option value="<?php echo $desig; ?>" <?php echo ($filter_desig == $desig) ? 'selected' : ''; ?>>
                                        <?php echo $desig; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <select name="dept" class="filter-select">
                                <option value="">All Departments</option>
                                <?php foreach($departments as $dept): ?>
                                    <option value="<?php echo $dept['dept_id']; ?>" <?php echo ($filter_dept == $dept['dept_id']) ? 'selected' : ''; ?>>
                                        <?php echo $dept['dept_name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <input type="text" name="search" class="search-box" placeholder="Search name or email..." value="<?php echo htmlspecialchars($search); ?>">
                            
                            <button type="submit" class="btn-search"><i class="fas fa-filter"></i> Filter</button>
                            
                            <?php if($search || $filter_dept || $filter_desig): ?>
                                <a href="managefaculty.php" class="btn-clear" title="Clear Filters">
                                    <i class="fas fa-times"></i> Clear
                                </a>
                            <?php endif; ?>
                        </form>
                    </div>

                    <?php if ($stmt->rowCount() > 0): ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Faculty Name</th>
                                    <th>Department</th>
                                    <th>Designation</th>
                                    <th>Email</th>
                                    <th>Login Credentials</th>
                                    <th class="center-align">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = $stmt->fetch(PDO::FETCH_ASSOC)): 
                                    $pfp = !empty($row['profile_photo_url']) ? $row['profile_photo_url'] : "https://static.vecteezy.com/system/resources/thumbnails/009/292/244/small/default-avatar-icon-of-social-media-user-vector.jpg";    
                                ?>
                                    <tr>
                                        <td>
                                            <div class="faculty-info">
                                                <img src="<?php echo $pfp; ?>" class="faculty-avatar">
                                                <span class="faculty-name"><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></span>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['dept_name']); ?></td>
                                        <td><span class="designation-badge"><?php echo htmlspecialchars($row['designation']); ?></span></td>
                                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                                        <!-- NEW COLUMN: Credentials -->
                                        <td>
                                            <div class="cred-box">
                                                <strong>User:</strong> <?php echo htmlspecialchars($row['username']); ?><br>
                                                <strong>Pass:</strong> <span style="color:#888; font-style:italic;">(Encrypted)</span>
                                            </div>
                                        </td>
                                        <td class="center-align">
                                            <div class="action-wrapper">
                                                <a href="../main/view_profile.php?id=<?php echo $row['faculty_id']; ?>" class="btn-icon btn-view" title="View Profile">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="edit_faculty.php?id=<?php echo $row['faculty_id']; ?>" class="btn-icon btn-edit" title="Edit Details">
                                                    <i class="fas fa-pen"></i>
                                                </a>
                                                <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to remove this faculty member? This cannot be undone.');">
                                                    <input type="hidden" name="faculty_id" value="<?php echo $row['faculty_id']; ?>">
                                                    <input type="hidden" name="user_id" value="<?php echo $row['user_id']; ?>">
                                                    <button type="submit" name="delete_faculty" class="btn-icon btn-del" title="Delete User">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state" style="text-align:center; padding:40px; color:#666;">
                            <p>No faculty members found matching your criteria.</p>
                        </div>
                    <?php endif; ?>
                </div>

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