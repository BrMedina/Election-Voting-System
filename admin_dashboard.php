<?php
require_once 'dbelection.php';
session_start();

// Auth guard – Admin only
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Admin') {
    header('Location: login.php');
    exit();
}

// Logout
if (isset($_GET['logout']) && $_GET['logout'] == '1') {
    writeLog($conn, 'Logged Out');
    session_destroy();
    header('Location: login.php');
    exit();
}

// Active section
$activeSection  = 'homeSection';
$validSections  = ['homeSection','usersSection','candidatesSection','electionsSection','positionsSection','logsSection'];
if (isset($_GET['section']) && in_array($_GET['section'], $validSections)) {
    $activeSection = $_GET['section'];
}

function writeLog($conn, $action) {
    $uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 'NULL';
    $a   = $conn->real_escape_string($action);
    $conn->query("INSERT INTO tbl_logs (user_id, action, DateTime) VALUES ($uid, '$a', NOW())");
}

// ── USERS ───────────────────────────────────────────────────
if (isset($_POST['deleteUser'])) {
    $id = intval($_POST['user_id']);
    $conn->query("DELETE FROM tbl_users WHERE user_id=$id");
    writeLog($conn, "Deleted User #$id");
    echo "<script>document.addEventListener('DOMContentLoaded',()=>Swal.fire('Deleted!','User removed.','success'));</script>";
    $activeSection = 'usersSection';
}
if (isset($_POST['saveUser'])) {
    $id    = !empty($_POST['user_id']) ? intval($_POST['user_id']) : null;
    $name  = $conn->real_escape_string(trim($_POST['full_name']));
    $uname = $conn->real_escape_string(trim($_POST['username']));
    $email = $conn->real_escape_string(trim($_POST['email']));
    $role  = $conn->real_escape_string($_POST['role']);
    $phone = $conn->real_escape_string(trim($_POST['phone']));
    $gender= $conn->real_escape_string($_POST['gender']);
    $stat  = $conn->real_escape_string($_POST['status']);
    $rawPwd = trim($_POST['password'] ?? '');
    if ($id) {
        $sql = "UPDATE tbl_users SET full_name='$name',username='$uname',email='$email',role='$role',phone='$phone',gender='$gender',status='$stat'";
        if ($rawPwd !== '') $sql .= ",password='".md5($rawPwd)."'";
        $sql .= " WHERE user_id=$id";
        $conn->query($sql);
        writeLog($conn, "Updated User #$id");
        echo "<script>document.addEventListener('DOMContentLoaded',()=>Swal.fire('Updated!','User updated.','success'));</script>";
    } else {
        $otp = rand(100000,999999);
        $p   = $rawPwd !== '' ? md5($rawPwd) : md5('changeme123');
        $conn->query("INSERT INTO tbl_users (full_name,username,email,role,phone,gender,password,otp,status) VALUES ('$name','$uname','$email','$role','$phone','$gender','$p','$otp','$stat')");
        writeLog($conn, "Added User: $name");
        echo "<script>document.addEventListener('DOMContentLoaded',()=>Swal.fire('Added!','User created.','success'));</script>";
    }
    $activeSection = 'usersSection';
}

// ── CANDIDATES ──────────────────────────────────────────────
if (isset($_POST['deleteCandidate'])) {
    $id = intval($_POST['candidate_id']);
    $conn->query("DELETE FROM tbl_candidate WHERE candidate_id=$id");
    writeLog($conn, "Deleted Candidate #$id");
    echo "<script>document.addEventListener('DOMContentLoaded',()=>Swal.fire('Deleted!','Candidate removed.','success'));</script>";
    $activeSection = 'candidatesSection';
}
if (isset($_POST['saveCandidate'])) {
    $id    = !empty($_POST['candidate_id']) ? intval($_POST['candidate_id']) : null;
    $name  = $conn->real_escape_string(trim($_POST['candidate_name']));
    $party = $conn->real_escape_string(trim($_POST['party_affiliation']));
    $pos   = $conn->real_escape_string(trim($_POST['election_position']));
    if ($id) {
        $conn->query("UPDATE tbl_candidate SET candidate_name='$name',party_affiliation='$party',election_position='$pos' WHERE candidate_id=$id");
        writeLog($conn, "Updated Candidate #$id");
        echo "<script>document.addEventListener('DOMContentLoaded',()=>Swal.fire('Updated!','Candidate updated.','success'));</script>";
    } else {
        $conn->query("INSERT INTO tbl_candidate (candidate_name,party_affiliation,election_position) VALUES ('$name','$party','$pos')");
        writeLog($conn, "Added Candidate: $name");
        echo "<script>document.addEventListener('DOMContentLoaded',()=>Swal.fire('Added!','Candidate added.','success'));</script>";
    }
    $activeSection = 'candidatesSection';
}

