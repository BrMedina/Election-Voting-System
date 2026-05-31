<?php
require_once 'dbelection.php';
session_start();

// Auth guard – Organizer only
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Organizer') {
    header('Location: login.php');
    exit();
}

// Logout
if (isset($_GET['logout']) && $_GET['logout'] == '1') {
    session_destroy();
    header('Location: login.php');
    exit();
}

// Active section (no users/logs allowed)
$activeSection = 'homeSection';
$validSections = ['homeSection','candidatesSection','electionsSection','positionsSection'];
if (isset($_GET['section']) && in_array($_GET['section'], $validSections)) {
    $activeSection = $_GET['section'];
}

// ── CANDIDATES ──────────────────────────────────────────────
if (isset($_POST['deleteCandidate'])) {
    $id = intval($_POST['candidate_id']);
    $conn->query("DELETE FROM tbl_candidate WHERE candidate_id=$id");
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
        echo "<script>document.addEventListener('DOMContentLoaded',()=>Swal.fire('Updated!','Candidate updated.','success'));</script>";
    } else {
        $conn->query("INSERT INTO tbl_candidate (candidate_name,party_affiliation,election_position) VALUES ('$name','$party','$pos')");
        echo "<script>document.addEventListener('DOMContentLoaded',()=>Swal.fire('Added!','Candidate added.','success'));</script>";
    }
    $activeSection = 'candidatesSection';
}

// ── ELECTIONS ───────────────────────────────────────────────
if (isset($_POST['deleteElection'])) {
    $id = intval($_POST['election_id']);
    $conn->query("DELETE FROM tbl_election WHERE election_id=$id");
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
        echo "<script>document.addEventListener('DOMContentLoaded',()=>Swal.fire('Updated!','Election updated.','success'));</script>";
    } else {
        $conn->query("INSERT INTO tbl_election (election_name,election_date,description) VALUES ('$ename','$edate','$edesc')");
        echo "<script>document.addEventListener('DOMContentLoaded',()=>Swal.fire('Added!','Election added.','success'));</script>";
    }
    $activeSection = 'electionsSection';
}

// ── POSITIONS ───────────────────────────────────────────────
if (isset($_POST['deletePosition'])) {
    $id = intval($_POST['position_id']);
    $conn->query("DELETE FROM tbl_position WHERE position_id=$id");
    echo "<script>document.addEventListener('DOMContentLoaded',()=>Swal.fire('Deleted!','Position removed.','success'));</script>";
    $activeSection = 'positionsSection';
}
if (isset($_POST['savePosition'])) {
    $id    = !empty($_POST['position_id']) ? intval($_POST['position_id']) : null;
    $pname = $conn->real_escape_string(trim($_POST['position_name']));
    $pdesc = $conn->real_escape_string(trim($_POST['description']));
    if ($id) {
        $conn->query("UPDATE tbl_position SET position_name='$pname',description='$pdesc' WHERE position_id=$id");
        echo "<script>document.addEventListener('DOMContentLoaded',()=>Swal.fire('Updated!','Position updated.','success'));</script>";
    } else {
        $conn->query("INSERT INTO tbl_position (position_name,description) VALUES ('$pname','$pdesc')");
        echo "<script>document.addEventListener('DOMContentLoaded',()=>Swal.fire('Added!','Position added.','success'));</script>";
    }
    $activeSection = 'positionsSection';
}

// ── DATA FETCH ──────────────────────────────────────────────
$candidRes = $conn->query("SELECT * FROM tbl_candidate ORDER BY election_position, candidate_name");
$electRes  = $conn->query("SELECT * FROM tbl_election ORDER BY election_date DESC");
$posRes    = $conn->query("SELECT * FROM tbl_position ORDER BY position_name");

