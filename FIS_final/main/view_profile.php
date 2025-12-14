<?php
// view_profile.php
session_start();
include "../database/dbcon.php";

// --- PREVENT CACHING ---
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

$db = new Database();
$conn = $db->connect();

// --- SECURITY CHECK ---
// Allow both Admin (1) and Faculty (2)
if (!isset($_SESSION['user_id']) || ($_SESSION['role_id'] != 2 && $_SESSION['role_id'] != 1)) {
    header("Location: index.php");
    exit();
}

$current_role = $_SESSION['role_id'];

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // Redirect based on role if ID is missing
    if ($current_role == 1) {
        header("Location: ../admin/managefaculty.php");
    } else {
        header("Location: faculty_list.php");
    }
    exit();
}

$target_id = $_GET['id'];

// Determine Back Link
$back_link = ($current_role == 1) ? "../admin/managefaculty.php" : "faculty_list.php";
$back_text = ($current_role == 1) ? "Back to Manage Faculty" : "Back to List";

// 1. FETCH TARGET FACULTY PROFILE
$sql_profile = "SELECT f.*, d.dept_name 
                FROM faculty_profiles f 
                LEFT JOIN departments d ON f.dept_id = d.dept_id 
                WHERE f.faculty_id = :fid";
$stmt = $conn->prepare($sql_profile);
$stmt->bindParam(':fid', $target_id);
$stmt->execute();
$profile = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$profile) {
    echo "Faculty member not found.";
    exit();
}

// 2. FETCH EDUCATION
$sql_edu = "SELECT * FROM education WHERE faculty_id = :fid ORDER BY year_graduated DESC";
$stmt_edu = $conn->prepare($sql_edu);
$stmt_edu->bindParam(':fid', $target_id);
$stmt_edu->execute();

// 3. FETCH SCHEDULE
$sql_load = "SELECT t.*, c.course_code, c.course_name 
             FROM teaching_load t 
             JOIN courses c ON t.course_id = c.course_id 
             WHERE t.faculty_id = :fid";
$stmt_load = $conn->prepare($sql_load);
$stmt_load->bindParam(':fid', $target_id);
$stmt_load->execute();

// 4. FETCH SCHEDULE
$stmt_sched = $conn->prepare($sql_load);
$stmt_sched->bindParam(':fid', $target_id);
$stmt_sched->execute();

// 5. FETCH PUBLICATIONS
$sql_pub = "SELECT * FROM publications WHERE faculty_id = :fid ORDER BY published_date DESC";
$stmt_pub = $conn->prepare($sql_pub);
$stmt_pub->bindParam(':fid', $target_id);
$stmt_pub->execute();

