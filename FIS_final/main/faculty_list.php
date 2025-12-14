<?php
// faculty_list.php
session_start();
include "../database/dbcon.php";

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

$db = new Database();
$conn = $db->connect();

if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// 1. FETCH CURRENT USER
$stmt = $conn->prepare("SELECT first_name, last_name, profile_photo_url FROM faculty_profiles WHERE user_id = :uid");
$stmt->bindParam(':uid', $user_id);
$stmt->execute();
$current_user = $stmt->fetch(PDO::FETCH_ASSOC);

$display_name = $current_user ? $current_user['first_name'] : $username;
$photo_url = (!empty($current_user['profile_photo_url'])) ? $current_user['profile_photo_url'] : "https://static.vecteezy.com/system/resources/thumbnails/009/292/244/small/default-avatar-icon-of-social-media-user-vector.jpg";

// --- FETCH FILTER OPTIONS ---
// Get Departments
$dept_stmt = $conn->prepare("SELECT * FROM departments ORDER BY dept_name ASC");
$dept_stmt->execute();
$departments = $dept_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get Designations (Distinct)
$desig_stmt = $conn->prepare("SELECT DISTINCT designation FROM faculty_profiles WHERE designation IS NOT NULL AND designation != '' ORDER BY designation ASC");
$desig_stmt->execute();
$designations = $desig_stmt->fetchAll(PDO::FETCH_COLUMN);

// --- SEARCH & FILTER LOGIC ---
$search = isset($_GET['search']) ? $_GET['search'] : '';
$filter_dept = isset($_GET['dept']) ? $_GET['dept'] : '';
$filter_desig = isset($_GET['designation']) ? $_GET['designation'] : '';

$search_query = "%$search%";

// Build Query dynamically
$sql_list = "SELECT f.faculty_id, f.first_name, f.last_name, f.email, f.designation, f.profile_photo_url, d.dept_name 
             FROM faculty_profiles f 
             LEFT JOIN departments d ON f.dept_id = d.dept_id 
             WHERE (f.first_name LIKE :s OR f.last_name LIKE :s OR d.dept_name LIKE :s)";

$params = [':s' => $search_query];

// Append Filters if selected
if (!empty($filter_dept)) {
    $sql_list .= " AND f.dept_id = :dept";
    $params[':dept'] = $filter_dept;
}

if (!empty($filter_desig)) {
    $sql_list .= " AND f.designation = :desig";
    $params[':desig'] = $filter_desig;
}

$sql_list .= " ORDER BY f.last_name ASC";

$stmt_list = $conn->prepare($sql_list);
$stmt_list->execute($params);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Directory - FIS</title>
    <link rel="stylesheet" href="../css/facultylist.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

</head>
<body>

    <!-- SIDEBAR -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2>FIS Portal</h2>
            <button class="close-btn" onclick="toggleSidebar()"><i class="fas fa-times"></i></button>
        </div>
        
        <ul class="nav-links">
            <li>
                <a href="home.php">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="profile.php">
                    <i class="fas fa-user-circle"></i>
                    <span>Profile</span>
                </a>
            </li>
            <!-- ACTIVE LINK -->
            <li>
                <a href="faculty_list.php" class="active">
                    <i class="fas fa-users"></i>
                    <span>Faculty List</span>
                </a>
            </li>
            <li>
                <a href="announcement.php">
                    <i class="fas fa-bullhorn"></i>
                    <span>Announcements</span>
                </a>
            </li>
            <li>
                <a href="schedule.php">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Schedule</span>
                </a>
            </li>

            <li style="margin-top: auto;"><a href="logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a></li>
        </ul>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content" id="main-content">
        
        <header class="top-header">
            <div style="display:flex; align-items:center; gap:15px;">
                <button class="burger-menu" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
                <h2 style="margin:0; font-size:1.2rem; color:var(--text-dark);">Faculty Directory</h2>
            </div>
            <div class="user-info">
                <span><?php echo htmlspecialchars($display_name); ?></span>
                <img src="<?php echo $photo_url; ?>" alt="User" class="user-avatar">
            </div>
        </header>

        <div class="content-wrapper" style="margin-top: 30px;">
            <div class="container" style="display: block; max-width: 98%;"> 
                
                <div class="card">
                    <div class="section-title" style="flex-wrap: wrap; gap: 10px;">
                        Faculty Members
                        
                        <!-- Search & Filter Bar -->
                        <div class="search-container" style="margin-bottom:0;">
                            <form method="GET" class="search-form" style="flex-wrap: wrap;">
                                
                                <!-- Designation Filter -->
                                <select name="designation" class="filter-select">
                                    <option value="">All Designations</option>
                                    <?php foreach($designations as $desig): ?>
                                        <option value="<?php echo $desig; ?>" <?php echo ($filter_desig == $desig) ? 'selected' : ''; ?>>
                                            <?php echo $desig; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                                <!-- Department Filter -->
                                <select name="dept" class="filter-select">
                                    <option value="">All Departments</option>
                                    <?php foreach($departments as $dept): ?>
                                        <option value="<?php echo $dept['dept_id']; ?>" <?php echo ($filter_dept == $dept['dept_id']) ? 'selected' : ''; ?>>
                                            <?php echo $dept['dept_name']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                                <input type="text" name="search" class="search-input" placeholder="Search name..." value="<?php echo htmlspecialchars($search); ?>">
                                
                                <button type="submit" class="btn-search"><i class="fas fa-filter"></i> Filter</button>
                                
                                <?php if($search || $filter_dept || $filter_desig): ?>
                                    <a href="faculty_list.php" style="padding: 8px; color: #dc3545; text-decoration: none; font-weight: 500;">
                                        <i class="fas fa-times"></i> Clear
                                    </a>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>

                    <?php if ($stmt_list->rowCount() > 0): ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Department</th>
                                    <th>Designation</th>
                                    <th>Email</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = $stmt_list->fetch(PDO::FETCH_ASSOC)): 
                                    $pfp = !empty($row['profile_photo_url']) ? $row['profile_photo_url'] : "https://static.vecteezy.com/system/resources/thumbnails/009/292/244/small/default-avatar-icon-of-social-media-user-vector.jpg";
                                ?>
                                    <tr>
                                        <td>
                                            <img src="<?php echo $pfp; ?>" class="faculty-avatar">
                                            <strong><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['dept_name']); ?></td>
                                        <td><span class="badge" style="background:#f4f6f9; color:#555;"><?php echo htmlspecialchars($row['designation']); ?></span></td>
                                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                                        <td>
                                            <a href="view_profile.php?id=<?php echo $row['faculty_id']; ?>" class="btn-view">
                                                <i class="fas fa-eye"></i> View Profile
                                            </a>
                                            <a href="mailto:<?php echo $row['email']; ?>" style="color:#666; text-decoration:none; font-size:1.1rem; vertical-align: middle;">
                                                <i class="fas fa-envelope"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">
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