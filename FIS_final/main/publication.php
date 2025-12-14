<?php
// publications.php
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
$stmt = $conn->prepare("SELECT faculty_id FROM faculty_profiles WHERE user_id = :uid");
$stmt->bindParam(':uid', $user_id);
$stmt->execute();
$user_info = $stmt->fetch(PDO::FETCH_ASSOC);
$faculty_id = $user_info['faculty_id'];

$message = "";
$msg_type = "";

// --- HANDLE FORM SUBMISSIONS ---

// 1. ADD PUBLICATION
if (isset($_POST['add_pub'])) {
    $title = $_POST['title'];
    $type = $_POST['type'];
    $date = $_POST['date'];
    $link = $_POST['link'];

    $sql = "INSERT INTO publications (faculty_id, title, publication_type, published_date, citation_link) 
            VALUES (:fid, :title, :type, :date, :link)";
    $stmt = $conn->prepare($sql);
    if ($stmt->execute([':fid' => $faculty_id, ':title' => $title, ':type' => $type, ':date' => $date, ':link' => $link])) {
        $message = "Publication added successfully!";
        $msg_type = "success";
    } else {
        $message = "Error adding publication.";
        $msg_type = "error";
    }
}

// 2. EDIT PUBLICATION
if (isset($_POST['edit_pub'])) {
    $pub_id = $_POST['pub_id'];
    $title = $_POST['title'];
    $type = $_POST['type'];
    $date = $_POST['date'];
    $link = $_POST['link'];

    $sql = "UPDATE publications SET title = :title, publication_type = :type, published_date = :date, citation_link = :link WHERE pub_id = :pid";
    $stmt = $conn->prepare($sql);
    if ($stmt->execute([':title' => $title, ':type' => $type, ':date' => $date, ':link' => $link, ':pid' => $pub_id])) {
        $message = "Publication updated successfully!";
        $msg_type = "success";
    } else {
        $message = "Error updating publication.";
        $msg_type = "error";
    }
}

// 3. DELETE PUBLICATION
if (isset($_POST['delete_pub'])) {
    $pub_id = $_POST['pub_id'];
    $stmt = $conn->prepare("DELETE FROM publications WHERE pub_id = :pid");
    if ($stmt->execute([':pid' => $pub_id])) {
        $message = "Publication deleted.";
        $msg_type = "success";
    }
}

// --- FETCH DATA ---
$sql_pubs = "SELECT * FROM publications WHERE faculty_id = :fid ORDER BY published_date DESC";
$stmt_pubs = $conn->prepare($sql_pubs);
$stmt_pubs->bindParam(':fid', $faculty_id);
$stmt_pubs->execute();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Publications - FIS</title>
    <link rel="stylesheet" href="../css/home.css">
    <link rel="stylesheet" href="../css/publication.css">
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
            <li><a href="profile.php" class="active"><i class="fas fa-user-circle"></i><span>Profile</span></a></li>
            <li><a href="faculty_list.php"><i class="fas fa-users"></i><span>Faculty List</span></a></li>
            <li><a href="announcement.php"><i class="fas fa-bullhorn"></i><span>Announcements</span></a></li>
            <li><a href="schedule.php"><i class="fas fa-calendar-alt"></i><span>Schedule</span></a></li>
            <!-- Research Link Removed from Sidebar -->
            <li style="margin-top: auto;"><a href="logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a></li>
        </ul>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content" id="main-content">
        
        <header class="top-header">
            <div style="display:flex; align-items:center; gap:15px;">
                <button class="burger-menu" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
                <h2 style="margin:0; font-size:1.2rem; color:var(--text-dark);">Manage Publications</h2>
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
                        Research & Publications
                        <button class="btn-add" onclick="openModal('addModal')"><i class="fas fa-plus"></i> Add New</button>
                    </div>

                    <?php if ($stmt_pubs->rowCount() > 0): ?>
                        <div class="pub-list">
                            <?php while($row = $stmt_pubs->fetch(PDO::FETCH_ASSOC)): ?>
                                <div class="pub-item">
                                    <div class="pub-icon-box">
                                        <i class="fas fa-book-open"></i>
                                    </div>
                                    <div class="pub-details">
                                        <span class="pub-type"><?php echo htmlspecialchars($row['publication_type']); ?></span>
                                        <h4 class="pub-title"><?php echo htmlspecialchars($row['title']); ?></h4>
                                        <span class="pub-date">
                                            <i class="far fa-calendar"></i> <?php echo date("F d, Y", strtotime($row['published_date'])); ?>
                                        </span>
                                        <?php if($row['citation_link']): ?>
                                            <a href="<?php echo $row['citation_link']; ?>" target="_blank" class="pub-link"><i class="fas fa-external-link-alt"></i> View Link</a>
                                        <?php endif; ?>
                                    </div>
                                    <div class="pub-actions">
                                        <button class="btn-icon edit" onclick='openEditModal(<?php echo json_encode($row); ?>)'><i class="fas fa-pen"></i></button>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this publication?');">
                                            <input type="hidden" name="pub_id" value="<?php echo $row['pub_id']; ?>">
                                            <button type="submit" name="delete_pub" class="btn-icon delete"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <p>No research or publications added yet.</p>
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
                <h3>Add Publication</h3>
                <span class="close" onclick="closeModal('addModal')">&times;</span>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Title</label>
                        <input type="text" name="title" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Type</label>
                            <select name="type" required>
                                <option value="Journal">Journal</option>
                                <option value="Conference">Conference</option>
                                <option value="Book">Book</option>
                                <option value="Thesis">Thesis</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Date Published</label>
                            <input type="date" name="date" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Citation Link (Optional)</label>
                        <input type="url" name="link" placeholder="https://...">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModal('addModal')">Cancel</button>
                    <button type="submit" name="add_pub" class="btn-save">Save</button>
                </div>
            </form>
        </div>
    </div>

    <!-- EDIT MODAL -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Publication</h3>
                <span class="close" onclick="closeModal('editModal')">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="pub_id" id="edit_pub_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Title</label>
                        <input type="text" name="title" id="edit_title" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Type</label>
                            <select name="type" id="edit_type" required>
                                <option value="Journal">Journal</option>
                                <option value="Conference">Conference</option>
                                <option value="Book">Book</option>
                                <option value="Thesis">Thesis</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Date Published</label>
                            <input type="date" name="date" id="edit_date" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Citation Link (Optional)</label>
                        <input type="url" name="link" id="edit_link" placeholder="https://...">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModal('editModal')">Cancel</button>
                    <button type="submit" name="edit_pub" class="btn-save">Update</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
            document.getElementById('main-content').classList.toggle('shifted');
        }

        function openModal(id) { document.getElementById(id).style.display = "block"; }
        function closeModal(id) { document.getElementById(id).style.display = "none"; }

        function openEditModal(data) {
            document.getElementById('edit_pub_id').value = data.pub_id;
            document.getElementById('edit_title').value = data.title;
            document.getElementById('edit_type').value = data.publication_type;
            document.getElementById('edit_date').value = data.published_date;
            document.getElementById('edit_link').value = data.citation_link;
            openModal('editModal');
        }

        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = "none";
            }
        }
    </script>
</body>
</html>