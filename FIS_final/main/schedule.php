<?php
// schedule.php
session_start();
include "../database/dbcon.php";

// --- PREVENT CACHING ---
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

$db = new Database();
$conn = $db->connect();

// --- SECURITY CHECK ---
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Get Faculty ID
$stmt = $conn->prepare("SELECT faculty_id, first_name, profile_photo_url FROM faculty_profiles WHERE user_id = :uid");
$stmt->bindParam(':uid', $user_id);
$stmt->execute();
$user_info = $stmt->fetch(PDO::FETCH_ASSOC);
$faculty_id = $user_info['faculty_id'];
$display_name = $user_info['first_name'];
$photo_url = (!empty($user_info['profile_photo_url'])) ? $user_info['profile_photo_url'] : "https://static.vecteezy.com/system/resources/thumbnails/009/292/244/small/default-avatar-icon-of-social-media-user-vector.jpg";

$message = "";
$msg_type = "";

// --- HANDLE FORM SUBMISSIONS ---

// 1. ADD SCHEDULE
if (isset($_POST['add_schedule'])) {
    $course_id = $_POST['course_id'];
    $section = $_POST['section'];
    $day = $_POST['day']; // e.g. "MWF"
    $time_start = $_POST['time_start'];
    $time_end = $_POST['time_end'];
    $semester = "1st Sem 2025"; // Hardcoded for this example, or add an input

    $sched_time = "$day $time_start-$time_end";

    $sql = "INSERT INTO teaching_load (faculty_id, course_id, section_name, schedule_time, semester) 
            VALUES (:fid, :cid, :sec, :time, :sem)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':fid' => $faculty_id, ':cid' => $course_id, ':sec' => $section, ':time' => $sched_time, ':sem' => $semester]);
    
    $message = "Schedule added successfully!";
    $msg_type = "success";
}

// 2. EDIT SCHEDULE
if (isset($_POST['edit_schedule'])) {
    $load_id = $_POST['load_id'];
    $course_id = $_POST['course_id'];
    $section = $_POST['section'];
    $day = $_POST['day'];
    $time_start = $_POST['time_start'];
    $time_end = $_POST['time_end'];
    
    $sched_time = "$day $time_start-$time_end";

    $sql = "UPDATE teaching_load SET course_id = :cid, section_name = :sec, schedule_time = :time WHERE load_id = :lid";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':cid' => $course_id, ':sec' => $section, ':time' => $sched_time, ':lid' => $load_id]);

    $message = "Schedule updated successfully!";
    $msg_type = "success";
}

// 3. DELETE SCHEDULE
if (isset($_POST['delete_schedule'])) {
    $load_id = $_POST['load_id'];
    $stmt = $conn->prepare("DELETE FROM teaching_load WHERE load_id = :lid");
    $stmt->execute([':lid' => $load_id]);
    
    $message = "Schedule removed.";
    $msg_type = "success"; // or error style for red
}

// --- FETCH DATA FOR VIEW ---

// Get Schedules
$sql_load = "SELECT t.*, c.course_code, c.course_name 
             FROM teaching_load t 
             JOIN courses c ON t.course_id = c.course_id 
             WHERE t.faculty_id = :fid";
$stmt_load = $conn->prepare($sql_load);
$stmt_load->bindParam(':fid', $faculty_id);
$stmt_load->execute();

