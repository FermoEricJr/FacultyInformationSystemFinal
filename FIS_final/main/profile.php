    <?php
    // profile.php
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

    // 1. FETCH FACULTY PROFILE & DEPARTMENT
    $sql_profile = "SELECT f.*, d.dept_name, d.dept_code 
                    FROM faculty_profiles f 
                    LEFT JOIN departments d ON f.dept_id = d.dept_id 
                    WHERE f.user_id = :uid";
    $stmt = $conn->prepare($sql_profile);
    $stmt->bindParam(':uid', $user_id);
    $stmt->execute();
    $faculty = $stmt->fetch(PDO::FETCH_ASSOC);

    $faculty_id = $faculty ? $faculty['faculty_id'] : 0;

    // 2. FETCH EDUCATION
    $sql_edu = "SELECT * FROM education WHERE faculty_id = :fid ORDER BY year_graduated DESC";
    $stmt_edu = $conn->prepare($sql_edu);
    $stmt_edu->bindParam(':fid', $faculty_id);
    $stmt_edu->execute();

    // 3. FETCH TEACHING LOAD
    $sql_load = "SELECT t.*, c.course_code, c.course_name, c.units 
                FROM teaching_load t 
                JOIN courses c ON t.course_id = c.course_id 
                WHERE t.faculty_id = :fid";
    $stmt_load = $conn->prepare($sql_load);
    $stmt_load->bindParam(':fid', $faculty_id);
    $stmt_load->execute();

    // 4. FETCH PUBLICATIONS
    $sql_pub = "SELECT * FROM publications WHERE faculty_id = :fid ORDER BY published_date DESC";
    $stmt_pub = $conn->prepare($sql_pub);
    $stmt_pub->bindParam(':fid', $faculty_id);
    $stmt_pub->execute();

    // Photo Fallback
    $photo_url = (!empty($faculty['profile_photo_url'])) ? $faculty['profile_photo_url'] : "https://static.vecteezy.com/system/resources/thumbnails/009/292/244/small/default-avatar-icon-of-social-media-user-vector.jpg";
    ?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>My Profile - FIS</title>
        <link rel="stylesheet" href="../css/home.css">
        <link rel="stylesheet" href="../css/profile.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <style>
            /* Specific style for the Manage button inside card headers */
            .btn-manage {
                font-size: 0.85rem;
                color: #800000;
                text-decoration: none;
                border: 1px solid #800000;
                padding: 5px 12px;
                border-radius: 4px;
                transition: 0.3s;
                display: inline-flex;
                align-items: center;
                gap: 5px;
            }
            .btn-manage:hover {
                background-color: #800000;
                color: white;
            }
        </style>
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
                <!-- PROFILE IS ACTIVE HERE -->
                <li>
                    <a href="profile.php" class="active">
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
                    <h2 style="margin:0; font-size:1.2rem; color:var(--text-dark);">My Profile</h2>
                </div>
                <div class="user-info">
                    <span><?php echo htmlspecialchars($faculty['first_name'] ?? $username); ?></span>
                    <img src="<?php echo $photo_url; ?>" alt="User" class="user-avatar">
                </div>
            </header>

            <div class="content-wrapper">
                <div class="container">
                    
                    <!-- LEFT SIDEBAR: PROFILE SUMMARY -->
                    <aside>
                        <div class="profile-card">
                            <?php if ($faculty): ?>
                                <img src="<?php echo $photo_url; ?>" alt="Profile Photo" class="profile-img">
                                <div class="profile-name"><?php echo htmlspecialchars($faculty['first_name'] . " " . $faculty['last_name']); ?></div>
                                <div class="profile-meta"><?php echo htmlspecialchars($faculty['designation']); ?></div>
                                <div class="profile-meta"><?php echo htmlspecialchars($faculty['dept_name']); ?></div>
                                <div class="badge">Faculty ID: <?php echo $faculty_id; ?></div>

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
                            <?php else: ?>
                                <div class="empty-state">
                                    <p>Profile Incomplete</p>
                                    <button>Setup Profile</button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </aside>

                    <!-- RIGHT CONTENT -->
                    <main>
                        <!-- TEACHING LOAD CARD -->
                        <div class="card">
                            <div class="section-title">
                                <span>Current Teaching Load <small>This Semester</small></span>
                                <!-- Added Manage Button -->
                                <a href="schedule.php" class="btn-manage">
                                    <i class="fas fa-edit"></i> Manage
                                </a>
                            </div>
                            <?php if ($stmt_load->rowCount() > 0): ?>
                                <table class="data-table">
                                    <thead><tr><th>Code</th><th>Course</th><th>Section</th><th>Schedule</th></tr></thead>
                                    <tbody>
                                        <?php while($row = $stmt_load->fetch(PDO::FETCH_ASSOC)): ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($row['course_code']); ?></strong></td>
                                                <td><?php echo htmlspecialchars($row['course_name']); ?></td>
                                                <td><?php echo htmlspecialchars($row['section_name']); ?></td>
                                                <td><?php echo htmlspecialchars($row['schedule_time']); ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <div class="empty-state">No teaching load assigned for this semester.</div>
                            <?php endif; ?>
                        </div>

                        <!-- PUBLICATIONS CARD -->
                        <div class="card">
                            <div class="section-title">
                                <span>Research & Publications <small>(Total: <?php echo $stmt_pub->rowCount(); ?>)</small></span>
                                <!-- NEW MANAGE BUTTON FOR PUBLICATIONS -->
                                <a href="publication.php" class="btn-manage">
                                    <i class="fas fa-edit"></i> Manage
                                </a>
                            </div>
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