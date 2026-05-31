<?php
// Include the database connection configuration
require_once 'dbelection.php';

// Start the session to track logged-in user details
session_start();

// --- AUTHENTICATION GUARD ---
// Check if the user is logged in as an Organizer.
// If not authorized, redirect them back to the login page.
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Organizer') {
    header('Location: login.php');
    exit();
}

// --- LOGOUT LOGIC ---
// If the logout query parameter is set, destroy session variables and redirect
if (isset($_GET['logout']) && $_GET['logout'] == '1') {
    session_destroy();
    header('Location: login.php');
    exit();
}

// --- ACTIVE MENU SECTION SELECTION ---
// Decide which tab to display. Default is homeSection.
$activeSection = 'homeSection';
if (isset($_GET['section'])) {
    $section = $_GET['section'];
    if ($section == 'homeSection' || $section == 'candidatesSection' || $section == 'electionsSection' || $section == 'positionsSection') {
        $activeSection = $section;
    }
}

// --- CANDIDATES MANAGEMENT ACTIONS ---

// Action: Delete Candidate
if (isset($_POST['deleteCandidate'])) {
    $id = intval($_POST['candidate_id']);
    
    // Execute SQL delete query
    $conn->query("DELETE FROM tbl_candidate WHERE candidate_id = " . $id);
    
    // Show confirmation dialog using SweetAlert2
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire('Deleted!', 'Candidate removed.', 'success');
        });
    </script>";
    $activeSection = 'candidatesSection';
}

// Action: Save Candidate (Add or Edit)
if (isset($_POST['saveCandidate'])) {
    $id = null;
    if (!empty($_POST['candidate_id'])) {
        $id = intval($_POST['candidate_id']);
    }
    
    $name  = $conn->real_escape_string(trim($_POST['candidate_name']));
    $party = $conn->real_escape_string(trim($_POST['party_affiliation']));
    $pos   = $conn->real_escape_string(trim($_POST['election_position']));
    
    if ($id) {
        // Update existing candidate details
        $conn->query("UPDATE tbl_candidate SET candidate_name = '$name', party_affiliation = '$party', election_position = '$pos' WHERE candidate_id = " . $id);
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire('Updated!', 'Candidate details updated.', 'success');
            });
        </script>";
    } else {
        // Insert new candidate details
        $conn->query("INSERT INTO tbl_candidate (candidate_name, party_affiliation, election_position) VALUES ('$name', '$party', '$pos')");
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire('Added!', 'Candidate added successfully.', 'success');
            });
        </script>";
    }
    
    // Auto-create position in positions table if it doesn't already exist
    if ($pos !== '') {
        $escapedPos = $conn->real_escape_string($pos);
        $checkPos = $conn->query("SELECT * FROM tbl_position WHERE position_name = '$escapedPos'");
        
        if ($checkPos && $checkPos->num_rows == 0) {
            $conn->query("INSERT INTO tbl_position (position_name, description) VALUES ('$escapedPos', 'Auto-created from candidate registration')");
        }
    }
    $activeSection = 'candidatesSection';
}

// --- ELECTIONS MANAGEMENT ACTIONS ---

// Action: Delete Election
if (isset($_POST['deleteElection'])) {
    $id = intval($_POST['election_id']);
    $conn->query("DELETE FROM tbl_election WHERE election_id = " . $id);
    
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire('Deleted!', 'Election removed.', 'success');
        });
    </script>";
    $activeSection = 'electionsSection';
}

// Action: Save Election (Add or Edit)
if (isset($_POST['saveElection'])) {
    $id = null;
    if (!empty($_POST['election_id'])) {
        $id = intval($_POST['election_id']);
    }
    
    $ename = $conn->real_escape_string(trim($_POST['election_name']));
    $edate = $conn->real_escape_string($_POST['election_date']);
    $edesc = $conn->real_escape_string(trim($_POST['description']));
    
    if ($id) {
        // Update existing election details
        $conn->query("UPDATE tbl_election SET election_name = '$ename', election_date = '$edate', description = '$edesc' WHERE election_id = " . $id);
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire('Updated!', 'Election schedule updated.', 'success');
            });
        </script>";
    } else {
        // Insert new election details
        $conn->query("INSERT INTO tbl_election (election_name, election_date, description) VALUES ('$ename', '$edate', '$edesc')");
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire('Added!', 'New election created.', 'success');
            });
        </script>";
    }
    $activeSection = 'electionsSection';
}

