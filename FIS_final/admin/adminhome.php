<?php
// admin/adminhome.php
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

$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Admin';

// --- 1. QUICK STATS ---
$stmt_fac = $conn->query("SELECT COUNT(*) as count FROM faculty_profiles");
$total_faculty = $stmt_fac->fetch(PDO::FETCH_ASSOC)['count'];

$stmt_req = $conn->query("SELECT COUNT(*) as count FROM pending_registrations");
$pending_count = $stmt_req->fetch(PDO::FETCH_ASSOC)['count'];

$stmt_dept = $conn->query("SELECT COUNT(*) as count FROM departments");
$total_depts = $stmt_dept->fetch(PDO::FETCH_ASSOC)['count'];

// --- 2. CHART DATA: FACULTY PER DEPARTMENT ---
$chart_query = "SELECT d.dept_name, COUNT(f.faculty_id) as count 
                FROM departments d 
                LEFT JOIN faculty_profiles f ON d.dept_id = f.dept_id 
                GROUP BY d.dept_id";
$stmt_chart = $conn->prepare($chart_query);
$stmt_chart->execute();
$chart_data = $stmt_chart->fetchAll(PDO::FETCH_ASSOC);

$dept_labels = [];
$dept_counts = [];
foreach($chart_data as $data) {
    $dept_labels[] = $data['dept_name'];
    $dept_counts[] = $data['count'];
}

// --- 3. CHART DATA: FACULTY PER DESIGNATION ---
$desig_query = "SELECT designation, COUNT(*) as count 
                FROM faculty_profiles 
                WHERE designation IS NOT NULL AND designation != '' 
                GROUP BY designation";
$stmt_desig = $conn->prepare($desig_query);
$stmt_desig->execute();
$desig_data = $stmt_desig->fetchAll(PDO::FETCH_ASSOC);

$desig_labels = [];
$desig_counts = [];
foreach($desig_data as $data) {
    $desig_labels[] = $data['designation'];
    $desig_counts[] = $data['count'];
}

// --- 4. FULL FACULTY LIST ---
$list_sql = "SELECT f.first_name, f.last_name, f.email, f.designation, d.dept_name 
             FROM faculty_profiles f 
             LEFT JOIN departments d ON f.dept_id = d.dept_id 
             ORDER BY d.dept_name, f.last_name";
