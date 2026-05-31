<?php
// Include the database connection configuration
require_once 'dbelection.php';

// Start the session to track logged-in user details
session_start();

// --- LOGOUT LOGIC ---
// If the logout query parameter is set, destroy session variables and redirect
if (isset($_GET['logout']) && $_GET['logout'] == '1') {
    session_destroy();
    header('Location: index.php');
    exit();
}

// --- SETUP SESSION VARIABLES ---
// Check if the user is currently logged in
$loggedIn = false;
if (isset($_SESSION['user_type'])) {
    $loggedIn = true;
}

$sessionName = '';
if ($loggedIn) {
    if (isset($_SESSION['fullname'])) {
        $sessionName = $_SESSION['fullname'];
    } else {
        $sessionName = 'User';
    }
}

$sessionRole = '';
if ($loggedIn) {
    if (isset($_SESSION['user_type'])) {
        $sessionRole = $_SESSION['user_type'];
    }
}

$sessionInit = '';
if ($loggedIn && $sessionName !== '') {
    $sessionInit = strtoupper(substr($sessionName, 0, 1));
}


// --- HELPER FUNCTIONS ---

// Helper function to find a candidate's image in the images/ directory
function getCandidateImage($candidateName) {
    $dir = 'images/';
    if (!is_dir($dir)) {
        return null;
    }
    
    $files = scandir($dir);
    $normalizedName = strtolower(trim($candidateName));
    
    // Loop through each file in the images folder
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        
        $fileInfo = pathinfo($file);
        $fileName = strtolower($fileInfo['filename']);
        
        // Simple search check to see if names match
        if (strpos($normalizedName, $fileName) !== false || strpos($fileName, $normalizedName) !== false) {
            return $dir . $file;
        }
    }
    return null;
}

// Helper function to map a party affiliation to its label and color.
// A clean switch case is perfect for a college project.
function getPartyColor($party) {
    $partyName = trim($party);
    
    switch ($partyName) {
        case 'Building Leadership & Momentum':
            return array('bg' => '#1D4ED8', 'label' => 'BLM');
            
        case 'Celestial ng Pagbabago':
            return array('bg' => '#15803D', 'label' => 'CNP');
            
        case 'Montoya Independent Leadership & Freedom':
            return array('bg' => '#B45309', 'label' => 'MILF');
            
        default:
            return array('bg' => '#6B7280', 'label' => 'IND');
    }
}


// --- FETCH CANDIDATES FROM DATABASE ---
$candidatesByPos = array();
$dbError = '';
$totalCandidates = 0;