$statCandids = $conn->query("SELECT COUNT(*) c FROM tbl_candidate")->fetch_assoc()['c'];
$statVotes   = $conn->query("SELECT COUNT(*) c FROM vote")->fetch_assoc()['c'];
$statElects  = $conn->query("SELECT COUNT(*) c FROM tbl_election")->fetch_assoc()['c'];
$statPos     = $conn->query("SELECT COUNT(*) c FROM tbl_position")->fetch_assoc()['c'];

$firstName = explode(' ', $_SESSION['fullname'] ?? 'Organizer')[0];
$initials  = strtoupper(substr($_SESSION['fullname'] ?? 'O', 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Organizer Dashboard – Election System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root{--brand:#1a6e4a;--midnight:#163d2a;--sw:260px;--th:64px;}
    *,*::before,*::after{box-sizing:border-box;}
    body{font-family:'Poppins',system-ui,sans-serif;background:#f4f6fb;color:#1f2430;margin:0;}
    .topbar{position:fixed;top:0;left:0;right:0;height:var(--th);background:#fff;border-bottom:1px solid #e5e7ef;display:flex;align-items:center;padding:0 1.5rem;z-index:1030;box-shadow:0 2px 12px rgba(22,61,42,.08);}
    .topbar-brand{font-size:1.05rem;font-weight:700;color:var(--midnight);text-decoration:none;}
    .topbar-brand span{color:var(--brand);}
    .sidebar-toggle{background:none;border:none;cursor:pointer;color:var(--midnight);font-size:1.4rem;margin-right:1rem;padding:4px 8px;border-radius:6px;transition:background .2s;}
    .sidebar-toggle:hover{background:#f0f8f4;}
    .user-badge{width:38px;height:38px;border-radius:50%;background:var(--brand);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.9rem;flex-shrink:0;}
    .sidebar{position:fixed;top:var(--th);left:0;bottom:0;width:var(--sw);background:var(--midnight);z-index:1020;overflow-y:auto;transition:transform .3s cubic-bezier(.4,0,.2,1);display:flex;flex-direction:column;}
    .sidebar.collapsed{transform:translateX(calc(-1 * var(--sw)));}
    .sidebar-lbl{font-size:.65rem;font-weight:600;color:rgba(255,255,255,.35);text-transform:uppercase;letter-spacing:.12em;padding:1.25rem 1.25rem .4rem;}
    .snav{list-style:none;padding:0 .75rem;margin:0;}
    .snav li a{display:flex;align-items:center;gap:.7rem;padding:.58rem .75rem;color:rgba(255,255,255,.72);text-decoration:none;border-radius:8px;font-size:.88rem;font-weight:500;transition:all .18s;}
    .snav li a:hover,.snav li a.act{background:rgba(255,255,255,.1);color:#fff;}
    .snav li a.act{background:var(--brand)!important;color:#fff!important;}
    .snav li a i{font-size:1rem;flex-shrink:0;}
    .sdiv{border-color:rgba(255,255,255,.1);margin:.5rem 1.25rem;}
    .layout-main{margin-top:var(--th);margin-left:var(--sw);min-height:calc(100vh - var(--th));padding:2rem;transition:margin-left .3s;}
    .layout-main.expanded{margin-left:0;}
    .stat-card{border-radius:16px;padding:1.4rem;color:#fff;display:flex;align-items:center;gap:1rem;}
    .stat-icon{width:52px;height:52px;border-radius:12px;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-size:1.5rem;flex-shrink:0;}
    .stat-num{font-size:1.9rem;font-weight:700;line-height:1;}
    .stat-lbl{font-size:.82rem;opacity:.88;margin-top:2px;}
    .g-green{background:linear-gradient(135deg,#1a6e4a,#27ae76);}
    .g-navy{background:linear-gradient(135deg,#163d2a,#2e6e4a);}
    .g-teal{background:linear-gradient(135deg,#0d7377,#14a085);}
    .g-blue{background:linear-gradient(135deg,#1a4a8e,#2e7dd6);}
    .sc{background:#fff;border-radius:16px;box-shadow:0 2px 20px rgba(22,61,42,.07);padding:2rem;}
    .sc-title{font-size:1.2rem;font-weight:700;color:var(--midnight);}
    .table thead th{background:var(--midnight);color:#fff;font-weight:600;font-size:.78rem;text-transform:uppercase;letter-spacing:.06em;border:none;}
    .table-hover tbody tr:hover{background:#f2faf6;}
    .table td,.table th{vertical-align:middle;}
    .sb-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.35);z-index:1015;}
    .sb-overlay.show{display:block;}
    @media(max-width:991px){.layout-main{margin-left:0!important;}.sidebar{transform:translateX(calc(-1 * var(--sw)));}.sidebar.open{transform:translateX(0);}}
  </style>
</head>
<body>

<header class="topbar">
  <button class="sidebar-toggle" id="sidebarToggle"><i class="bi bi-list"></i></button>
  <a class="topbar-brand" href="organizer_dashboard.php">🗳️ Election <span>Organizer</span></a>
  <div class="ms-auto d-flex align-items-center gap-2">
    <div class="user-badge"><?= $initials ?></div>
    <div class="d-none d-sm-block">
      <div style="font-size:.85rem;font-weight:600;"><?= htmlspecialchars($_SESSION['fullname'] ?? 'Organizer') ?></div>
      <div style="font-size:.72rem;color:#888;">Organizer</div>
    </div>
  </div>
</header>

<div class="sb-overlay" id="sbOverlay"></div>

<nav class="sidebar" id="sidebar">
  <div class="sidebar-lbl">Navigation</div>
  <ul class="snav">
    <li><a href="organizer_dashboard.php?section=homeSection" class="<?= $activeSection==='homeSection'?'act':'' ?>"><i class="bi bi-house-door-fill"></i> Dashboard</a></li>
    <li><a href="organizer_dashboard.php?section=candidatesSection" class="<?= $activeSection==='candidatesSection'?'act':'' ?>"><i class="bi bi-person-badge-fill"></i> Candidates</a></li>
    <li><a href="organizer_dashboard.php?section=electionsSection" class="<?= $activeSection==='electionsSection'?'act':'' ?>"><i class="bi bi-collection-fill"></i> Elections</a></li>
    <li><a href="organizer_dashboard.php?section=positionsSection" class="<?= $activeSection==='positionsSection'?'act':'' ?>"><i class="bi bi-list-task"></i> Positions</a></li>
  </ul>
  <hr class="sdiv">
  <ul class="snav mb-3">
    <li><a href="organizer_dashboard.php?logout=1"><i class="bi bi-box-arrow-left"></i> Logout</a></li>
  </ul>
</nav>

<main class="layout-main" id="layoutMain">

  <!-- HOME -->
  <div id="homeSection" <?= $activeSection!=='homeSection'?'style="display:none"':'' ?>>
    <div class="mb-4">
      <h1 style="font-size:1.6rem;font-weight:700;">Welcome, <?= htmlspecialchars($firstName) ?>! 🌿</h1>
      <p class="text-muted mb-0">Manage candidates, elections, and positions from here.</p>
    </div>
    <div class="row g-3 mb-4">
      <div class="col-6 col-xl-3"><div class="stat-card g-green"><div class="stat-icon"><i class="bi bi-person-badge-fill"></i></div><div><div class="stat-num"><?= $statCandids ?></div><div class="stat-lbl">Candidates</div></div></div></div>
      <div class="col-6 col-xl-3"><div class="stat-card g-navy"><div class="stat-icon"><i class="bi bi-collection-fill"></i></div><div><div class="stat-num"><?= $statElects ?></div><div class="stat-lbl">Elections</div></div></div></div>
      <div class="col-6 col-xl-3"><div class="stat-card g-teal"><div class="stat-icon"><i class="bi bi-list-task"></i></div><div><div class="stat-num"><?= $statPos ?></div><div class="stat-lbl">Positions</div></div></div></div>
      <div class="col-6 col-xl-3"><div class="stat-card g-blue"><div class="stat-icon"><i class="bi bi-check2-circle"></i></div><div><div class="stat-num"><?= $statVotes ?></div><div class="stat-lbl">Votes Cast</div></div></div></div>
    </div>
    <div class="sc">
      <h2 class="sc-title mb-1">Quick Access</h2>
      <p class="text-muted small mb-3">Jump to any section quickly.</p>
      <div class="d-flex flex-wrap gap-2">
        <a href="organizer_dashboard.php?section=candidatesSection" class="btn btn-outline-success btn-sm"><i class="bi bi-person-badge me-1"></i>Manage Candidates</a>
        <a href="organizer_dashboard.php?section=electionsSection" class="btn btn-outline-primary btn-sm"><i class="bi bi-collection me-1"></i>Manage Elections</a>
        <a href="organizer_dashboard.php?section=positionsSection" class="btn btn-outline-secondary btn-sm"><i class="bi bi-list-task me-1"></i>Manage Positions</a>
      </div>
    </div>
  </div>

  <!-- CANDIDATES -->
  <div id="candidatesSection" <?= $activeSection!=='candidatesSection'?'style="display:none"':'' ?>>
    <div class="sc">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div><h2 class="sc-title">Manage Candidates</h2><p class="text-muted mb-0 small">Add, edit, or remove election candidates</p></div>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addCandidateModal"><i class="bi bi-plus-circle me-1"></i>Add Candidate</button>
      </div>
      <div class="table-responsive">
        <table class="table table-hover table-bordered align-middle">
          <thead><tr><th>#</th><th>Name</th><th>Party Affiliation</th><th>Position</th><th>Actions</th></tr></thead>
          <tbody>
            <?php if($candidRes && $candidRes->num_rows>0): foreach($candidRes as $c): ?>
            <tr>
              <td><?= $c['candidate_id'] ?></td>
              <td><strong><?= htmlspecialchars($c['candidate_name']) ?></strong></td>
              <td><?= htmlspecialchars($c['party_affiliation'] ?? '-') ?></td>
              <td><span class="badge bg-success rounded-pill px-2"><?= htmlspecialchars($c['election_position'] ?? '-') ?></span></td>
              <td class="text-nowrap">
                <button class="btn btn-sm btn-outline-warning me-1" onclick='editCandidate(<?= json_encode($c) ?>)' data-bs-toggle="modal" data-bs-target="#editCandidateModal"><i class="bi bi-pencil-fill"></i></button>
                <button class="btn btn-sm btn-outline-danger" onclick="confirmDel('dcf<?= $c['candidate_id'] ?>')"><i class="bi bi-trash-fill"></i></button>
                <form id="dcf<?= $c['candidate_id'] ?>" method="POST" action="organizer_dashboard.php" style="display:none"><input type="hidden" name="candidate_id" value="<?= $c['candidate_id'] ?>"><input type="hidden" name="deleteCandidate" value="1"></form>
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

  <!-- ELECTIONS -->
  <div id="electionsSection" <?= $activeSection!=='electionsSection'?'style="display:none"':'' ?>>
    <div class="sc">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div><h2 class="sc-title">Manage Elections</h2><p class="text-muted mb-0 small">Configure elections and schedules</p></div>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addElectionModal"><i class="bi bi-plus-circle me-1"></i>Add Election</button>
      </div>
      <div class="table-responsive">
        <table class="table table-hover table-bordered align-middle">
          <thead><tr><th>#</th><th>Election Name</th><th>Date</th><th>Description</th><th>Actions</th></tr></thead>
          <tbody>
            <?php if($electRes && $electRes->num_rows>0): foreach($electRes as $e): ?>
            <tr>
              <td><?= $e['election_id'] ?></td>
              <td><strong><?= htmlspecialchars($e['election_name']) ?></strong></td>
              <td><?= htmlspecialchars($e['election_date'] ?? '-') ?></td>
              <td><?= htmlspecialchars(mb_substr($e['description']??'',0,60)).(mb_strlen($e['description']??'')>60?'…':'') ?></td>
              <td class="text-nowrap">
                <button class="btn btn-sm btn-outline-warning me-1" onclick='editElection(<?= json_encode($e) ?>)' data-bs-toggle="modal" data-bs-target="#editElectionModal"><i class="bi bi-pencil-fill"></i></button>
                <button class="btn btn-sm btn-outline-danger" onclick="confirmDel('def<?= $e['election_id'] ?>')"><i class="bi bi-trash-fill"></i></button>
                <form id="def<?= $e['election_id'] ?>" method="POST" action="organizer_dashboard.php" style="display:none"><input type="hidden" name="election_id" value="<?= $e['election_id'] ?>"><input type="hidden" name="deleteElection" value="1"></form>
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

  <!-- POSITIONS -->
  <div id="positionsSection" <?= $activeSection!=='positionsSection'?'style="display:none"':'' ?>>
    <div class="sc">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div><h2 class="sc-title">Manage Positions</h2><p class="text-muted mb-0 small">Define election positions</p></div>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addPositionModal"><i class="bi bi-plus-circle me-1"></i>Add Position</button>
      </div>
      <div class="table-responsive">
        <table class="table table-hover table-bordered align-middle">
          <thead><tr><th>#</th><th>Position Name</th><th>Description</th><th>Actions</th></tr></thead>
          <tbody>
            <?php if($posRes && $posRes->num_rows>0): foreach($posRes as $p): ?>
            <tr>
              <td><?= $p['position_id'] ?></td>
              <td><strong><?= htmlspecialchars($p['position_name']) ?></strong></td>
              <td><?= htmlspecialchars($p['description'] ?? '-') ?></td>
              <td class="text-nowrap">
                <button class="btn btn-sm btn-outline-warning me-1" onclick='editPosition(<?= json_encode($p) ?>)' data-bs-toggle="modal" data-bs-target="#editPositionModal"><i class="bi bi-pencil-fill"></i></button>
                <button class="btn btn-sm btn-outline-danger" onclick="confirmDel('dpf<?= $p['position_id'] ?>')"><i class="bi bi-trash-fill"></i></button>
                <form id="dpf<?= $p['position_id'] ?>" method="POST" action="organizer_dashboard.php" style="display:none"><input type="hidden" name="position_id" value="<?= $p['position_id'] ?>"><input type="hidden" name="deletePosition" value="1"></form>
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

</main>

<!-- MODALS -->
<div class="modal fade" id="addCandidateModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
  <div class="modal-header bg-success text-white"><h5 class="modal-title"><i class="bi bi-person-plus me-2"></i>Add Candidate</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
  <form method="POST" action="organizer_dashboard.php"><div class="modal-body">
    <div class="mb-3"><label class="form-label fw-semibold">Candidate Name</label><input type="text" name="candidate_name" class="form-control" required></div>
    <div class="mb-3"><label class="form-label fw-semibold">Party Affiliation</label><input type="text" name="party_affiliation" class="form-control"></div>
    <div class="mb-3"><label class="form-label fw-semibold">Position (e.g. President)</label><input type="text" name="election_position" class="form-control" required></div>
  </div>
  <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" name="saveCandidate" class="btn btn-success"><i class="bi bi-save me-1"></i>Save</button></div>
  </form>
</div></div></div>

<div class="modal fade" id="editCandidateModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
  <div class="modal-header bg-warning"><h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit Candidate</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <form method="POST" action="organizer_dashboard.php"><div class="modal-body">
    <input type="hidden" name="candidate_id" id="ec_id">
    <div class="mb-3"><label class="form-label fw-semibold">Candidate Name</label><input type="text" name="candidate_name" id="ec_name" class="form-control" required></div>
    <div class="mb-3"><label class="form-label fw-semibold">Party Affiliation</label><input type="text" name="party_affiliation" id="ec_party" class="form-control"></div>
    <div class="mb-3"><label class="form-label fw-semibold">Position</label><input type="text" name="election_position" id="ec_pos" class="form-control" required></div>
  </div>
  <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" name="saveCandidate" class="btn btn-warning"><i class="bi bi-save me-1"></i>Update</button></div>
  </form>
</div></div></div>

<div class="modal fade" id="addElectionModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
  <div class="modal-header bg-success text-white"><h5 class="modal-title"><i class="bi bi-collection me-2"></i>Add Election</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
  <form method="POST" action="organizer_dashboard.php"><div class="modal-body">
    <div class="mb-3"><label class="form-label fw-semibold">Election Name</label><input type="text" name="election_name" class="form-control" required></div>
    <div class="mb-3"><label class="form-label fw-semibold">Election Date</label><input type="date" name="election_date" class="form-control"></div>
    <div class="mb-3"><label class="form-label fw-semibold">Description</label><textarea name="description" class="form-control" rows="3"></textarea></div>
  </div>
  <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" name="saveElection" class="btn btn-success"><i class="bi bi-save me-1"></i>Save</button></div>
  </form>
</div></div></div>

<div class="modal fade" id="editElectionModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
  <div class="modal-header bg-warning"><h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit Election</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <form method="POST" action="organizer_dashboard.php"><div class="modal-body">
    <input type="hidden" name="election_id" id="ee_id">
    <div class="mb-3"><label class="form-label fw-semibold">Election Name</label><input type="text" name="election_name" id="ee_name" class="form-control" required></div>
    <div class="mb-3"><label class="form-label fw-semibold">Election Date</label><input type="date" name="election_date" id="ee_date" class="form-control"></div>
    <div class="mb-3"><label class="form-label fw-semibold">Description</label><textarea name="description" id="ee_desc" class="form-control" rows="3"></textarea></div>
  </div>
  <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" name="saveElection" class="btn btn-warning"><i class="bi bi-save me-1"></i>Update</button></div>
  </form>
</div></div></div>

<div class="modal fade" id="addPositionModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
  <div class="modal-header bg-success text-white"><h5 class="modal-title"><i class="bi bi-list-task me-2"></i>Add Position</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
  <form method="POST" action="organizer_dashboard.php"><div class="modal-body">
    <div class="mb-3"><label class="form-label fw-semibold">Position Name</label><input type="text" name="position_name" class="form-control" required></div>
    <div class="mb-3"><label class="form-label fw-semibold">Description</label><textarea name="description" class="form-control" rows="3"></textarea></div>
  </div>
  <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" name="savePosition" class="btn btn-success"><i class="bi bi-save me-1"></i>Save</button></div>
  </form>
</div></div></div>

<div class="modal fade" id="editPositionModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
  <div class="modal-header bg-warning"><h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit Position</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <form method="POST" action="organizer_dashboard.php"><div class="modal-body">
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
function confirmDel(formId){Swal.fire({title:'Are you sure?',text:'This cannot be undone.',icon:'warning',showCancelButton:true,confirmButtonColor:'#d33',cancelButtonColor:'#6c757d',confirmButtonText:'Yes, delete!'}).then(r=>{if(r.isConfirmed)document.getElementById(formId).submit();});}
function editCandidate(c){document.getElementById('ec_id').value=c.candidate_id;document.getElementById('ec_name').value=c.candidate_name||'';document.getElementById('ec_party').value=c.party_affiliation||'';document.getElementById('ec_pos').value=c.election_position||'';}
function editElection(e){document.getElementById('ee_id').value=e.election_id;document.getElementById('ee_name').value=e.election_name||'';document.getElementById('ee_date').value=e.election_date||'';document.getElementById('ee_desc').value=e.description||'';}
function editPosition(p){document.getElementById('ep_id').value=p.position_id;document.getElementById('ep_name').value=p.position_name||'';document.getElementById('ep_desc').value=p.description||'';}
</script>
</body>
</html>