$stmt_list = $conn->query($list_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard & Report</title>
    <link rel="stylesheet" href="../css/home.css">
    <link rel="stylesheet" href="../css/adminhome.css">
    <link rel="stylesheet" href="../css/adminprint.css" media="print"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

    <!-- SIDEBAR -->
    <div class="sidebar no-print" id="sidebar">
        <div class="sidebar-header">
            <h2>FIS Admin</h2>
            <button class="close-btn" onclick="toggleSidebar()"><i class="fas fa-times"></i></button>
        </div>
        <ul class="nav-links">
            <li><a href="#" class="active"><i class="fas fa-chart-line"></i><span>Dashboard</span></a></li>
            <li><a href="adminrequest.php"><i class="fas fa-user-plus"></i><span>Registration Requests</span><?php if($pending_count > 0): ?><span class="alert-badge"><?php echo $pending_count; ?></span><?php endif; ?></a></li>
            <li><a href="managefaculty.php"><i class="fas fa-users-cog"></i><span>Manage Faculty</span></a></li>
            <li><a href="adminannounce.php"><i class="fas fa-users-cog"></i><span>Announcement  </span></a></li>
            <li style="margin-top: auto;"><a href="../main/logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a></li>
        </ul>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content" id="main-content">
        
        <!-- HEADER -->
        <header class="top-header no-print">
            <div style="display:flex; align-items:center; gap:15px;">
                <button class="burger-menu" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
                <h2 style="margin:0; font-size:1.2rem; color:var(--text-dark);">Admin Dashboard</h2>
            </div>
            <div class="user-info">
                <span>Administrator</span>
                <div class="user-avatar" style="background: var(--maroon-primary); color: white; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-user-shield"></i>
                </div>
            </div>
        </header>

        <div class="content-wrapper">
            
            <!-- TITLE SECTION -->
            <div class="dashboard-header" id="print-header">
                <div class="welcome-text">
                    <h1>Faculty Information Overview</h1>
                    <p>System Status and Demographic Report</p>
                </div>
                <button class="btn-print no-print" onclick="printPage('all')">
                    <i class="fas fa-print"></i> Print Full Report
                </button>
            </div>

            <!-- STATS CARDS (Visible in All Prints) -->
            <div class="stats-grid" id="stats-section">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                    <span class="stat-number"a><?php echo $total_faculty; ?></span>
                    <span class="stat-label">Total Faculty</span>
                </div>
                <div class="stat-card no-print" onclick="window.location.href='adminrequest.php'" style="cursor: pointer;">
                    <div class="stat-icon" style="color: <?php echo ($pending_count > 0) ? '#dc3545' : 'var(--maroon-light)'; ?>">
                        <i class="fas fa-user-clock"></i>
                    </div>
                    <span class="stat-number" style="color: <?php echo ($pending_count > 0) ? '#dc3545' : 'var(--maroon-primary)'; ?>">
                        <?php echo $pending_count; ?>
                    </span>
                    <span class="stat-label">Pending Requests</span>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-university"></i></div>
                    <span class="stat-number"><?php echo $total_depts; ?></span>
                    <span class="stat-label">Departments</span>
                </div>
            </div>

            <!-- CHARTS ROW -->
            <div class="charts-row" style="display: flex; gap: 20px; flex-wrap: wrap;">
                
                <!-- DEPT CHART -->
                <div class="card chart-container" id="dept-chart-card" style="flex: 1; min-width: 400px;">
                    <div class="section-title">
                        Department Distribution
                        <button class="btn-icon no-print" onclick="printPage('dept')" title="Print This Graph">
                            <i class="fas fa-print"></i>
                        </button>
                    </div>
                    <div style="height: 300px; width: 100%;">
                        <canvas id="deptChart"></canvas>
                    </div>
                </div>

                <!-- DESIGNATION CHART -->
                <div class="card chart-container" id="desig-chart-card" style="flex: 1; min-width: 400px;">
                    <div class="section-title">
                        Designation Breakdown
                        <button class="btn-icon no-print" onclick="printPage('desig')" title="Print This Graph">
                            <i class="fas fa-print"></i>
                        </button>
                    </div>
                    <div style="height: 300px; width: 100%;">
                        <canvas id="desigChart"></canvas>
                    </div>
                </div>

            </div>

            <!-- FACULTY LIST TABLE -->
            <div class="card report-list" id="list-card">
                <div class="section-title">
                    Faculty Roster
                    <button class="btn-icon no-print" onclick="printPage('list')" title="Print List">
                        <i class="fas fa-print"></i>
                    </button>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Department</th>
                            <th>Designation</th>
                            <th>Email</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($stmt_list->rowCount() > 0): ?>
                            <?php while($row = $stmt_list->fetch(PDO::FETCH_ASSOC)): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($row['first_name'] . " " . $row['last_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['dept_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['designation']); ?></td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" style="text-align:center;">No faculty members found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
        
        <footer class="main-footer no-print"><p>&copy; <?php echo date("Y"); ?> Faculty Information System.</p></footer>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
            document.getElementById('main-content').classList.toggle('shifted');
        }

        // --- PRINT FUNCTION ---
        function printPage(section) {
            // Reset classes
            document.body.classList.remove('print-all', 'print-dept', 'print-desig', 'print-list');
            
            // Add specific class
            document.body.classList.add('print-' + section);
            
            window.print();
            
            // Remove class after print dialog closes (optional, mostly for cleanup)
            // document.body.classList.remove('print-' + section);
        }

        // --- CHART 1: DEPARTMENTS ---
        const ctxDept = document.getElementById('deptChart').getContext('2d');
        new Chart(ctxDept, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($dept_labels); ?>,
                datasets: [{
                    label: 'Faculty Count',
                    data: <?php echo json_encode($dept_counts); ?>,
                    backgroundColor: 'rgba(128, 0, 0, 0.7)', 
                    borderColor: 'rgba(128, 0, 0, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } },
                plugins: { legend: { display: false } }
            }
        });

        // --- CHART 2: DESIGNATIONS (Pie Chart) ---
        const ctxDesig = document.getElementById('desigChart').getContext('2d');
        new Chart(ctxDesig, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode($desig_labels); ?>,
                datasets: [{
                    data: <?php echo json_encode($desig_counts); ?>,
                    backgroundColor: [
                        'rgba(128, 0, 0, 0.8)',   // Maroon
                        'rgba(220, 53, 69, 0.7)', // Red
                        'rgba(255, 193, 7, 0.7)', // Yellow
                        'rgba(40, 167, 69, 0.7)', // Green
                        'rgba(23, 162, 184, 0.7)', // Teal
                        'rgba(108, 117, 125, 0.7)' // Grey
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'right' }
                }
            }
        });
    </script>
</body>
</html>