// ── ELECTIONS ───────────────────────────────────────────────
if (isset($_POST['deleteElection'])) {
    $id = intval($_POST['election_id']);
    $conn->query("DELETE FROM tbl_election WHERE election_id=$id");
    writeLog($conn, "Deleted Election #$id");
    echo "<script>document.addEventListener('DOMContentLoaded',()=>Swal.fire('Deleted!','Election removed.','success'));</script>";
    $activeSection = 'electionsSection';
}
if (isset($_POST['saveElection'])) {
    $id    = !empty($_POST['election_id']) ? intval($_POST['election_id']) : null;
    $ename = $conn->real_escape_string(trim($_POST['election_name']));
    $edate = $conn->real_escape_string($_POST['election_date']);
    $edesc = $conn->real_escape_string(trim($_POST['description']));
    if ($id) {
        $conn->query("UPDATE tbl_election SET election_name='$ename',election_date='$edate',description='$edesc' WHERE election_id=$id");
        writeLog($conn, "Updated Election #$id");
        echo "<script>document.addEventListener('DOMContentLoaded',()=>Swal.fire('Updated!','Election updated.','success'));</script>";
    } else {
        $conn->query("INSERT INTO tbl_election (election_name,election_date,description) VALUES ('$ename','$edate','$edesc')");
        writeLog($conn, "Added Election: $ename");
        echo "<script>document.addEventListener('DOMContentLoaded',()=>Swal.fire('Added!','Election added.','success'));</script>";
    }
    $activeSection = 'electionsSection';
}

// ── POSITIONS ───────────────────────────────────────────────
if (isset($_POST['deletePosition'])) {
    $id = intval($_POST['position_id']);
    $conn->query("DELETE FROM tbl_position WHERE position_id=$id");
    writeLog($conn, "Deleted Position #$id");
    echo "<script>document.addEventListener('DOMContentLoaded',()=>Swal.fire('Deleted!','Position removed.','success'));</script>";
    $activeSection = 'positionsSection';
}
if (isset($_POST['savePosition'])) {
    $id    = !empty($_POST['position_id']) ? intval($_POST['position_id']) : null;
    $pname = $conn->real_escape_string(trim($_POST['position_name']));
    $pdesc = $conn->real_escape_string(trim($_POST['description']));
    if ($id) {
        $conn->query("UPDATE tbl_position SET position_name='$pname',description='$pdesc' WHERE position_id=$id");
        writeLog($conn, "Updated Position #$id");
        echo "<script>document.addEventListener('DOMContentLoaded',()=>Swal.fire('Updated!','Position updated.','success'));</script>";
    } else {
        $conn->query("INSERT INTO tbl_position (position_name,description) VALUES ('$pname','$pdesc')");
        writeLog($conn, "Added Position: $pname");
        echo "<script>document.addEventListener('DOMContentLoaded',()=>Swal.fire('Added!','Position added.','success'));</script>";
    }
    $activeSection = 'positionsSection';
}

// ── DATA FETCH ──────────────────────────────────────────────
$usersRes  = $conn->query("SELECT * FROM tbl_users ORDER BY user_id DESC");
$candidRes = $conn->query("SELECT * FROM tbl_candidate ORDER BY election_position, candidate_name");
$electRes  = $conn->query("SELECT * FROM tbl_election ORDER BY election_date DESC");
$posRes    = $conn->query("SELECT * FROM tbl_position ORDER BY position_name");
$logsRes   = $conn->query("SELECT l.log_id,l.user_id,u.full_name,l.action,l.DateTime FROM tbl_logs l LEFT JOIN tbl_users u ON l.user_id=u.user_id ORDER BY l.DateTime DESC LIMIT 100");

$statUsers   = $conn->query("SELECT COUNT(*) c FROM tbl_users")->fetch_assoc()['c'];
$statCandids = $conn->query("SELECT COUNT(*) c FROM tbl_candidate")->fetch_assoc()['c'];
$statVotes   = $conn->query("SELECT COUNT(*) c FROM vote")->fetch_assoc()['c'];
$statElects  = $conn->query("SELECT COUNT(*) c FROM tbl_election")->fetch_assoc()['c'];