// Get Courses (for Dropdown)
$courses = $conn->query("SELECT * FROM courses ORDER BY course_code ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Schedule - FIS</title>
    <link rel="stylesheet" href="../css/home.css">
    <link rel="stylesheet" href="../css/viewprofile.css"> <!-- Reusing card styles -->
    <link rel="stylesheet" href="../css/schedule.css">   <!-- New styles for forms/modals -->
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
            <li><a href="home.php"><i class="fas fa-home"></i><span>Dashboard</span></a></li>
            <!-- Profile is active parent -->
            <li><a href="profile.php"><i class="fas fa-user-circle"></i><span>Profile</span></a></li>
            <li><a href="faculty_list.php"><i class="fas fa-users"></i><span>Faculty List</span></a></li>
            <li><a href="announcement.php"><i class="fas fa-bullhorn"></i><span>Announcements</span></a></li>
            <!-- Schedule Link removed from Sidebar or kept depending on preference, usually kept for quick access -->
            <li><a href="schedule.php" class="active"><i class="fas fa-calendar-alt"></i><span>Schedule</span></a></li>
            <li style="margin-top: auto;"><a href="logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a></li>
        </ul>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content" id="main-content">
        
        <header class="top-header">
            <div style="display:flex; align-items:center; gap:15px;">
                <button class="burger-menu" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
                <h2 style="margin:0; font-size:1.2rem; color:var(--text-dark);">Manage Schedule</h2>
            </div>
            
            <!-- BACK TO PROFILE BUTTON -->
            <a href="profile.php" style="color:var(--maroon-primary); text-decoration:none; font-weight:600;">
                <i class="fas fa-arrow-left"></i> Back to Profile
            </a>
        </header>

        <div class="content-wrapper" style="margin-top: 30px;">
            <div class="container" style="display: block; max-width: 900px;">
                
                <?php if($message): ?>
                    <div class="<?php echo ($msg_type == 'error') ? 'error-msg' : 'success-msg'; ?>" style="margin-bottom: 20px;">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="section-title">
                        My Class Schedule
                        <button class="btn-add" onclick="openModal('addModal')"><i class="fas fa-plus"></i> Add Class</button>
                    </div>

                    <?php if ($stmt_load->rowCount() > 0): ?>
                        <div class="schedule-list">
                            <?php while($row = $stmt_load->fetch(PDO::FETCH_ASSOC)): ?>
                                <div class="schedule-item">
                                    <div class="sched-time-box">
                                        <i class="far fa-clock"></i>
                                        <span><?php echo htmlspecialchars($row['schedule_time']); ?></span>
                                    </div>
                                    <div class="sched-details">
                                        <div class="course-code">
                                            <?php echo htmlspecialchars($row['course_code']); ?>
                                            <span class="section-badge"><?php echo htmlspecialchars($row['section_name']); ?></span>
                                        </div>
                                        <span class="course-name"><?php echo htmlspecialchars($row['course_name']); ?></span>
                                    </div>
                                    <div class="sched-actions">
                                        <button class="btn-icon edit" onclick='openEditModal(<?php echo json_encode($row); ?>)'><i class="fas fa-pen"></i></button>
                                        
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this schedule?');">
                                            <input type="hidden" name="load_id" value="<?php echo $row['load_id']; ?>">
                                            <button type="submit" name="delete_schedule" class="btn-icon delete"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <p>No schedule added yet.</p>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
        
        <footer class="main-footer"><p>&copy; <?php echo date("Y"); ?> Faculty Information System.</p></footer>
    </div>

    <!-- ADD MODAL -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Class</h3>
                <span class="close" onclick="closeModal('addModal')">&times;</span>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Course Subject</label>
                        <select name="course_id" required>
                            <option value="" disabled selected>Select Subject</option>
                            <?php foreach($courses as $c): ?>
                                <option value="<?php echo $c['course_id']; ?>"><?php echo $c['course_code'] . " - " . $c['course_name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Section</label>
                        <input type="text" name="section" placeholder="e.g. BSCS-2A" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Day(s)</label>
                            <input type="text" name="day" placeholder="e.g. MWF" required>
                        </div>
                        <div class="form-group">
                            <label>Start Time</label>
                            <input type="text" name="time_start" placeholder="e.g. 9:00" required>
                        </div>
                        <div class="form-group">
                            <label>End Time</label>
                            <input type="text" name="time_end" placeholder="e.g. 10:30" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModal('addModal')">Cancel</button>
                    <button type="submit" name="add_schedule" class="btn-save">Save Schedule</button>
                </div>
            </form>
        </div>
    </div>

    <!-- EDIT MODAL -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Class</h3>
                <span class="close" onclick="closeModal('editModal')">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="load_id" id="edit_load_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Course Subject</label>
                        <select name="course_id" id="edit_course_id" required>
                            <?php foreach($courses as $c): ?>
                                <option value="<?php echo $c['course_id']; ?>"><?php echo $c['course_code'] . " - " . $c['course_name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Section</label>
                        <input type="text" name="section" id="edit_section" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Day(s)</label>
                            <input type="text" name="day" id="edit_day" required>
                        </div>
                        <div class="form-group">
                            <label>Start Time</label>
                            <input type="text" name="time_start" id="edit_start" required>
                        </div>
                        <div class="form-group">
                            <label>End Time</label>
                            <input type="text" name="time_end" id="edit_end" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModal('editModal')">Cancel</button>
                    <button type="submit" name="edit_schedule" class="btn-save">Update Schedule</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
            document.getElementById('main-content').classList.toggle('shifted');
        }

        function openModal(id) {
            document.getElementById(id).style.display = "block";
        }

        function closeModal(id) {
            document.getElementById(id).style.display = "none";
        }

        function openEditModal(data) {
            document.getElementById('edit_load_id').value = data.load_id;
            document.getElementById('edit_course_id').value = data.course_id;
            document.getElementById('edit_section').value = data.section_name;
            
            // Basic parsing of "MWF 9:00-10:00"
            // Assumes format: "DAYS START-END"
            let parts = data.schedule_time.split(' ');
            if (parts.length >= 2) {
                document.getElementById('edit_day').value = parts[0];
                let times = parts[1].split('-');
                if (times.length >= 2) {
                    document.getElementById('edit_start').value = times[0];
                    document.getElementById('edit_end').value = times[1];
                }
            } else {
                // Fallback if format is different
                document.getElementById('edit_day').value = data.schedule_time;
            }

            openModal('editModal');
        }

        // Close modal if clicked outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = "none";
            }
        }
    </script>
</body>
</html>