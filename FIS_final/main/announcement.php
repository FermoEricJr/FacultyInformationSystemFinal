<?php
// announcement.php
session_start();
include "../database/dbcon.php";

// --- PREVENT CACHING ---
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Initialize Database
$db = new Database();
$conn = $db->connect();

// --- SECURITY CHECK ---
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Fetch Current User (for Header)
$stmt = $conn->prepare("SELECT first_name, profile_photo_url FROM faculty_profiles WHERE user_id = :uid");
$stmt->bindParam(':uid', $user_id);
$stmt->execute();
$current_user = $stmt->fetch(PDO::FETCH_ASSOC);

$display_name = $current_user ? $current_user['first_name'] : $username;
$photo_url = (!empty($current_user['profile_photo_url'])) ? $current_user['profile_photo_url'] : "https://static.vecteezy.com/system/resources/thumbnails/009/292/244/small/default-avatar-icon-of-social-media-user-vector.jpg";

// --- FETCH ANNOUNCEMENTS ---
$sql_ann = "SELECT * FROM announcements ORDER BY created_at DESC";
$stmt_ann = $conn->prepare($sql_ann);
$stmt_ann->execute();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements - FIS</title>
    <link rel="stylesheet" href="../css/home.css">
    <link rel="stylesheet" href="../css/announcement.css">
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
            <li>
                <a href="faculty_list.php">
                    <i class="fas fa-users"></i>
                    <span>Faculty List</span>
                </a>
            </li>
            <!-- ACTIVE LINK -->
            <li>
                <a href="announcement.php" class="active">
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
            <li style="margin-top: auto;">
                <a href="logout.php" class="logout-link">
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
                <button class="burger-menu" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
                <h2 style="margin:0; font-size:1.2rem; color:var(--text-dark);">Announcements</h2>
            </div>
            <div class="user-info">
                <span><?php echo htmlspecialchars($display_name); ?></span>
                <img src="<?php echo $photo_url; ?>" alt="User" class="user-avatar">
            </div>
        </header>

        <div class="content-wrapper" style="margin-top: 30px;">
            <div class="container" style="display: block; max-width: 800px;"> <!-- Centered column -->
                
                <?php if ($stmt_ann->rowCount() > 0): ?>
                    <?php while($row = $stmt_ann->fetch(PDO::FETCH_ASSOC)): ?>
                        <div class="announcement-card">
                            <div class="ann-header">
                                <div class="ann-title">
                                    <?php echo htmlspecialchars($row['title']); ?>
                                </div>
                                <span class="ann-date">
                                    <i class="far fa-clock"></i> 
                                    <?php echo date("M d, Y", strtotime($row['created_at'])); ?>
                                </span>
                            </div>
                            <div class="ann-body">
                                <?php echo nl2br(htmlspecialchars($row['content'])); ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <p>No announcements posted yet.</p>
                    </div>
                <?php endif; ?>

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