// Fallback Photo
$default_pfp = "https://static.vecteezy.com/system/resources/thumbnails/009/292/244/small/default-avatar-icon-of-social-media-user-vector.jpg";
$photo_url = (!empty($profile['profile_photo_url'])) ? $profile['profile_photo_url'] : $default_pfp;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($profile['last_name']); ?> - Profile</title>
    <link rel="stylesheet" href="../css/home.css">
    <link rel="stylesheet" href="../css/viewprofile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

    <!-- SIDEBAR (Dynamic based on Role) -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2><?php echo ($current_role == 1) ? 'FIS Admin' : 'FIS Portal'; ?></h2>
            <button class="close-btn" onclick="toggleSidebar()"><i class="fas fa-times"></i></button>
        </div>
        <ul class="nav-links">
            <?php if($current_role == 1): // ADMIN SIDEBAR ?>
                <li><a href="../admin/adminhome.php"><i class="fas fa-chart-line"></i><span>Dashboard</span></a></li>
                <li><a href="../admin/requests.php"><i class="fas fa-user-plus"></i><span>Registration Requests</span></a></li>
                <li><a href="../admin/managefaculty.php" class="active"><i class="fas fa-users-cog"></i><span>Manage Faculty</span></a></li>
                <li><a href="#"><i class="fas fa-building"></i><span>Departments</span></a></li>
            <?php else: // FACULTY SIDEBAR ?>
                <li><a href="home.php"><i class="fas fa-home"></i><span>Dashboard</span></a></li>
                <li><a href="profile.php"><i class="fas fa-user-circle"></i><span>Profile</span></a></li>
                <li><a href="faculty_list.php" class="active"><i class="fas fa-users"></i><span>Faculty List</span></a></li>
                <li><a href="announcement.php"><i class="fas fa-bullhorn"></i><span>Announcements</span></a></li>
                <li><a href="schedule.php"><i class="fas fa-calendar-alt"></i><span>Schedule</span></a></li>
            <?php endif; ?>
            
            <li style="margin-top: auto;"><a href="logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a></li>
        </ul>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content" id="main-content">
        
        <header class="top-header">
            <div style="display:flex; align-items:center; gap:15px;">
                <button class="burger-menu" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
                <h2 style="margin:0; font-size:1.2rem; color:var(--text-dark);">Faculty Profile View</h2>
            </div>
            <!-- Dynamic Back Button -->
            <a href="<?php echo $back_link; ?>" style="color:var(--maroon-primary); text-decoration:none; font-weight:600;">
                <i class="fas fa-arrow-left"></i> <?php echo $back_text; ?>
            </a>
        </header>

        <div class="content-wrapper">
            <div class="container">
                
                <!-- LEFT SIDEBAR: PROFILE SUMMARY -->
                <aside>
                    <div class="profile-card">
                        <img src="<?php echo $photo_url; ?>" alt="Profile Photo" class="profile-img">
                        <div class="profile-name">
                            <?php echo htmlspecialchars($profile['first_name'] . " " . $profile['last_name']); ?>
                        </div>
                        <div class="profile-meta"><?php echo htmlspecialchars($profile['designation']); ?></div>
                        <div class="profile-meta"><?php echo htmlspecialchars($profile['dept_name']); ?></div>
                        
                        <div style="margin-top:15px; font-size:0.9rem; color:#666;">
                            <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($profile['email']); ?>
                        </div>
                        <div style="margin-top:5px; font-size:0.9rem; color:#666;">
                            <i class="fas fa-phone"></i> <?php echo htmlspecialchars($profile['phone_number']); ?>
                        </div>

                        <div class="edu-list">
                            <div class="section-title" style="font-size: 0.9rem; border:none; margin-bottom:10px;">Academic Background</div>
                            <?php 
                            if ($stmt_edu->rowCount() > 0) {
                                while($edu = $stmt_edu->fetch(PDO::FETCH_ASSOC)) {
                                    echo '<div class="edu-item">';
                                    echo '<span class="edu-degree">'. htmlspecialchars($edu['degree_name']) .'</span>';
                                    echo '<span class="edu-school">'. htmlspecialchars($edu['institution']) . ' (' . $edu['year_graduated'] . ')</span>';
                                    echo '</div>';
                                }
                            } else {
                                echo '<p class="empty-state" style="padding:10px; font-size:0.8rem;">No education records found.</p>';
                            }
                            ?>
                        </div>
                    </div>
                </aside>

                <!-- RIGHT CONTENT -->
                <main>
                    <!-- WEEKLY SCHEDULE -->
                    <div class="card">
                        <div class="section-title">
                            Weekly Schedule <small>Class Hours</small>
                        </div>
                        <?php if ($stmt_sched->rowCount() > 0): ?>
                            <div class="schedule-list">
                                <?php while($sched = $stmt_sched->fetch(PDO::FETCH_ASSOC)): ?>
                                    <div class="schedule-item">
                                        <div class="sched-time-box">
                                            <i class="far fa-clock"></i>
                                            <span><?php echo htmlspecialchars($sched['schedule_time']); ?></span>
                                        </div>
                                        <div class="sched-details">
                                            <div class="course-code">
                                                <?php echo htmlspecialchars($sched['course_code']); ?>
                                                <span class="section-badge"><?php echo htmlspecialchars($sched['section_name']); ?></span>
                                            </div>
                                            <span class="course-name"><?php echo htmlspecialchars($sched['course_name']); ?></span>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">No schedule available.</div>
                        <?php endif; ?>
                    </div>

                 

                    <!-- PUBLICATIONS -->
                    <div class="card">
                        <div class="section-title">Publications</div>
                        <?php if ($stmt_pub->rowCount() > 0): ?>
                            <table class="data-table">
                                <thead><tr><th>Date</th><th>Title</th><th>Type</th><th>Link</th></tr></thead>
                                <tbody>
                                    <?php while($pub = $stmt_pub->fetch(PDO::FETCH_ASSOC)): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($pub['published_date']); ?></td>
                                            <td><?php echo htmlspecialchars($pub['title']); ?></td>
                                            <td><span class="badge" style="background:#eee; color:#666; font-weight:normal;"><?php echo htmlspecialchars($pub['publication_type']); ?></span></td>
                                            <td>
                                                <?php if($pub['citation_link']): ?>
                                                    <a href="<?php echo htmlspecialchars($pub['citation_link']); ?>" target="_blank" style="color:var(--maroon-primary); font-weight:bold;">View</a>
                                                <?php else: ?> - <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="empty-state">No publications recorded.</div>
                        <?php endif; ?>
                    </div>
                </main>

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