$query = "SELECT candidate_id, candidate_name, party_affiliation, election_position FROM tbl_candidate ORDER BY election_position, candidate_name";
$result = $conn->query($query);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $pos = $row['election_position'];
        if ($pos == null || $pos == '') {
            $pos = 'Other';
        }
        $candidatesByPos[$pos][] = $row;
        $totalCandidates++;
    }
} else {
    $dbError = $conn->error;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Election Portal – National Voting System</title>
  
  <!-- CSS Frameworks -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
  
  <!-- External Externalized CSS -->
  <link rel="stylesheet" href="index.css">
</head>
<body>

  <!-- NAVBAR -->
  <nav class="navbar navbar-expand-lg navbar-light bg-white topbar shadow-sm">
    <div class="container">
      <a class="navbar-brand fw-bold fs-4" href="index.php">Election <span>Portal</span></a>
      <div class="ms-auto d-flex align-items-center gap-3">
        <?php if ($loggedIn) { ?>
          
          <!-- Primary CTA Button (For Voters only) -->
          <?php if ($sessionRole === 'Voters' || $sessionRole === 'Voter') { ?>
            <a class="btn btn-danger text-white px-4 fw-bold" href="voter_dashboard.php"><i class="bi bi-check-square me-2"></i>Vote Now!</a>
          <?php } ?>
          
          <!-- Logged-in Account dropdown menu -->
          <div class="dropdown">
            <button class="btn p-0 border-0 bg-transparent d-flex align-items-center gap-2 user-profile-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
              <div class="user-profile-avatar"><?php echo htmlspecialchars($sessionInit); ?></div>
              <div class="d-none d-sm-block text-start user-profile-info">
                <div class="user-profile-name"><?php echo htmlspecialchars($sessionName); ?></div>
                <div class="user-profile-role"><?php echo htmlspecialchars($sessionRole); ?></div>
              </div>
              <i class="bi bi-chevron-down text-muted user-profile-chevron"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm border user-dropdown-menu">
              <li><span class="dropdown-item-text text-muted user-dropdown-label">Signed in as</span></li>
              <li><span class="dropdown-item-text fw-semibold user-dropdown-name"><?php echo htmlspecialchars($sessionName); ?></span></li>
              <li><hr class="dropdown-divider my-1"></li>
              
              <!-- Dashboard Link inside dropdown based on user role -->
              <?php if ($sessionRole === 'Voters' || $sessionRole === 'Voter') { ?>
                <li><a class="dropdown-item" href="voter_dashboard.php"><i class="bi bi-speedometer2 me-2"></i>Voter Dashboard</a></li>
              <?php } else if ($sessionRole === 'Admin') { ?>
                <li><a class="dropdown-item" href="admin_dashboard.php"><i class="bi bi-speedometer2 me-2"></i>Admin Dashboard</a></li>
              <?php } else if ($sessionRole === 'Organizer') { ?>
                <li><a class="dropdown-item" href="organizer_dashboard.php"><i class="bi bi-speedometer2 me-2"></i>Organizer Dashboard</a></li>
              <?php } ?>
              
              <li><hr class="dropdown-divider my-1"></li>
              <li><a class="dropdown-item text-danger" href="index.php?logout=1"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
            </ul>
          </div>
          
        <?php } else { ?>
          
          <!-- Not logged in links -->
          <a class="btn btn-danger text-white px-4 fw-bold" href="voter_dashboard.php"><i class="bi bi-check-square me-2"></i>Vote Now!</a>
          <a class="btn btn-outline-secondary btn-sm" href="login.php"><i class="bi bi-shield-lock me-1"></i>Login</a>
          
        <?php } ?>
      </div>
    </div>
  </nav>

  <!-- HERO SECTION -->
  <div class="container mt-4">
    <div class="hero-section rounded-1">
      <div class="row align-items-center">
        <div class="col-lg-8">
          <span class="badge bg-danger mb-2 text-uppercase fw-bold hero-badge">Official Election 2026</span>
          <h1 class="display-5 fw-bold mb-3">National General Elections</h1>
          <p class="lead mb-4">Welcome to the central voting and profiles system. Review the registered candidate lists below, study their party affiliations, and click on their profiles to cast your secure ballot.</p>
          <div class="d-flex flex-wrap gap-2">
            <a href="voter_dashboard.php?section=voteSection" class="btn btn-danger btn-lg px-4 fw-bold"><i class="bi bi-check-circle me-2"></i>Access Voting Ballot</a>
            <a href="login.php" class="btn btn-outline-light btn-lg px-4"><i class="bi bi-shield-lock me-2"></i>Admin/Organizer Login</a>
          </div>
        </div>
        <div class="col-lg-4 d-none d-lg-block">
          <div class="p-4 bg-white bg-opacity-10 rounded border border-white border-opacity-25">
            <h5 class="fw-bold mb-3 text-white"><i class="bi bi-info-circle-fill me-2 text-danger"></i>Election Guidelines</h5>
            <ul class="list-unstyled mb-0 text-white-50 small hero-guidelines-list">
              <li class="mb-2"><i class="bi bi-chevron-right me-1 text-danger"></i> Review candidates grouped by position.</li>
              <li class="mb-2"><i class="bi bi-chevron-right me-1 text-danger"></i> Voters are permitted 1 vote per position.</li>
              <li class="mb-2"><i class="bi bi-chevron-right me-1 text-danger"></i> Cast ballots cannot be modified after submission.</li>
              <li class="mb-0"><i class="bi bi-chevron-right me-1 text-danger"></i> Live tallies are public under results.</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- CANDIDATE BALLOT LISTING -->
  <div class="container mb-5">
    <h2 class="section-title">Official Ballot Candidates (<?php echo $totalCandidates; ?> Registered)</h2>
    
    <?php if ($dbError) { ?>
      <div class="alert alert-danger" role="alert">
        Unable to load ballot: <?php echo htmlspecialchars($dbError); ?>
      </div>
    <?php } else if (empty($candidatesByPos)) { ?>
      <div class="alert alert-secondary text-center py-5" role="alert">
        <i class="bi bi-inbox fs-2 d-block mb-3"></i>
        No registered candidates found on the ballot yet.
      </div>
    <?php } else { ?>
      <?php foreach ($candidatesByPos as $pos => $cands) { ?>
        
        <div class="mb-4">
          <h4 class="fw-bold mb-3 text-dark border-start border-4 border-danger ps-2 position-header"><?php echo htmlspecialchars($pos); ?> Candidates</h4>
          <div class="row g-3">
            <?php foreach ($cands as $c) { 
              $photoPath = getCandidateImage($c['candidate_name']);
              $init = strtoupper(substr($c['candidate_name'], 0, 1));
            ?>
              <div class="col-12">
                <div class="candidate-row-card p-3 shadow-sm">
                  <div class="row align-items-center g-3">
                    
                    <!-- Candidate Photo / Avatar box -->
                    <div class="col-auto">
                      <div class="cand-photo-frame">
                        <?php if ($photoPath) { ?>
                          <img src="<?php echo htmlspecialchars($photoPath); ?>" alt="Photo of <?php echo htmlspecialchars($c['candidate_name']); ?>">
                        <?php } else { ?>
                          <div class="cand-avatar-fallback"><?php echo $init; ?></div>
                        <?php } ?>
                      </div>
                    </div>
                    
                    <!-- Candidate Information Box -->
                    <div class="col">
                      <span class="badge bg-danger text-uppercase mb-1 candidate-badge"><?php echo htmlspecialchars($pos); ?></span>
                      <h5 class="fw-bold mb-1 text-dark"><?php echo htmlspecialchars($c['candidate_name']); ?></h5>
                      <?php $pc = getPartyColor($c['party_affiliation'] ?? 'Independent'); ?>
                      <p class="text-secondary mb-0 small">
                        <span class="party-badge" style="background-color: <?php echo htmlspecialchars($pc['bg']); ?>;"><?php echo htmlspecialchars($pc['label']); ?></span>
                        <?php echo htmlspecialchars($c['party_affiliation'] ?? 'Independent'); ?>
                      </p>
                      <div class="text-muted mt-2 small">Candidate ID: <strong>#<?php echo htmlspecialchars($c['candidate_id']); ?></strong></div>
                    </div>
                    
                    <!-- Quick Vote Action Button -->
                    <div class="col-md-auto text-md-end">
                      <a href="voter_dashboard.php?section=voteSection" class="btn btn-outline-danger fw-bold px-4 py-2"><i class="bi bi-check-circle me-2"></i>Vote for Candidate</a>
                    </div>
                    
                  </div>
                </div>
              </div>
            <?php } ?>
          </div>
        </div>
        
      <?php } ?>
    <?php } ?>
  </div>

  <!-- BootStrap JavaScript Bundler -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