// --- POSITIONS MANAGEMENT ACTIONS ---

// Action: Delete Position
if (isset($_POST['deletePosition'])) {
    $id = intval($_POST['position_id']);
    
    // Find name of the position to unlink it from candidates
    $oldPosQuery = $conn->query("SELECT position_name FROM tbl_position WHERE position_id = " . $id);
    $oldPos = '';
    if ($oldPosQuery && $oldPosQuery->num_rows > 0) {
        $oldPosRow = $oldPosQuery->fetch_assoc();
        $oldPos = $oldPosRow['position_name'];
    }
    
    // Execute position deletion
    $conn->query("DELETE FROM tbl_position WHERE position_id = " . $id);
    
    // Update matching candidates to have an empty position
    if ($oldPos !== '') {
        $escapedOldPos = $conn->real_escape_string($oldPos);
        $conn->query("UPDATE tbl_candidate SET election_position = '' WHERE election_position = '" . $escapedOldPos . "'");
    }
    
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire('Deleted!', 'Position removed.', 'success');
        });
    </script>";
    $activeSection = 'positionsSection';
}

// Action: Save Position (Add or Edit)
if (isset($_POST['savePosition'])) {
    $id = null;
    if (!empty($_POST['position_id'])) {
        $id = intval($_POST['position_id']);
    }
    
    $pname = $conn->real_escape_string(trim($_POST['position_name']));
    $pdesc = $conn->real_escape_string(trim($_POST['description']));
    
    if ($id) {
        // Find previous name to update candidate fields too
        $oldPosQuery = $conn->query("SELECT position_name FROM tbl_position WHERE position_id = " . $id);
        $oldPos = '';
        if ($oldPosQuery && $oldPosQuery->num_rows > 0) {
            $oldPosRow = $oldPosQuery->fetch_assoc();
            $oldPos = $oldPosRow['position_name'];
        }
        
        // Update position details
        $conn->query("UPDATE tbl_position SET position_name = '$pname', description = '$pdesc' WHERE position_id = " . $id);
        
        // Propagate position name change to matching candidates
        if ($oldPos !== '') {
            $escapedOldPos = $conn->real_escape_string($oldPos);
            $conn->query("UPDATE tbl_candidate SET election_position = '$pname' WHERE election_position = '" . $escapedOldPos . "'");
        }
        
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire('Updated!', 'Position updated.', 'success');
            });
        </script>";
    } else {
        // Insert new position details
        $conn->query("INSERT INTO tbl_position (position_name, description) VALUES ('$pname', '$pdesc')");
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire('Added!', 'New position defined.', 'success');
            });
        </script>";
    }
    $activeSection = 'positionsSection';
}

// --- FETCH DATA FOR RENDERING ---

// 1. Fetch Candidates List
$candidRes = $conn->query("SELECT * FROM tbl_candidate ORDER BY election_position, candidate_name");

// 2. Fetch Elections List
$electRes  = $conn->query("SELECT * FROM tbl_election ORDER BY election_date DESC");

// 3. Fetch Positions List
$posRes    = $conn->query("SELECT * FROM tbl_position ORDER BY position_name");

// 4. Fetch Stats Counts for Dashboard widgets
$statCandidsRes = $conn->query("SELECT COUNT(*) as count FROM tbl_candidate");
$statCandidsRow = $statCandidsRes->fetch_assoc();
$statCandids = intval($statCandidsRow['count']);