$firstName = explode(' ', $_SESSION['fullname'] ?? 'Admin')[0];
$initials  = strtoupper(substr($_SESSION['fullname'] ?? 'A', 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard – Election System</title>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
  <style>
    :root { --sidebar-width: 260px; --topbar-height: 56px; }
    body { font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; background-color: #f8f9fa; color: #212529; }
    
    /* Layout */
    .topbar { position: fixed; top: 0; left: 0; right: 0; height: var(--topbar-height); background-color: #ffffff; border-bottom: 1px solid #dee2e6; display: flex; align-items: center; padding: 0 1.5rem; z-index: 1030; }
    .topbar-brand { font-size: 1.1rem; font-weight: 700; color: #212529; text-decoration: none; }
    .topbar-brand span { color: #0d6efd; }
    .sidebar-toggle { background: none; border: none; cursor: pointer; color: #212529; font-size: 1.4rem; margin-right: 1rem; padding: 4px 8px; border-radius: 4px; }
    .sidebar-toggle:hover { background-color: #e9ecef; }
    .user-avatar { width: 36px; height: 36px; border-radius: 50%; background-color: #0d6efd; color: #ffffff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.9rem; }
    
    .sidebar { position: fixed; top: var(--topbar-height); left: 0; bottom: 0; width: var(--sidebar-width); background-color: #212529; z-index: 1020; overflow-y: auto; transition: transform 0.2s ease-in-out; display: flex; flex-direction: column; }
    .sidebar.collapsed { transform: translateX(calc(-1 * var(--sidebar-width))); }
    .sidebar-label { font-size: 0.7rem; font-weight: 700; color: #6c757d; text-transform: uppercase; letter-spacing: 0.08em; padding: 1.25rem 1.25rem 0.5rem; }
    .sidebar-nav { list-style: none; padding: 0 0.75rem; margin: 0; }
    .sidebar-link { display: flex; align-items: center; gap: 0.75rem; padding: 0.6rem 0.8rem; color: #adb5bd; text-decoration: none; border-radius: 4px; font-size: 0.88rem; transition: background-color 0.15s, color 0.15s; }
    .sidebar-link:hover { background-color: #2c3034; color: #ffffff; }
    .sidebar-link.active { background-color: #0d6efd; color: #ffffff !important; }
    
    .layout-main { margin-top: var(--topbar-height); margin-left: var(--sidebar-width); min-height: calc(100vh - var(--topbar-height)); padding: 2rem; transition: margin-left 0.2s ease-in-out; }
    .layout-main.expanded { margin-left: 0; }
    
    .sidebar-overlay { display: none; position: fixed; inset: 0; background-color: rgba(0, 0, 0, 0.4); z-index: 1015; }
    .sidebar-overlay.show { display: block; }
    
    @media (max-width: 991px) {
      .layout-main { margin-left: 0 !important; }
      .sidebar { transform: translateX(calc(-1 * var(--sidebar-width))); }
      .sidebar.open { transform: translateX(0); }
    }
  </style>
</head>
<body>

<!-- TOPBAR -->
<header class="topbar">
  <button class="sidebar-toggle" id="sidebarToggle"><i class="bi bi-list"></i></button>
  <a class="topbar-brand" href="admin_dashboard.php">Election <span>Admin</span></a>
  <a href="index.php" class="btn btn-outline-secondary btn-sm ms-3"><i class="bi bi-arrow-left me-1"></i> Back to Main</a>
  <div class="ms-auto d-flex align-items-center gap-2">
    <div class="user-avatar"><?= $initials ?></div>
    <div class="d-none d-sm-block">
      <div style="font-size:0.85rem; font-weight:600;"><?= htmlspecialchars($_SESSION['fullname'] ?? 'Admin') ?></div>
      <div style="font-size:0.72rem; color:#6c757d;">Administrator</div>
    </div>
  </div>
</header>

<div class="sidebar-overlay" id="sbOverlay"></div>

<!-- SIDEBAR -->
<nav class="sidebar" id="sidebar">
  <div class="sidebar-label">Navigation</div>
  <ul class="sidebar-nav">
    <li><a href="admin_dashboard.php?section=homeSection" class="sidebar-link <?= $activeSection==='homeSection'?'active':'' ?>"><i class="bi bi-house-door-fill"></i> Dashboard</a></li>
    <li><a href="admin_dashboard.php?section=usersSection" class="sidebar-link <?= $activeSection==='usersSection'?'active':'' ?>"><i class="bi bi-people-fill"></i> Users</a></li>
    <li><a href="admin_dashboard.php?section=candidatesSection" class="sidebar-link <?= $activeSection==='candidatesSection'?'active':'' ?>"><i class="bi bi-person-badge-fill"></i> Candidates</a></li>
    <li><a href="admin_dashboard.php?section=electionsSection" class="sidebar-link <?= $activeSection==='electionsSection'?'active':'' ?>"><i class="bi bi-collection-fill"></i> Elections</a></li>
    <li><a href="admin_dashboard.php?section=positionsSection" class="sidebar-link <?= $activeSection==='positionsSection'?'active':'' ?>"><i class="bi bi-list-task"></i> Positions</a></li>
    <li><a href="admin_dashboard.php?section=logsSection" class="sidebar-link <?= $activeSection==='logsSection'?'active':'' ?>"><i class="bi bi-clock-history"></i> Activity Logs</a></li>
  </ul>
  <hr class="border-secondary mx-3 my-2">
  <ul class="sidebar-nav mb-3">
    <li><a href="admin_dashboard.php?logout=1" class="sidebar-link"><i class="bi bi-box-arrow-left"></i> Logout</a></li>
  </ul>
</nav>

<!-- MAIN -->
<main class="layout-main" id="layoutMain">

  <!-- HOME -->
  <div id="homeSection" <?= $activeSection!=='homeSection'?'style="display:none"':'' ?>>
    <div class="mb-4">
      <h1 class="h3 fw-bold">Welcome back, <?= htmlspecialchars($firstName) ?>!</h1>
      <p class="text-muted mb-0">Here's the election system overview.</p>
    </div>
    
    <div class="row g-3 mb-4">
      <div class="col-md-6 col-lg-3">
        <div class="card bg-primary text-white border-0 rounded-1">
          <div class="card-body d-flex align-items-center justify-content-between p-4">
            <div>
              <h6 class="card-subtitle mb-1 text-white-50 small text-uppercase">Total Users</h6>
              <h2 class="card-title mb-0 fw-bold"><?= $statUsers ?></h2>
            </div>
            <i class="bi bi-people-fill fs-1 text-white-50"></i>
          </div>
        </div>
      </div>
      <div class="col-md-6 col-lg-3">
        <div class="card bg-dark text-white border-0 rounded-1">
          <div class="card-body d-flex align-items-center justify-content-between p-4">
            <div>
              <h6 class="card-subtitle mb-1 text-white-50 small text-uppercase">Candidates</h6>
              <h2 class="card-title mb-0 fw-bold"><?= $statCandids ?></h2>
            </div>
            <i class="bi bi-person-badge-fill fs-1 text-white-50"></i>
          </div>
        </div>
      </div>
      <div class="col-md-6 col-lg-3">
        <div class="card bg-success text-white border-0 rounded-1">
          <div class="card-body d-flex align-items-center justify-content-between p-4">
            <div>
              <h6 class="card-subtitle mb-1 text-white-50 small text-uppercase">Votes Cast</h6>
              <h2 class="card-title mb-0 fw-bold"><?= $statVotes ?></h2>
            </div>
            <i class="bi bi-check2-circle fs-1 text-white-50"></i>
          </div>
        </div>
      </div>
      <div class="col-md-6 col-lg-3">
        <div class="card bg-primary text-white border-0 rounded-1">
          <div class="card-body d-flex align-items-center justify-content-between p-4">
            <div>
              <h6 class="card-subtitle mb-1 text-white-50 small text-uppercase">Elections</h6>
              <h2 class="card-title mb-0 fw-bold"><?= $statElects ?></h2>
            </div>
            <i class="bi bi-collection-fill fs-1 text-white-50"></i>
          </div>
        </div>
      </div>
    </div>
    
    <div class="card border-light shadow-sm">
      <div class="card-body p-4">
        <h5 class="fw-bold mb-1">Quick Access</h5>
        <p class="text-muted small mb-3">Navigate to any section from the sidebar or the shortcuts below.</p>
        <div class="d-flex flex-wrap gap-2">
          <a href="admin_dashboard.php?section=usersSection" class="btn btn-primary btn-sm">Manage Users</a>
          <a href="admin_dashboard.php?section=candidatesSection" class="btn btn-dark btn-sm">Manage Candidates</a>
          <a href="admin_dashboard.php?section=electionsSection" class="btn btn-secondary btn-sm">Manage Elections</a>
          <a href="admin_dashboard.php?section=positionsSection" class="btn btn-outline-secondary btn-sm">Manage Positions</a>
          <a href="admin_dashboard.php?section=logsSection" class="btn btn-outline-primary btn-sm">View Logs</a>
        </div>
      </div>
    </div>
  </div>

  <!-- USERS -->
  <div id="usersSection" <?= $activeSection!=='usersSection'?'style="display:none"':'' ?>>
    <div class="card border-light shadow-sm">
      <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <div>
            <h5 class="fw-bold mb-1">Manage Users</h5>
            <p class="text-muted mb-0 small">Add, edit, or remove system users</p>
          </div>
          <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#addUserModal"><i class="bi bi-plus-circle me-1"></i>Add User</button>
        </div>
        <div class="table-responsive">
          <table class="table table-hover table-bordered align-middle">
            <thead class="table-light">
              <tr>
                <th>#</th>
                <th>Full Name</th>
                <th>Username</th>
                <th>Email</th>
                <th>Role</th>
                <th>Phone</th>
                <th>Gender</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if($usersRes && $usersRes->num_rows>0): foreach($usersRes as $u): ?>
              <tr>
                <td><?= $u['user_id'] ?></td>
                <td><strong><?= htmlspecialchars($u['full_name']) ?></strong></td>
                <td><?= htmlspecialchars($u['username']) ?></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td><span class="badge <?= $u['role']==='Admin'?'bg-danger':($u['role']==='Organizer'?'bg-warning text-dark':'bg-success') ?>"><?= htmlspecialchars($u['role'] ?? '-') ?></span></td>
                <td><?= htmlspecialchars($u['phone'] ?? '-') ?></td>
                <td><?= htmlspecialchars($u['gender'] ?? '-') ?></td>
                <td><span class="badge <?= ($u['status']??'')=='Active'?'bg-success':'bg-warning text-dark' ?>"><?= htmlspecialchars($u['status'] ?? '-') ?></span></td>
                <td class="text-nowrap">
                  <button class="btn btn-sm btn-outline-warning me-1" onclick='editUser(<?= json_encode($u) ?>)' data-bs-toggle="modal" data-bs-target="#editUserModal" title="Edit"><i class="bi bi-pencil-fill"></i></button>
                  <button class="btn btn-sm btn-outline-danger" onclick="confirmDel('duf<?= $u['user_id'] ?>')" title="Delete"><i class="bi bi-trash-fill"></i></button>
                  <form id="duf<?= $u['user_id'] ?>" method="POST" action="admin_dashboard.php" style="display:none"><input type="hidden" name="user_id" value="<?= $u['user_id'] ?>"><input type="hidden" name="deleteUser" value="1"></form>
                </td>
              </tr>
              <?php endforeach; else: ?>
              <tr><td colspan="9" class="text-center py-4 text-muted">No users found.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- CANDIDATES -->
  <div id="candidatesSection" <?= $activeSection!=='candidatesSection'?'style="display:none"':'' ?>>
    <div class="card border-light shadow-sm">
      <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <div>
            <h5 class="fw-bold mb-1">Manage Candidates</h5>
            <p class="text-muted mb-0 small">Add, edit, or remove election candidates</p>
          </div>
          <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#addCandidateModal"><i class="bi bi-plus-circle me-1"></i>Add Candidate</button>
        </div>
        <div class="table-responsive">
          <table class="table table-hover table-bordered align-middle">
            <thead class="table-light">
              <tr>
                <th>#</th>
                <th>Name</th>
                <th>Party Affiliation</th>
                <th>Position</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if($candidRes && $candidRes->num_rows>0): foreach($candidRes as $c): ?>
              <tr>
                <td><?= $c['candidate_id'] ?></td>
                <td><strong><?= htmlspecialchars($c['candidate_name']) ?></strong></td>
                <td><?= htmlspecialchars($c['party_affiliation'] ?? '-') ?></td>
                <td><span class="badge bg-primary"><?= htmlspecialchars($c['election_position'] ?? '-') ?></span></td>
                <td class="text-nowrap">
                  <button class="btn btn-sm btn-outline-warning me-1" onclick='editCandidate(<?= json_encode($c) ?>)' data-bs-toggle="modal" data-bs-target="#editCandidateModal"><i class="bi bi-pencil-fill"></i></button>
                  <button class="btn btn-sm btn-outline-danger" onclick="confirmDel('dcf<?= $c['candidate_id'] ?>')"><i class="bi bi-trash-fill"></i></button>
                  <form id="dcf<?= $c['candidate_id'] ?>" method="POST" action="admin_dashboard.php" style="display:none"><input type="hidden" name="candidate_id" value="<?= $c['candidate_id'] ?>"><input type="hidden" name="deleteCandidate" value="1"></form>
                </td>
              </tr>
              <?php endforeach; else: ?>
              <tr><td colspan="5" class="text-center py-4 text-muted">No candidates found.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- ELECTIONS -->
  <div id="electionsSection" <?= $activeSection!=='electionsSection'?'style="display:none"':'' ?>>
    <div class="card border-light shadow-sm">
      <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <div>
            <h5 class="fw-bold mb-1">Manage Elections</h5>
            <p class="text-muted mb-0 small">Configure elections and schedules</p>
          </div>
          <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#addElectionModal"><i class="bi bi-plus-circle me-1"></i>Add Election</button>
        </div>
        <div class="table-responsive">
          <table class="table table-hover table-bordered align-middle">
            <thead class="table-light">
              <tr>
                <th>#</th>
                <th>Election Name</th>
                <th>Date</th>
                <th>Description</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if($electRes && $electRes->num_rows>0): foreach($electRes as $e): ?>
              <tr>
                <td><?= $e['election_id'] ?></td>
                <td><strong><?= htmlspecialchars($e['election_name']) ?></strong></td>
                <td><?= htmlspecialchars($e['election_date'] ?? '-') ?></td>
                <td><?= htmlspecialchars(mb_substr($e['description'] ?? '', 0, 60)).(mb_strlen($e['description']??'')>60?'...':'') ?></td>
                <td class="text-nowrap">
                  <button class="btn btn-sm btn-outline-warning me-1" onclick='editElection(<?= json_encode($e) ?>)' data-bs-toggle="modal" data-bs-target="#editElectionModal"><i class="bi bi-pencil-fill"></i></button>
                  <button class="btn btn-sm btn-outline-danger" onclick="confirmDel('def<?= $e['election_id'] ?>')"><i class="bi bi-trash-fill"></i></button>
                  <form id="def<?= $e['election_id'] ?>" method="POST" action="admin_dashboard.php" style="display:none"><input type="hidden" name="election_id" value="<?= $e['election_id'] ?>"><input type="hidden" name="deleteElection" value="1"></form>
                </td>
              </tr>
              <?php endforeach; else: ?>
              <tr><td colspan="5" class="text-center py-4 text-muted">No elections found.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- POSITIONS -->
  <div id="positionsSection" <?= $activeSection!=='positionsSection'?'style="display:none"':'' ?>>
    <div class="card border-light shadow-sm">
      <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <div>
            <h5 class="fw-bold mb-1">Manage Positions</h5>
            <p class="text-muted mb-0 small">Define election positions</p>
          </div>
          <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#addPositionModal"><i class="bi bi-plus-circle me-1"></i>Add Position</button>
        </div>
        <div class="table-responsive">
          <table class="table table-hover table-bordered align-middle">
            <thead class="table-light">
              <tr>
                <th>#</th>
                <th>Position Name</th>
                <th>Description</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if($posRes && $posRes->num_rows>0): foreach($posRes as $p): ?>
              <tr>
                <td><?= $p['position_id'] ?></td>
                <td><strong><?= htmlspecialchars($p['position_name']) ?></strong></td>
                <td><?= htmlspecialchars($p['description'] ?? '-') ?></td>
                <td class="text-nowrap">
                  <button class="btn btn-sm btn-outline-warning me-1" onclick='editPosition(<?= json_encode($p) ?>)' data-bs-toggle="modal" data-bs-target="#editPositionModal"><i class="bi bi-pencil-fill"></i></button>
                  <button class="btn btn-sm btn-outline-danger" onclick="confirmDel('dpf<?= $p['position_id'] ?>')"><i class="bi bi-trash-fill"></i></button>
                  <form id="dpf<?= $p['position_id'] ?>" method="POST" action="admin_dashboard.php" style="display:none"><input type="hidden" name="position_id" value="<?= $p['position_id'] ?>"><input type="hidden" name="deletePosition" value="1"></form>
                </td>
              </tr>
              <?php endforeach; else: ?>
              <tr><td colspan="4" class="text-center py-4 text-muted">No positions found.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- LOGS -->
  <div id="logsSection" <?= $activeSection!=='logsSection'?'style="display:none"':'' ?>>
    <div class="card border-light shadow-sm">
      <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <div>
            <h5 class="fw-bold mb-1">Activity Logs</h5>
            <p class="text-muted mb-0 small">Last 100 system actions</p>
          </div>
          <span class="badge bg-secondary"><?= $logsRes ? $logsRes->num_rows : 0 ?> entries</span>
        </div>
        <div class="table-responsive">
          <table class="table table-hover table-bordered align-middle">
            <thead class="table-light">
              <tr>
                <th>#</th>
                <th>User</th>
                <th>Action</th>
                <th>Date &amp; Time</th>
              </tr>
            </thead>
            <tbody>
              <?php if($logsRes && $logsRes->num_rows>0): foreach($logsRes as $l): ?>
              <tr>
                <td><?= $l['log_id'] ?></td>
                <td><?= htmlspecialchars($l['full_name'] ?? 'System') ?></td>
                <td><?= htmlspecialchars($l['action']) ?></td>
                <td><?= htmlspecialchars($l['DateTime'] ?? '-') ?></td>
              </tr>
              <?php endforeach; else: ?>
              <tr><td colspan="4" class="text-center py-4 text-muted">No logs found.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

</main>

<!-- MODALS -->
<!-- Add User -->
<div class="modal fade" id="addUserModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
  <div class="modal-header bg-danger text-white"><h5 class="modal-title"><i class="bi bi-person-plus me-2"></i>Add New User</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
  <form method="POST" action="admin_dashboard.php"><div class="modal-body">
    <div class="row g-3">
      <div class="col-md-6"><label class="form-label fw-semibold">Full Name</label><input type="text" name="full_name" class="form-control" required></div>
      <div class="col-md-6"><label class="form-label fw-semibold">Username</label><input type="text" name="username" class="form-control" required></div>
      <div class="col-md-6"><label class="form-label fw-semibold">Email</label><input type="email" name="email" class="form-control" required></div>
      <div class="col-md-6"><label class="form-label fw-semibold">Password</label><input type="password" name="password" class="form-control" required></div>
      <div class="col-md-4"><label class="form-label fw-semibold">Role</label><select name="role" class="form-select" required><option value="">Select role</option><option value="Admin">Admin</option><option value="Organizer">Organizer</option><option value="Voters">Voters</option></select></div>
      <div class="col-md-4"><label class="form-label fw-semibold">Gender</label><select name="gender" class="form-select"><option value="">Select</option><option>Male</option><option>Female</option><option>Other</option><option>Prefer not to say</option></select></div>
      <div class="col-md-4"><label class="form-label fw-semibold">Status</label><select name="status" class="form-select"><option value="Active">Active</option><option value="Pending">Pending</option></select></div>
      <div class="col-md-6"><label class="form-label fw-semibold">Phone</label><input type="tel" name="phone" class="form-control"></div>
    </div>
  </div>
  <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" name="saveUser" class="btn btn-danger"><i class="bi bi-save me-1"></i>Save User</button></div>
  </form>
</div></div></div>

<!-- Edit User -->
<div class="modal fade" id="editUserModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
  <div class="modal-header bg-warning"><h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit User</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <form method="POST" action="admin_dashboard.php"><div class="modal-body">
    <input type="hidden" name="user_id" id="eu_id">
    <div class="row g-3">
      <div class="col-md-6"><label class="form-label fw-semibold">Full Name</label><input type="text" name="full_name" id="eu_name" class="form-control" required></div>
      <div class="col-md-6"><label class="form-label fw-semibold">Username</label><input type="text" name="username" id="eu_uname" class="form-control" required></div>
      <div class="col-md-6"><label class="form-label fw-semibold">Email</label><input type="email" name="email" id="eu_email" class="form-control" required></div>
      <div class="col-md-6"><label class="form-label fw-semibold">New Password <small class="text-muted">(leave blank to keep)</small></label><input type="password" name="password" id="eu_pwd" class="form-control"></div>
      <div class="col-md-4"><label class="form-label fw-semibold">Role</label><select name="role" id="eu_role" class="form-select" required><option value="">Select role</option><option value="Admin">Admin</option><option value="Organizer">Organizer</option><option value="Voters">Voters</option></select></div>
      <div class="col-md-4"><label class="form-label fw-semibold">Gender</label><select name="gender" id="eu_gender" class="form-select"><option value="">Select</option><option>Male</option><option>Female</option><option>Other</option><option>Prefer not to say</option></select></div>
      <div class="col-md-4"><label class="form-label fw-semibold">Status</label><select name="status" id="eu_stat" class="form-select"><option value="Active">Active</option><option value="Pending">Pending</option></select></div>
      <div class="col-md-6"><label class="form-label fw-semibold">Phone</label><input type="tel" name="phone" id="eu_phone" class="form-control"></div>
    </div>
  </div>
  <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" name="saveUser" class="btn btn-warning"><i class="bi bi-save me-1"></i>Update User</button></div>
  </form>
</div></div></div>

<!-- Add Candidate -->
<div class="modal fade" id="addCandidateModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
  <div class="modal-header bg-danger text-white"><h5 class="modal-title"><i class="bi bi-person-plus me-2"></i>Add Candidate</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
  <form method="POST" action="admin_dashboard.php"><div class="modal-body">
    <div class="mb-3"><label class="form-label fw-semibold">Candidate Name</label><input type="text" name="candidate_name" class="form-control" required></div>
    <div class="mb-3"><label class="form-label fw-semibold">Party Affiliation</label><input type="text" name="party_affiliation" class="form-control"></div>
    <div class="mb-3"><label class="form-label fw-semibold">Position (e.g. President)</label><input type="text" name="election_position" class="form-control" required></div>
  </div>
  <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" name="saveCandidate" class="btn btn-danger"><i class="bi bi-save me-1"></i>Save</button></div>
  </form>
</div></div></div>

<!-- Edit Candidate -->
<div class="modal fade" id="editCandidateModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
  <div class="modal-header bg-warning"><h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit Candidate</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <form method="POST" action="admin_dashboard.php"><div class="modal-body">
    <input type="hidden" name="candidate_id" id="ec_id">
    <div class="mb-3"><label class="form-label fw-semibold">Candidate Name</label><input type="text" name="candidate_name" id="ec_name" class="form-control" required></div>
    <div class="mb-3"><label class="form-label fw-semibold">Party Affiliation</label><input type="text" name="party_affiliation" id="ec_party" class="form-control"></div>
    <div class="mb-3"><label class="form-label fw-semibold">Position</label><input type="text" name="election_position" id="ec_pos" class="form-control" required></div>
  </div>
  <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" name="saveCandidate" class="btn btn-warning"><i class="bi bi-save me-1"></i>Update</button></div>
  </form>
</div></div></div>

<!-- Add Election -->
<div class="modal fade" id="addElectionModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
  <div class="modal-header bg-danger text-white"><h5 class="modal-title"><i class="bi bi-collection me-2"></i>Add Election</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
  <form method="POST" action="admin_dashboard.php"><div class="modal-body">
    <div class="mb-3"><label class="form-label fw-semibold">Election Name</label><input type="text" name="election_name" class="form-control" required></div>
    <div class="mb-3"><label class="form-label fw-semibold">Election Date</label><input type="date" name="election_date" class="form-control"></div>
    <div class="mb-3"><label class="form-label fw-semibold">Description</label><textarea name="description" class="form-control" rows="3"></textarea></div>
  </div>
  <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" name="saveElection" class="btn btn-danger"><i class="bi bi-save me-1"></i>Save</button></div>
  </form>
</div></div></div>

<!-- Edit Election -->
<div class="modal fade" id="editElectionModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
  <div class="modal-header bg-warning"><h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit Election</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <form method="POST" action="admin_dashboard.php"><div class="modal-body">
    <input type="hidden" name="election_id" id="ee_id">
    <div class="mb-3"><label class="form-label fw-semibold">Election Name</label><input type="text" name="election_name" id="ee_name" class="form-control" required></div>
    <div class="mb-3"><label class="form-label fw-semibold">Election Date</label><input type="date" name="election_date" id="ee_date" class="form-control"></div>
    <div class="mb-3"><label class="form-label fw-semibold">Description</label><textarea name="description" id="ee_desc" class="form-control" rows="3"></textarea></div>
  </div>
  <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" name="saveElection" class="btn btn-warning"><i class="bi bi-save me-1"></i>Update</button></div>
  </form>
</div></div></div>

<!-- Add Position -->
<div class="modal fade" id="addPositionModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
  <div class="modal-header bg-danger text-white"><h5 class="modal-title"><i class="bi bi-list-task me-2"></i>Add Position</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
  <form method="POST" action="admin_dashboard.php"><div class="modal-body">
    <div class="mb-3"><label class="form-label fw-semibold">Position Name</label><input type="text" name="position_name" class="form-control" required></div>
    <div class="mb-3"><label class="form-label fw-semibold">Description</label><textarea name="description" class="form-control" rows="3"></textarea></div>
  </div>
  <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" name="savePosition" class="btn btn-danger"><i class="bi bi-save me-1"></i>Save</button></div>
  </form>
</div></div></div>

<!-- Edit Position -->
<div class="modal fade" id="editPositionModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
  <div class="modal-header bg-warning"><h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit Position</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <form method="POST" action="admin_dashboard.php"><div class="modal-body">
    <input type="hidden" name="position_id" id="ep_id">
    <div class="mb-3"><label class="form-label fw-semibold">Position Name</label><input type="text" name="position_name" id="ep_name" class="form-control" required></div>
    <div class="mb-3"><label class="form-label fw-semibold">Description</label><textarea name="description" id="ep_desc" class="form-control" rows="3"></textarea></div>
  </div>
  <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" name="savePosition" class="btn btn-warning"><i class="bi bi-save me-1"></i>Update</button></div>
  </form>
</div></div></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Sidebar toggle
const sidebar=document.getElementById('sidebar'),main=document.getElementById('layoutMain'),overlay=document.getElementById('sbOverlay');
let isMobile=window.innerWidth<992;
function syncSidebar(){isMobile=window.innerWidth<992;if(!isMobile){sidebar.classList.remove('open','collapsed');main.classList.remove('expanded');}else{sidebar.classList.remove('open');main.classList.add('expanded');}}
syncSidebar();
window.addEventListener('resize',syncSidebar);
document.getElementById('sidebarToggle').addEventListener('click',()=>{
  if(isMobile){const open=sidebar.classList.toggle('open');overlay.classList.toggle('show',open);}
  else{const col=sidebar.classList.toggle('collapsed');main.classList.toggle('expanded',col);}
});
overlay.addEventListener('click',()=>{sidebar.classList.remove('open');overlay.classList.remove('show');});

// Delete confirm
function confirmDel(formId){
  Swal.fire({title:'Are you sure?',text:'This action cannot be undone.',icon:'warning',showCancelButton:true,confirmButtonColor:'#dc3545',cancelButtonColor:'#6c757d',confirmButtonText:'Yes, delete it!'}).then(r=>{if(r.isConfirmed)document.getElementById(formId).submit();});
}

// Edit User
function editUser(u){
  document.getElementById('eu_id').value=u.user_id;
  document.getElementById('eu_name').value=u.full_name||'';
  document.getElementById('eu_uname').value=u.username||'';
  document.getElementById('eu_email').value=u.email||'';
  document.getElementById('eu_pwd').value='';
  document.getElementById('eu_role').value=u.role||'';
  document.getElementById('eu_gender').value=u.gender||'';
  document.getElementById('eu_stat').value=u.status||'Active';
  document.getElementById('eu_phone').value=u.phone||'';
}

// Edit Candidate
function editCandidate(c){
  document.getElementById('ec_id').value=c.candidate_id;
  document.getElementById('ec_name').value=c.candidate_name||'';
  document.getElementById('ec_party').value=c.party_affiliation||'';
  document.getElementById('ec_pos').value=c.election_position||'';
}

// Edit Election
function editElection(e){
  document.getElementById('ee_id').value=e.election_id;
  document.getElementById('ee_name').value=e.election_name||'';
  document.getElementById('ee_date').value=e.election_date||'';
  document.getElementById('ee_desc').value=e.description||'';
}

// Edit Position
function editPosition(p){
  document.getElementById('ep_id').value=p.position_id;
  document.getElementById('ep_name').value=p.position_name||'';
  document.getElementById('ep_desc').value=p.description||'';
}
</script>
</body>
</html>
