<?php
// admin/admin_announcement.php
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

// --- HANDLE POST ANNOUNCEMENT ---
if (isset($_POST['post_announcement'])) {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $admin_id = $_SESSION['user_id'];

    if (!empty($title) && !empty($content)) {
        try {
            $sql = "INSERT INTO announcements (title, content, posted_by) VALUES (:t, :c, :uid)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([':t' => $title, ':c' => $content, ':uid' => $admin_id]);
            
            $message = "Announcement posted successfully!";
            $msg_type = "success";
        } catch (Exception $e) {
            $message = "Error posting announcement: " . $e->getMessage();
            $msg_type = "error";
        }
    } else {
        $message = "Please fill in all fields.";
        $msg_type = "error";
    }
}

// --- HANDLE DELETE ANNOUNCEMENT ---
if (isset($_POST['delete_announcement'])) {
    $ann_id = $_POST['announcement_id'];
    $stmt = $conn->prepare("DELETE FROM announcements WHERE announcement_id = :id");
    $stmt->execute([':id' => $ann_id]);
    $message = "Announcement deleted.";
    $msg_type = "success";
}

// --- FETCH HISTORY ---
$sql_hist = "SELECT * FROM announcements ORDER BY created_at DESC";
$stmt_hist = $conn->prepare($sql_hist);
$stmt_hist->execute();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post Announcement - Admin</title>
    <link rel="stylesheet" href="../css/home.css">
    <link rel="stylesheet" href="../css/adminannounce.css">
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
            <li><a href="requests.php"><i class="fas fa-user-plus"></i><span>Registration Requests</span></a></li>
            <li><a href="managefaculty.php"><i class="fas fa-users-cog"></i><span>Manage Faculty</span></a></li>
            
            <!-- ACTIVE LINK -->
            <li><a href="admin_announcement.php" class="active"><i class="fas fa-bullhorn"></i><span>Announcements</span></a></li>

            <li style="margin-top: auto;"><a href="../main/logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a></li>
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
                <span>Administrator</span>
                <div class="user-avatar" style="background: var(--maroon-primary); color: white; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-user-shield"></i>
                </div>
            </div>
        </header>

        <div class="content-wrapper">
            
            <!-- ALERT MESSAGE (Moved OUTSIDE the grid container to fix layout) -->
            <?php if($message): ?>
                <div class="alert-wrapper">
                    <div class="<?php echo ($msg_type == 'error') ? 'error-msg' : 'success-msg'; ?>">
                        <?php echo $message; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="container">
                <!-- COMPOSE SECTION -->
                <div class="card compose-card">
                    <div class="section-title">Compose New Announcement</div>
                    
                    <!-- Templates Toolbar -->
                    <div class="template-toolbar">
                        <span><i class="fas fa-magic"></i> Quick Templates:</span>
                        <button type="button" class="btn-template" onclick="useTemplate('meeting')">Faculty Meeting</button>
                        <button type="button" class="btn-template" onclick="useTemplate('deadline')">Grade Deadline</button>
                        <button type="button" class="btn-template" onclick="useTemplate('maintenance')">System Maintenance</button>
                        <button type="button" class="btn-template" onclick="useTemplate('holiday')">Holiday Notice</button>
                    </div>

                    <form method="POST" class="announcement-form">
                        <div class="form-group">
                            <label>Subject / Title</label>
                            <input type="text" name="title" id="ann_title" placeholder="Enter announcement title..." required>
                        </div>
                        <div class="form-group">
                            <label>Message Content</label>
                            <textarea name="content" id="ann_content" rows="6" placeholder="Type your message here..." required></textarea>
                        </div>
                        <div class="form-actions">
                            <button type="reset" class="btn-clear">Clear</button>
                            <button type="submit" name="post_announcement" class="btn-post">
                                <i class="fas fa-paper-plane"></i> Post Announcement
                            </button>
                        </div>
                    </form>
                </div>

                <!-- HISTORY SECTION -->
                <div class="card history-card">
                    <div class="section-title">Recent Announcements</div>
                    
                    <?php if ($stmt_hist->rowCount() > 0): ?>
                        <div class="history-list">
                            <?php while($row = $stmt_hist->fetch(PDO::FETCH_ASSOC)): ?>
                                <div class="history-item">
                                    <div class="hist-header">
                                        <strong><?php echo htmlspecialchars($row['title']); ?></strong>
                                        <span class="hist-date"><?php echo date("M d, Y h:i A", strtotime($row['created_at'])); ?></span>
                                    </div>
                                    <p class="hist-preview"><?php echo substr(htmlspecialchars($row['content']), 0, 100) . '...'; ?></p>
                                    
                                    <form method="POST" onsubmit="return confirm('Delete this announcement?');">
                                        <input type="hidden" name="announcement_id" value="<?php echo $row['announcement_id']; ?>">
                                        <button type="submit" name="delete_announcement" class="btn-del-text">Delete</button>
                                    </form>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <p class="empty-state">No announcements posted yet.</p>
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

        // --- TEMPLATE LOGIC ---
        function useTemplate(type) {
            const titleInput = document.getElementById('ann_title');
            const contentInput = document.getElementById('ann_content');
            
            let templates = {
                'meeting': {
                    title: 'Mandatory Faculty Meeting',
                    content: "Dear Faculty,\n\nPlease be informed that there will be a mandatory meeting on [Date] at [Time] regarding [Topic].\n\nVenue: [Location]\n\nAttendance is required.\n\nRegards,\nAdmin"
                },
                'deadline': {
                    title: 'Submission of Grades Deadline',
                    content: "This is a reminder that the deadline for submission of grades for this semester is on [Date].\n\nPlease ensure all records are updated before the system locks.\n\nThank you."
                },
                'maintenance': {
                    title: 'System Maintenance Notice',
                    content: "The Faculty Information System will undergo scheduled maintenance on [Date] from [Start Time] to [End Time].\n\nThe system will be inaccessible during this period. Please save your work."
                },
                'holiday': {
                    title: 'Holiday Announcement',
                    content: "Please be advised that classes and office work are suspended on [Date] in observance of [Holiday Name].\n\nRegular operations will resume on [Resume Date]."
                }
            };

            if(templates[type]) {
                titleInput.value = templates[type].title;
                contentInput.value = templates[type].content;
            }
        }
    </script>
</body>
</html>