$statVotesRes   = $conn->query("SELECT COUNT(*) as count FROM vote");
$statVotesRow   = $statVotesRes->fetch_assoc();
$statVotes = intval($statVotesRow['count']);

$statElectsRes  = $conn->query("SELECT COUNT(*) as count FROM tbl_election");
$statElectsRow  = $statElectsRes->fetch_assoc();
$statElects = intval($statElectsRow['count']);

$statPosRes     = $conn->query("SELECT COUNT(*) as count FROM tbl_position");
$statPosRow     = $statPosRes->fetch_assoc();
$statPos = intval($statPosRow['count']);

// 5. Setup User Profile info
$fullName = $_SESSION['fullname'] ?? 'Organizer';
$nameParts = explode(' ', $fullName);
$firstName = $nameParts[0];

$initials  = strtoupper(substr($fullName, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Organizer Dashboard – Election System</title>
  
  <!-- CSS Frameworks -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
  
  <!-- External Externalized CSS -->
  <link rel="stylesheet" href="organizer_dashboard.css">
</head>
<body>

<!-- TOP NAVIGATION BAR -->
<header class="topbar">
  <button class="sidebar-toggle" id="sidebarToggle"><i class="bi bi-list"></i></button>
  <a class="topbar-brand" href="organizer_dashboard.php">Election <span>Organizer</span></a>
  <a href="index.php" class="btn btn-outline-secondary btn-sm ms-3"><i class="bi bi-arrow-left me-1"></i> Back to Main</a>
  
  <div class="ms-auto d-flex align-items-center gap-2">
    <div class="user-avatar"><?php echo $initials; ?></div>
    <div class="d-none d-sm-block">
      <div class="topbar-user-name"><?php echo htmlspecialchars($fullName); ?></div>
      <div class="topbar-user-role">Organizer</div>
    </div>
  </div>
</header>

<div class="sidebar-overlay" id="sbOverlay"></div>

<!-- SIDEBAR NAVIGATION MENU -->
<nav class="sidebar" id="sidebar">
  <div class="sidebar-label">Navigation</div>
  <ul class="sidebar-nav">
    <li>
      <a href="organizer_dashboard.php?section=homeSection" class="sidebar-link <?php if ($activeSection === 'homeSection') { echo 'active'; } ?>">
        <i class="bi bi-house-door-fill"></i> Dashboard
      </a>
    </li>
    <li>
      <a href="organizer_dashboard.php?section=candidatesSection" class="sidebar-link <?php if ($activeSection === 'candidatesSection') { echo 'active'; } ?>">
        <i class="bi bi-person-badge-fill"></i> Candidates
      </a>
    </li>
    <li>
      <a href="organizer_dashboard.php?section=electionsSection" class="sidebar-link <?php if ($activeSection === 'electionsSection') { echo 'active'; } ?>">
        <i class="bi bi-collection-fill"></i> Elections
      </a>
    </li>
    <li>
      <a href="organizer_dashboard.php?section=positionsSection" class="sidebar-link <?php if ($activeSection === 'positionsSection') { echo 'active'; } ?>">
        <i class="bi bi-list-task"></i> Positions
      </a>
    </li>
  </ul>
  <hr class="border-secondary mx-3 my-2">
  <ul class="sidebar-nav mb-3">
    <li>
      <a href="organizer_dashboard.php?logout=1" class="sidebar-link">
        <i class="bi bi-box-arrow-left"></i> Logout
      </a>
    </li>
  </ul>
</nav>

<!-- MAIN MAIN CONTENT DISPLAY -->
<main class="layout-main" id="layoutMain">

  <!-- 1. HOME DASHBOARD OVERVIEW SECTION -->
  <?php if ($activeSection == 'homeSection') { ?>
  <div id="homeSection">
    <div class="mb-4">
      <h1 class="h3 fw-bold">Welcome, <?php echo htmlspecialchars($firstName); ?>!</h1>
      <p class="text-muted mb-0">Manage candidates, elections, and positions from here.</p>
    </div>
    
    <div class="row g-3 mb-4">
      <div class="col-md-6 col-lg-3">
        <div class="card bg-success text-white border-0 rounded-1">
          <div class="card-body d-flex align-items-center justify-content-between p-4">
            <div>
              <h6 class="card-subtitle mb-1 text-white-50 small text-uppercase">Candidates</h6>
              <h2 class="card-title mb-0 fw-bold"><?php echo $statCandids; ?></h2>
            </div>
            <i class="bi bi-person-badge-fill fs-1 text-white-50"></i>
          </div>
        </div>
      </div>
      <div class="col-md-6 col-lg-3">
        <div class="card bg-dark text-white border-0 rounded-1">
          <div class="card-body d-flex align-items-center justify-content-between p-4">
            <div>
              <h6 class="card-subtitle mb-1 text-white-50 small text-uppercase">Elections</h6>
              <h2 class="card-title mb-0 fw-bold"><?php echo $statElects; ?></h2>
            </div>
            <i class="bi bi-collection-fill fs-1 text-white-50"></i>
          </div>
        </div>
      </div>
      <div class="col-md-6 col-lg-3">
        <div class="card bg-secondary text-white border-0 rounded-1">
          <div class="card-body d-flex align-items-center justify-content-between p-4">
            <div>
              <h6 class="card-subtitle mb-1 text-white-50 small text-uppercase">Positions</h6>
              <h2 class="card-title mb-0 fw-bold"><?php echo $statPos; ?></h2>
            </div>
            <i class="bi bi-list-task fs-1 text-white-50"></i>
          </div>
        </div>
      </div>
      <div class="col-md-6 col-lg-3">
        <div class="card bg-success text-white border-0 rounded-1">
          <div class="card-body d-flex align-items-center justify-content-between p-4">
            <div>
              <h6 class="card-subtitle mb-1 text-white-50 small text-uppercase">Votes Cast</h6>
              <h2 class="card-title mb-0 fw-bold"><?php echo $statVotes; ?></h2>
            </div>
            <i class="bi bi-check2-circle fs-1 text-white-50"></i>
          </div>
        </div>
      </div>
    </div>
    
    <div class="card border-light shadow-sm">
      <div class="card-body p-4">
        <h5 class="fw-bold mb-1">Quick Access</h5>
        <p class="text-muted small mb-3">Jump to any section quickly.</p>
        <div class="d-flex flex-wrap gap-2">
          <a href="organizer_dashboard.php?section=candidatesSection" class="btn btn-success btn-sm">Manage Candidates</a>
          <a href="organizer_dashboard.php?section=electionsSection" class="btn btn-dark btn-sm">Manage Elections</a>
          <a href="organizer_dashboard.php?section=positionsSection" class="btn btn-secondary btn-sm">Manage Positions</a>
        </div>
      </div>
    </div>
  </div>
  <?php } ?>

  <!-- 2. CANDIDATES LIST SECTION -->
  <?php if ($activeSection == 'candidatesSection') { ?>
  <div id="candidatesSection">
    <div class="card border-light shadow-sm">
      <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <div>
            <h5 class="fw-bold mb-1">Manage Candidates</h5>
            <p class="text-muted mb-0 small">Add, edit, or remove election candidates</p>
          </div>
          <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addCandidateModal">
            <i class="bi bi-plus-circle me-1"></i>Add Candidate
          </button>
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
              <?php if ($candidRes && $candidRes->num_rows > 0) { ?>
                <?php while ($c = $candidRes->fetch_assoc()) { ?>
                <tr>
                  <td><?php echo $c['candidate_id']; ?></td>
                  <td><strong><?php echo htmlspecialchars($c['candidate_name']); ?></strong></td>
                  <td><?php echo htmlspecialchars($c['party_affiliation'] ?? '-'); ?></td>
                  <td><span class="badge bg-success"><?php echo htmlspecialchars($c['election_position'] ?? '-'); ?></span></td>
                  <td class="text-nowrap">
                    <button class="btn btn-sm btn-outline-warning me-1" onclick='editCandidate(<?php echo htmlspecialchars(json_encode($c), ENT_QUOTES, "UTF-8"); ?>)' data-bs-toggle="modal" data-bs-target="#editCandidateModal">
                      <i class="bi bi-pencil-fill"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="confirmDel('dcf<?php echo $c['candidate_id']; ?>')">
                      <i class="bi bi-trash-fill"></i>
                    </button>
                    <form id="dcf<?php echo $c['candidate_id']; ?>" method="POST" action="organizer_dashboard.php" class="form-hidden">
                      <input type="hidden" name="candidate_id" value="<?php echo $c['candidate_id']; ?>">
                      <input type="hidden" name="deleteCandidate" value="1">
                    </form>
                  </td>
                </tr>
                <?php } ?>
              <?php } else { ?>
                <tr>
                  <td colspan="5" class="text-center py-4 text-muted">No candidates found.</td>
                </tr>
              <?php } ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  <?php } ?>

  <!-- 3. ELECTIONS LIST SECTION -->
  <?php if ($activeSection == 'electionsSection') { ?>
  <div id="electionsSection">
    <div class="card border-light shadow-sm">
      <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <div>
            <h5 class="fw-bold mb-1">Manage Elections</h5>
            <p class="text-muted mb-0 small">Configure elections and schedules</p>
          </div>
          <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addElectionModal">
            <i class="bi bi-plus-circle me-1"></i>Add Election
          </button>
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
              <?php if ($electRes && $electRes->num_rows > 0) { ?>
                <?php while ($e = $electRes->fetch_assoc()) { ?>
                <tr>
                  <td><?php echo $e['election_id']; ?></td>
                  <td><strong><?php echo htmlspecialchars($e['election_name']); ?></strong></td>
                  <td><?php echo htmlspecialchars($e['election_date'] ?? '-'); ?></td>
                  <td>
                    <?php 
                    $desc = $e['description'] ?? '';
                    if (mb_strlen($desc) > 60) {
                        $desc = mb_substr($desc, 0, 60) . '...';
                    }
                    echo htmlspecialchars($desc);
                    ?>
                  </td>
                  <td class="text-nowrap">
                    <button class="btn btn-sm btn-outline-warning me-1" onclick='editElection(<?php echo htmlspecialchars(json_encode($e), ENT_QUOTES, "UTF-8"); ?>)' data-bs-toggle="modal" data-bs-target="#editElectionModal">
                      <i class="bi bi-pencil-fill"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="confirmDel('def<?php echo $e['election_id']; ?>')">
                      <i class="bi bi-trash-fill"></i>
                    </button>
                    <form id="def<?php echo $e['election_id']; ?>" method="POST" action="organizer_dashboard.php" class="form-hidden">
                      <input type="hidden" name="election_id" value="<?php echo $e['election_id']; ?>">
                      <input type="hidden" name="deleteElection" value="1">
                    </form>
                  </td>
                </tr>
                <?php } ?>
              <?php } else { ?>
                <tr>
                  <td colspan="5" class="text-center py-4 text-muted">No elections found.</td>
                </tr>
              <?php } ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  <?php } ?>

  <!-- 4. POSITIONS LIST SECTION -->
  <?php if ($activeSection == 'positionsSection') { ?>
  <div id="positionsSection">
    <div class="card border-light shadow-sm">
      <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <div>
            <h5 class="fw-bold mb-1">Manage Positions</h5>
            <p class="text-muted mb-0 small">Define election positions</p>
          </div>
          <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addPositionModal">
            <i class="bi bi-plus-circle me-1"></i>Add Position
          </button>
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
              <?php if ($posRes && $posRes->num_rows > 0) { ?>
                <?php while ($p = $posRes->fetch_assoc()) { ?>
                <tr>
                  <td><?php echo $p['position_id']; ?></td>
                  <td><strong><?php echo htmlspecialchars($p['position_name']); ?></strong></td>
                  <td><?php echo htmlspecialchars($p['description'] ?? '-'); ?></td>
                  <td class="text-nowrap">
                    <button class="btn btn-sm btn-outline-warning me-1" onclick='editPosition(<?php echo htmlspecialchars(json_encode($p), ENT_QUOTES, "UTF-8"); ?>)' data-bs-toggle="modal" data-bs-target="#editPositionModal">
                      <i class="bi bi-pencil-fill"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="confirmDel('dpf<?php echo $p['position_id']; ?>')">
                      <i class="bi bi-trash-fill"></i>
                    </button>
                    <form id="dpf<?php echo $p['position_id']; ?>" method="POST" action="organizer_dashboard.php" class="form-hidden">
                      <input type="hidden" name="position_id" value="<?php echo $p['position_id']; ?>">
                      <input type="hidden" name="deletePosition" value="1">
                    </form>
                  </td>
                </tr>
                <?php } ?>
              <?php } else { ?>
                <tr>
                  <td colspan="4" class="text-center py-4 text-muted">No positions found.</td>
                </tr>
              <?php } ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  <?php } ?>

</main>

<!-- MODAL FOR ADDING CANDIDATE -->
<div class="modal fade" id="addCandidateModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i>Add Candidate</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="organizer_dashboard.php">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label fw-semibold">Candidate Name</label>
            <input type="text" name="candidate_name" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Party Affiliation</label>
            <input type="text" name="party_affiliation" class="form-control">
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Position (e.g. President)</label>
            <input type="text" name="election_position" class="form-control" list="positionsList" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" name="saveCandidate" class="btn btn-success"><i class="bi bi-save me-1"></i>Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- MODAL FOR EDITING CANDIDATE -->
<div class="modal fade" id="editCandidateModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-warning">
        <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit Candidate</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="organizer_dashboard.php">
        <div class="modal-body">
          <input type="hidden" name="candidate_id" id="ec_id">
          <div class="mb-3">
            <label class="form-label fw-semibold">Candidate Name</label>
            <input type="text" name="candidate_name" id="ec_name" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Party Affiliation</label>
            <input type="text" name="party_affiliation" id="ec_party" class="form-control">
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Position</label>
            <input type="text" name="election_position" id="ec_pos" class="form-control" list="positionsList" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" name="saveCandidate" class="btn btn-warning"><i class="bi bi-save me-1"></i>Update</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- DATALIST SOURCE FOR POSITIONS AUTOCALC -->
<datalist id="positionsList">
  <?php 
  $datalistRes = $conn->query("SELECT position_name FROM tbl_position ORDER BY position_name");
  if ($datalistRes) {
      while ($dlRow = $datalistRes->fetch_assoc()) {
          echo '<option value="' . htmlspecialchars($dlRow['position_name']) . '">';
      }
  }
  ?>
</datalist>

<!-- MODAL FOR ADDING ELECTION -->
<div class="modal fade" id="addElectionModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title"><i class="bi bi-collection me-2"></i>Add Election</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="organizer_dashboard.php">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label fw-semibold">Election Name</label>
            <input type="text" name="election_name" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Election Date</label>
            <input type="date" name="election_date" class="form-control">
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Description</label>
            <textarea name="description" class="form-control" rows="3"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" name="saveElection" class="btn btn-success"><i class="bi bi-save me-1"></i>Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- MODAL FOR EDITING ELECTION -->
<div class="modal fade" id="editElectionModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-warning">
        <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit Election</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="organizer_dashboard.php">
        <div class="modal-body">
          <input type="hidden" name="election_id" id="ee_id">
          <div class="mb-3">
            <label class="form-label fw-semibold">Election Name</label>
            <input type="text" name="election_name" id="ee_name" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Election Date</label>
            <input type="date" name="election_date" id="ee_date" class="form-control">
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Description</label>
            <textarea name="description" id="ee_desc" class="form-control" rows="3"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" name="saveElection" class="btn btn-warning"><i class="bi bi-save me-1"></i>Update</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- MODAL FOR ADDING POSITION -->
<div class="modal fade" id="addPositionModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title"><i class="bi bi-list-task me-2"></i>Add Position</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="organizer_dashboard.php">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label fw-semibold">Position Name</label>
            <input type="text" name="position_name" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Description</label>
            <textarea name="description" class="form-control" rows="3"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" name="savePosition" class="btn btn-success"><i class="bi bi-save me-1"></i>Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- MODAL FOR EDITING POSITION -->
<div class="modal fade" id="editPositionModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-warning">
        <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit Position</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="organizer_dashboard.php">
        <div class="modal-body">
          <input type="hidden" name="position_id" id="ep_id">
          <div class="mb-3">
            <label class="form-label fw-semibold">Position Name</label>
            <input type="text" name="position_name" id="ep_name" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Description</label>
            <textarea name="description" id="ep_desc" class="form-control" rows="3"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" name="savePosition" class="btn btn-warning"><i class="bi bi-save me-1"></i>Update</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- JS Frameworks & Libraries -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// --- SIDEBAR TOGGLE LOGIC ---
var sidebar = document.getElementById('sidebar');
var main = document.getElementById('layoutMain');
var overlay = document.getElementById('sbOverlay');
var sidebarToggle = document.getElementById('sidebarToggle');

// Helper to determine if we are in mobile width
function isMobileView() {
    return window.innerWidth < 992;
}

// Adjust sidebar visibility state based on screen size
function syncSidebar() {
    if (!isMobileView()) {
        // Desktop: Always show sidebar
        sidebar.classList.remove('open');
        sidebar.classList.remove('collapsed');
        main.classList.remove('expanded');
    } else {
        // Mobile: Hide sidebar by default
        sidebar.classList.remove('open');
        main.classList.add('expanded');
    }
}

// Set initial view state
syncSidebar();

// Monitor window resizes to sync desktop/mobile states
window.addEventListener('resize', function() {
    syncSidebar();
});

// Sidebar menu button click handler
sidebarToggle.addEventListener('click', function() {
    if (isMobileView()) {
        // Mobile layout: slide overlay sidebar
        if (sidebar.classList.contains('open')) {
            sidebar.classList.remove('open');
            overlay.classList.remove('show');
        } else {
            sidebar.classList.add('open');
            overlay.classList.add('show');
        }
    } else {
        // Desktop layout: shift page contents width
        if (sidebar.classList.contains('collapsed')) {
            sidebar.classList.remove('collapsed');
            main.classList.remove('expanded');
        } else {
            sidebar.classList.add('collapsed');
            main.classList.add('expanded');
        }
    }
});

// Hide sidebar when clicking mobile shadow overlay background
overlay.addEventListener('click', function() {
    sidebar.classList.remove('open');
    overlay.classList.remove('show');
});


// --- DELETE CONFIRMATION ---
function confirmDel(formId) {
    Swal.fire({
        title: 'Are you sure?',
        text: 'This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete!'
    }).then(function(result) {
        if (result.isConfirmed) {
            document.getElementById(formId).submit();
        }
    });
}


// --- EDIT DIALOG FILL ACTIONS ---

// Fill candidate edit modal fields
function editCandidate(c) {
    document.getElementById('ec_id').value = c.candidate_id;
    document.getElementById('ec_name').value = c.candidate_name || '';
    document.getElementById('ec_party').value = c.party_affiliation || '';
    document.getElementById('ec_pos').value = c.election_position || '';
}

// Fill election edit modal fields
function editElection(e) {
    document.getElementById('ee_id').value = e.election_id;
    document.getElementById('ee_name').value = e.election_name || '';
    document.getElementById('ee_date').value = e.election_date || '';
    document.getElementById('ee_desc').value = e.description || '';
}

// Fill position edit modal fields
function editPosition(p) {
    document.getElementById('ep_id').value = p.position_id;
    document.getElementById('ep_name').value = p.position_name || '';
    document.getElementById('ep_desc').value = p.description || '';
}
</script>
</body>
</html>
