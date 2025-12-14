<?php
// home.php
session_start();
include "../database/dbcon.php";

// --- PREVENT CACHING ---
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Initialize Database (PDO)
$db = new Database();
$conn = $db->connect();

// --- SECURITY CHECK ---
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// 1. FETCH USER INFO
$stmt = $conn->prepare("SELECT first_name, profile_photo_url FROM faculty_profiles WHERE user_id = :uid");
$stmt->bindParam(':uid', $user_id);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$display_name = $user ? $user['first_name'] : $username;
$default_pfp = "https://static.vecteezy.com/system/resources/thumbnails/009/292/244/small/default-avatar-icon-of-social-media-user-vector.jpg";
$photo_url = (!empty($user['profile_photo_url'])) ? $user['profile_photo_url'] : $default_pfp;

// 2. FETCH NOTIFICATIONS (ANNOUNCEMENTS)
$sql_notif = "SELECT * FROM announcements ORDER BY created_at DESC LIMIT 5";
$stmt_notif = $conn->query($sql_notif);
$notifications = $stmt_notif->fetchAll(PDO::FETCH_ASSOC);

// Get count of 'new' notifications (e.g. posted in last 3 days)
$sql_count = "SELECT COUNT(*) as count FROM announcements WHERE created_at >= DATE_SUB(NOW(), INTERVAL 3 DAY)";
$notif_count = $conn->query($sql_count)->fetch(PDO::FETCH_ASSOC)['count'];

// Get the very latest one for the main container highlight
$latest_announcement = !empty($notifications) ? $notifications[0] : null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Home - FIS</title>
    <link rel="stylesheet" href="../css/home.css">
    <link rel="stylesheet" href="../css/notifications.css"> <!-- New CSS -->
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
            <li><a href="home.php" class="active"><i class="fas fa-home"></i><span>Dashboard</span></a></li>
            <li><a href="profile.php"><i class="fas fa-user-circle"></i><span>Profile</span></a></li>
            <li><a href="faculty_list.php"><i class="fas fa-users"></i><span>Faculty List</span></a></li>
            <li><a href="announcement.php"><i class="fas fa-bullhorn"></i><span>Announcements</span></a></li>
            <li><a href="schedule.php"><i class="fas fa-calendar-alt"></i><span>Schedule</span></a></li>
            <li style="margin-top: auto;"><a href="logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a></li>
        </ul>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content" id="main-content">
        
        <!-- HEADER -->
        <header class="top-header">
            <div style="display:flex; align-items:center; gap:15px;">
                <button class="burger-menu" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
                <h2 style="margin:0; font-size:1.2rem; color:var(--text-dark);">Dashboard</h2>
            </div>
            
            <div class="header-right">
                
                <!-- NOTIFICATION ICON -->
                <div class="notif-wrapper" onclick="toggleNotifDropdown()">
                    <i class="fas fa-bell notif-icon"></i>
                    <?php if($notif_count > 0): ?>
                        <span class="notif-badge"><?php echo $notif_count; ?></span>
                    <?php endif; ?>
                    
                    <!-- DROPDOWN MENU -->
                    <div class="notif-dropdown" id="notifDropdown">
                        <div class="notif-header">Notifications</div>
                        <div class="notif-list">
                            <?php if(count($notifications) > 0): ?>
                                <?php foreach($notifications as $notif): ?>
                                    <div class="notif-item" onclick="window.location.href='announcement.php'">
                                        <div class="notif-title"><?php echo htmlspecialchars($notif['title']); ?></div>
                                        <div class="notif-time"><?php echo date("M d, h:i A", strtotime($notif['created_at'])); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="notif-empty">No notifications</div>
                            <?php endif; ?>
                        </div>
                        <a href="announcement.php" class="notif-footer">View All</a>
                    </div>
                </div>

                <div class="user-info">
                    <span>Welcome, <?php echo htmlspecialchars($display_name); ?></span>
                    <img src="<?php echo $photo_url; ?>" alt="User" class="user-avatar">
                </div>
            </div>
        </header>

        <!-- DASHBOARD CONTENT -->
        <div class="content-wrapper">
            
            <!-- LATEST NOTIFICATION HIGHLIGHT -->
            <?php if($latest_announcement): ?>
                <div class="notification-highlight">
                    <div class="highlight-icon"><i class="fas fa-bullhorn"></i></div>
                    <div class="highlight-content">
                        <h3><?php echo htmlspecialchars($latest_announcement['title']); ?></h3>
                        <p><?php echo substr(htmlspecialchars($latest_announcement['content']), 0, 150) . '...'; ?></p>
                        <a href="announcement.php">Read More</a>
                    </div>
                    <button class="close-highlight" onclick="this.parentElement.style.display='none'">&times;</button>
                </div>
            <?php endif; ?>

            <div class="welcome-banner">
                <h1>WELCOME, <?php echo strtoupper(htmlspecialchars($display_name)); ?>!</h1>
                <p>Access your profile, check schedules, and connect with faculty members.</p>
            </div>

            <div class="card">
                <h3><i class="fas fa-info-circle"></i> Getting Started</h3>
                <p style="margin-top:10px; color:#666;">
                    Select <strong>"Profile"</strong> from the sidebar to view and manage your academic records.
                </p>
            </div>
        </div>
        
        <footer class="main-footer"><p>&copy; <?php echo date("Y"); ?> Faculty Information System.</p></footer>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
            document.getElementById('main-content').classList.toggle('shifted');
        }

        function toggleNotifDropdown() {
            const dropdown = document.getElementById('notifDropdown');
            dropdown.classList.toggle('show');
        }

        // Close dropdown when clicking outside
        window.onclick = function(event) {
            if (!event.target.matches('.notif-icon') && !event.target.matches('.notif-badge') && !event.target.closest('.notif-wrapper')) {
                var dropdowns = document.getElementsByClassName("notif-dropdown");
                for (var i = 0; i < dropdowns.length; i++) {
                    var openDropdown = dropdowns[i];
                    if (openDropdown.classList.contains('show')) {
                        openDropdown.classList.remove('show');
                    }
                }
            }
        }
    </script>
</body>
</html>