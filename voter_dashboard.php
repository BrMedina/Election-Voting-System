<?php
// Include the database connection file
require_once 'dbelection.php';

// Start the session to access logged-in user details
session_start();

// --- AUTHENTICATION GUARD ---
// Check if the user is logged in and check if they are a Voter/Voters.
// If they are not authorized, redirect them back to the login page immediately.
if (!isset($_SESSION['user_type']) || ($_SESSION['user_type'] !== 'Voters' && $_SESSION['user_type'] !== 'Voter')) {
    header('Location: login.php');
    exit();
}

// --- LOGOUT LOGIC ---
// If the logout query parameter is set, destroy the session and redirect to login
if (isset($_GET['logout']) && $_GET['logout'] == '1') {
    session_destroy();
    header('Location: login.php');
    exit();
}

function resolveVoterId($conn, $sessionUserId, $sessionFullName, $accountId = 0) {
  if ($sessionUserId > 0) {
    $checkVoter = $conn->query("SELECT voter_id FROM tbl_voter WHERE voter_id = " . $sessionUserId . " LIMIT 1");
    if ($checkVoter && $checkVoter->num_rows === 1) {
      return $sessionUserId;
    }
  }

  $userRow = null;
  if ($accountId > 0) {
    $userLookup = $conn->query("SELECT user_id, full_name, phone, date_of_birth, gender FROM tbl_users WHERE user_id = " . $accountId . " LIMIT 1");
    if ($userLookup && $userLookup->num_rows === 1) {
      $userRow = $userLookup->fetch_assoc();
      $_SESSION['account_id'] = (int)$userRow['user_id'];
    }
  } elseif (!empty($sessionFullName)) {
    $escapedFullName = $conn->real_escape_string($sessionFullName);
    $userLookup = $conn->query("SELECT user_id, full_name, phone, date_of_birth, gender FROM tbl_users WHERE full_name = '" . $escapedFullName . "' LIMIT 1");
    if ($userLookup && $userLookup->num_rows === 1) {
      $userRow = $userLookup->fetch_assoc();
      $_SESSION['account_id'] = (int)$userRow['user_id'];
    }
  }

  if ($userRow) {
    $lookupName = $conn->real_escape_string($userRow['full_name']);
    $lookupPhone = $conn->real_escape_string(trim((string)($userRow['phone'] ?? '')));
    $lookupDob = $conn->real_escape_string((string)($userRow['date_of_birth'] ?? ''));
    $lookupGender = $conn->real_escape_string((string)($userRow['gender'] ?? ''));

    $voterLookup = $conn->query("SELECT voter_id FROM tbl_voter WHERE voter_name = '" . $lookupName . "' AND contact_information = '" . $lookupPhone . "' LIMIT 1");
    if ($voterLookup && $voterLookup->num_rows === 1) {
      $voterRow = $voterLookup->fetch_assoc();
      return (int)$voterRow['voter_id'];
    }

    $insertVoter = "INSERT INTO tbl_voter (voter_name, date_of_birth, gender, contact_information) VALUES ('" . $lookupName . "', '" . $lookupDob . "', '" . $lookupGender . "', '" . $lookupPhone . "')";
    if ($conn->query($insertVoter)) {
      return (int)$conn->insert_id;
    }
  }

  return 0;
}

// Set up basic variables for the logged-in voter
$accountId = intval($_SESSION['account_id'] ?? 0);
$fullName  = $_SESSION['fullname'] ?? 'Voter';
$userId    = resolveVoterId($conn, intval($_SESSION['id'] ?? 0), $fullName, $accountId);

if ($userId <= 0) {
  session_destroy();
  header('Location: login.php');
  exit();
}

// Get the voter's first name by splitting the full name by space
$nameParts = explode(' ', $fullName);
$firstName = $nameParts[0];

// Get the first letter of the name to use as a user avatar icon
$initials  = strtoupper(substr($fullName, 0, 1));


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

// Helper function to get color and abbreviation for a party.
// A classic switch case is perfect for a college project.
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


// --- ACTIVE MENU SECTION SELECTION ---
// Decide which page tab to show based on the section GET parameter.
// Default to the candidates listing tab if none or invalid is provided.
$activeSection = 'candidatesSection';
if (isset($_GET['section'])) {
    $section = $_GET['section'];
    if ($section == 'candidatesSection' || $section == 'voteSection' || $section == 'myVoteSection' || $section == 'resultsSection') {
        $activeSection = $section;
    }
}


// --- SUBMIT VOTES FORM ACTION ---
$voteMsg = '';
if (isset($_POST['submitVotes'])) {
    // 1. Get all candidate positions from database
    $positionsQuery = "SELECT DISTINCT election_position FROM tbl_candidate WHERE election_position IS NOT NULL AND election_position != '' ORDER BY election_position";
    $positionsRes = $conn->query($positionsQuery);
    
    $positions = array();
    while ($row = $positionsRes->fetch_assoc()) {
        $positions[] = $row['election_position'];
    }

    // 2. Fetch the positions this voter has already voted for in the database
    $alreadyVotedQuery = "SELECT c.election_position FROM vote v JOIN tbl_candidate c ON v.candidate_id = c.candidate_id WHERE v.voter_id = " . $userId;
    $alreadyVotedRes = $conn->query($alreadyVotedQuery);
    
    $alreadyVotedPositions = array();
    while ($row = $alreadyVotedRes->fetch_assoc()) {
        $alreadyVotedPositions[] = $row['election_position'];
    }

    $inserted = 0;
    $skipped  = 0;
    
    // 3. Process each candidate position
    foreach ($positions as $pos) {
        // Build the HTML input field name (replacing special characters with underscores)
        $fieldName = 'vote_' . preg_replace('/[^a-zA-Z0-9]/', '_', $pos);
        
        if (isset($_POST[$fieldName]) && !empty($_POST[$fieldName])) {
            // Check if they already voted for this position
            if (in_array($pos, $alreadyVotedPositions)) {
                $skipped++;
                continue;
            }
            
            $candidateId = intval($_POST[$fieldName]);
            
            // Verify that this candidate actually belongs to this position
            $escapedPos = $conn->real_escape_string($pos);
            $checkQuery = "SELECT candidate_id FROM tbl_candidate WHERE candidate_id = " . $candidateId . " AND election_position = '" . $escapedPos . "'";
            $check = $conn->query($checkQuery);
            
            if ($check && $check->num_rows > 0) {
                // Safe query insert into database
                $insertQuery = "INSERT INTO vote (voter_id, candidate_id, vote_timestamp) VALUES (" . $userId . ", " . $candidateId . ", NOW())";
                $conn->query($insertQuery);
                $inserted++;
            }
        }
    }

    // 4. Output success or warning alerts to the voter using SweetAlert2
    if ($inserted > 0) {
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: 'Vote Submitted!',
                    text: 'You voted for " . $inserted . " position(s).',
                    icon: 'success',
                    confirmButtonColor: '#0d6efd'
                }).then(function() {
                    window.location.href = 'voter_dashboard.php?section=myVoteSection';
                });
            });
        </script>";
    } elseif ($skipped > 0) {
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: 'Already Voted',
                    text: 'You have already voted for all selected positions.',
                    icon: 'info',
                    confirmButtonColor: '#0d6efd'
                });
            });
        </script>";
    } else {
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: 'No Selection',
                    text: 'Please select at least one candidate to vote.',
                    icon: 'warning',
                    confirmButtonColor: '#0d6efd'
                });
            });
        </script>";
    }
    $activeSection = 'myVoteSection';
}


// --- FETCH DATA FOR RENDERING ---

// 1. Fetch all candidates and group them by position
$candidatesRes = $conn->query("SELECT * FROM tbl_candidate ORDER BY election_position, candidate_name");
$candidatesByPos = array();
if ($candidatesRes) {
    while ($row = $candidatesRes->fetch_assoc()) {
        $pos = $row['election_position'];
        if ($pos == null || $pos == '') {
            $pos = 'Other';
        }
        $candidatesByPos[$pos][] = $row;
    }
}

// 2. Fetch positions the current voter has voted for
$myVotedPosRes = $conn->query("SELECT c.election_position FROM vote v JOIN tbl_candidate c ON v.candidate_id = c.candidate_id WHERE v.voter_id = " . $userId);
$myVotedPositions = array();
if ($myVotedPosRes) {
    while ($row = $myVotedPosRes->fetch_assoc()) {
        $myVotedPositions[] = $row['election_position'];
    }
}

// 3. Fetch summary of user's cast votes
$myVotesQuery = "SELECT c.candidate_name, c.party_affiliation, c.election_position, v.vote_timestamp FROM vote v JOIN tbl_candidate c ON v.candidate_id = c.candidate_id WHERE v.voter_id = " . $userId . " ORDER BY c.election_position";
$myVotesRes = $conn->query($myVotesQuery);

// 4. Fetch overall live voting results count
$resultsQuery = "SELECT c.candidate_name, c.party_affiliation, c.election_position, COUNT(v.vote_id) as votes FROM tbl_candidate c LEFT JOIN vote v ON c.candidate_id = v.candidate_id GROUP BY c.candidate_id ORDER BY c.election_position, votes DESC";
$resultsRes = $conn->query($resultsQuery);

$resultsByPos = array();
$maxByPos = array();
if ($resultsRes) {
    while ($row = $resultsRes->fetch_assoc()) {
        $pos = $row['election_position'];
        if ($pos == null || $pos == '') {
            $pos = 'Other';
        }
        $resultsByPos[$pos][] = $row;
        
        $votesCount = intval($row['votes']);
        // Store maximum votes per position to display percentage bars
        if (!isset($maxByPos[$pos])) {
            $maxByPos[$pos] = 0;
        }
        if ($votesCount > $maxByPos[$pos]) {
            $maxByPos[$pos] = $votesCount;
        }
    }
}

// 5. Fetch total number of votes cast in the election
$totalVotesQuery = "SELECT COUNT(*) as total FROM vote";
$totalVotesRes = $conn->query($totalVotesQuery);
$totalVotesRow = $totalVotesRes->fetch_assoc();
$totalVotes = intval($totalVotesRow['total']);

// 6. Check if user has completed voting for all available categories
$alreadyVotedAll = false;
if (count($myVotedPositions) >= count($candidatesByPos)) {
    $alreadyVotedAll = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Voter Dashboard – Election System</title>
  
  <!-- CSS Frameworks -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
  
  <!-- External Externalized CSS -->
  <link rel="stylesheet" href="voter_dashboard.css">
</head>
<body>

<!-- TOP NAVIGATION BAR -->
<header class="topbar">
  <button class="sidebar-toggle" id="sidebarToggle"><i class="bi bi-list"></i></button>
  <a class="topbar-brand" href="voter_dashboard.php">Election <span>Portal</span></a>
  <a href="index.php" class="btn btn-outline-secondary btn-sm ms-3"><i class="bi bi-arrow-left me-1"></i> Back to Main</a>
  
  <div class="ms-auto d-flex align-items-center gap-2">
    <div class="user-avatar"><?php echo $initials; ?></div>
    <div class="d-none d-sm-block">
      <div class="topbar-user-name"><?php echo htmlspecialchars($fullName); ?></div>
      <div class="topbar-user-role">Voter</div>
    </div>
  </div>
</header>

<div class="sidebar-overlay" id="sbOverlay"></div>

<!-- SIDEBAR NAVIGATION MENU -->
<nav class="sidebar" id="sidebar">
  <div class="sidebar-label">Voter Menu</div>
  <ul class="sidebar-nav">
    <li>
      <a href="voter_dashboard.php?section=candidatesSection" class="sidebar-link <?php if ($activeSection === 'candidatesSection') { echo 'active'; } ?>">
        <i class="bi bi-person-badge-fill"></i> Candidates
      </a>
    </li>
    <li>
      <a href="voter_dashboard.php?section=voteSection" class="sidebar-link <?php if ($activeSection === 'voteSection') { echo 'active'; } ?>">
        <i class="bi bi-check-square-fill"></i> Cast Vote
        <?php if ($alreadyVotedAll && count($candidatesByPos) > 0) { ?>
          <span class="ms-auto badge bg-success badge-done">Done</span>
        <?php } ?>
      </a>
    </li>
    <li>
      <a href="voter_dashboard.php?section=myVoteSection" class="sidebar-link <?php if ($activeSection === 'myVoteSection') { echo 'active'; } ?>">
        <i class="bi bi-card-checklist"></i> My Vote Summary
      </a>
    </li>
    <li>
      <a href="voter_dashboard.php?section=resultsSection" class="sidebar-link <?php if ($activeSection === 'resultsSection') { echo 'active'; } ?>">
        <i class="bi bi-bar-chart-fill"></i> Overall Results
      </a>
    </li>
  </ul>
  <hr class="border-secondary mx-3 my-2">
  <ul class="sidebar-nav mb-3">
    <li>
      <a href="voter_dashboard.php?logout=1" class="sidebar-link">
        <i class="bi bi-box-arrow-left"></i> Logout
      </a>
    </li>
  </ul>
</nav>

<!-- MAIN MAIN CONTENT DISPLAY -->
<main class="layout-main" id="layoutMain">

  <!-- 1. CANDIDATES LIST SECTION -->
  <?php if ($activeSection == 'candidatesSection') { ?>
  <div id="candidatesSection">
    <div class="mb-3">
      <h1 class="h4 fw-bold">Candidate List</h1>
      <p class="text-muted small mb-0">Browse all registered candidates by position.</p>
    </div>
    
    <?php if (empty($candidatesByPos)) { ?>
      <div class="card border-light shadow-sm">
        <div class="card-body p-4">
          <p class="text-muted text-center mb-0 py-4">No candidates registered yet.</p>
        </div>
      </div>
    <?php } else { ?>
      <?php foreach ($candidatesByPos as $pos => $cands) { ?>
        <div class="card border-light shadow-sm mb-3">
          <div class="card-body p-4">
            <h5 class="fw-bold mb-3"><?php echo htmlspecialchars($pos); ?></h5>
            <div class="row g-3">
              <?php foreach ($cands as $c) { 
                $init = strtoupper(substr($c['candidate_name'], 0, 1));
                $img = getCandidateImage($c['candidate_name']);
              ?>
              <div class="col-md-6 col-lg-4">
                <div class="cand-card-simple">
                  <?php if ($img) { ?>
                    <img src="<?php echo htmlspecialchars($img); ?>" class="cand-avatar" alt="">
                  <?php } else { ?>
                    <div class="cand-avatar"><?php echo $init; ?></div>
                  <?php } ?>
                  <div>
                    <div class="cand-name"><?php echo htmlspecialchars($c['candidate_name']); ?></div>
                    <?php $pc = getPartyColor($c['party_affiliation'] ?? 'Independent'); ?>
                    <div class="cand-party">
                      <span class="party-badge" style="background-color: <?php echo htmlspecialchars($pc['bg']); ?>;"><?php echo htmlspecialchars($pc['label']); ?></span>
                      <?php echo htmlspecialchars($c['party_affiliation'] ?? 'Independent'); ?>
                    </div>
                  </div>
                </div>
              </div>
              <?php } ?>
            </div>
          </div>
        </div>
      <?php } ?>
    <?php } ?>
    
    <div class="text-center mt-3">
      <a href="voter_dashboard.php?section=voteSection" class="btn btn-danger btn-lg px-4">
        <i class="bi bi-check-square me-2"></i>Proceed to Vote
      </a>
    </div>
  </div>
  <?php } ?>

  <!-- 2. CAST VOTE SECTION -->
  <?php if ($activeSection == 'voteSection') { ?>
  <div id="voteSection">
    <div class="mb-3">
      <h1 class="h4 fw-bold">Cast Your Vote</h1>
      <p class="text-muted small mb-0">Select one candidate per position. You can only vote once per position.</p>
    </div>
    
    <?php if (empty($candidatesByPos)) { ?>
      <div class="card border-light shadow-sm">
        <div class="card-body p-4">
          <p class="text-muted text-center mb-0 py-4">No candidates available to vote for.</p>
        </div>
      </div>
    <?php } else { ?>
      <form method="POST" action="voter_dashboard.php" id="voteForm">
        <?php foreach ($candidatesByPos as $pos => $cands) {
          $fieldName = 'vote_' . preg_replace('/[^a-zA-Z0-9]/', '_', $pos);
          $alreadyVoted = in_array($pos, $myVotedPositions);
          
          // Get the current user's choice for this position if already voted
          $escapedPos = $conn->real_escape_string($pos);
          $myChoiceQuery = "SELECT c.candidate_name FROM vote v JOIN tbl_candidate c ON v.candidate_id = c.candidate_id WHERE v.voter_id = " . $userId . " AND c.election_position = '" . $escapedPos . "' LIMIT 1";
          $myChoiceRes = $conn->query($myChoiceQuery);
          
          $myChoice = null;
          if ($myChoiceRes && $myChoiceRes->num_rows > 0) {
              $myChoiceRow = $myChoiceRes->fetch_assoc();
              $myChoice = $myChoiceRow['candidate_name'];
          }
        ?>
        <div class="pos-section <?php if ($alreadyVoted) { echo 'border-success'; } ?>">
          <div class="pos-title">
            <?php echo htmlspecialchars($pos); ?>
            <?php if ($alreadyVoted) { ?>
              <span class="badge bg-success ms-auto">
                <i class="bi bi-check-circle me-1"></i>Voted: <?php echo htmlspecialchars($myChoice ?? ''); ?>
              </span>
            <?php } ?>
          </div>
          
          <?php if ($alreadyVoted) { ?>
            <p class="text-muted small mb-0">
              <i class="bi bi-lock me-1"></i>You have already cast your vote for this position.
            </p>
          <?php } else { ?>
            <?php foreach ($cands as $c) {
              $init = strtoupper(substr($c['candidate_name'], 0, 1));
              $img = getCandidateImage($c['candidate_name']);
            ?>
            <label class="cand-card w-100" for="<?php echo $fieldName . '_' . $c['candidate_id']; ?>">
              <input type="radio" name="<?php echo $fieldName; ?>" id="<?php echo $fieldName . '_' . $c['candidate_id']; ?>" value="<?php echo $c['candidate_id']; ?>" class="cand-radio">
              
              <?php if ($img) { ?>
                <img src="<?php echo htmlspecialchars($img); ?>" class="cand-avatar" alt="">
              <?php } else { ?>
                <div class="cand-avatar"><?php echo $init; ?></div>
              <?php } ?>
              
              <div>
                <div class="cand-name"><?php echo htmlspecialchars($c['candidate_name']); ?></div>
                <?php $pc = getPartyColor($c['party_affiliation'] ?? 'Independent'); ?>
                <div class="cand-party">
                  <span class="party-badge" style="background-color: <?php echo htmlspecialchars($pc['bg']); ?>;"><?php echo htmlspecialchars($pc['label']); ?></span>
                  <?php echo htmlspecialchars($c['party_affiliation'] ?? 'Independent'); ?>
                </div>
              </div>
              
              <i class="bi bi-circle ms-auto text-muted check-icon"></i>
            </label>
            <?php } ?>
          <?php } ?>
        </div>
        <?php } ?>

        <?php if (!$alreadyVotedAll) { ?>
          <div class="text-center mt-3">
            <button type="button" class="btn btn-danger btn-lg px-5" onclick="confirmVote()">
              <i class="bi bi-check-circle me-2"></i>Submit My Votes
            </button>
          </div>
        <?php } else { ?>
          <div class="alert alert-success text-center mt-3">
            <i class="bi bi-check-circle-fill me-2"></i>You have completed your voting. Thank you!
            <div class="mt-2">
              <a href="voter_dashboard.php?section=myVoteSection" class="btn btn-success btn-sm">View My Votes</a>
            </div>
          </div>
        <?php } ?>
        <input type="hidden" name="submitVotes" value="1">
      </form>
    <?php } ?>
  </div>
  <?php } ?>

  <!-- 3. MY VOTE SUMMARY SECTION -->
  <?php if ($activeSection == 'myVoteSection') { ?>
  <div id="myVoteSection">
    <div class="card border-light shadow-sm">
      <div class="card-body p-4">
        <div class="mb-4">
          <h5 class="fw-bold mb-1">My Vote Summary</h5>
          <p class="text-muted small mb-0">A record of all votes you have cast.</p>
        </div>
        
        <?php if ($myVotesRes && $myVotesRes->num_rows > 0) { ?>
          <?php while ($mv = $myVotesRes->fetch_assoc()) { 
            $init = strtoupper(substr($mv['candidate_name'], 0, 1));
            $img = getCandidateImage($mv['candidate_name']);
          ?>
          <div class="my-vote-item">
            <?php if ($img) { ?>
              <img src="<?php echo htmlspecialchars($img); ?>" class="cand-avatar" alt="">
            <?php } else { ?>
              <div class="cand-avatar bg-danger text-white"><?php echo $init; ?></div>
            <?php } ?>
            
            <div class="vote-item-info">
              <div class="vote-item-name"><?php echo htmlspecialchars($mv['candidate_name']); ?></div>
              <?php $pc = getPartyColor($mv['party_affiliation'] ?? 'Independent'); ?>
              <div class="vote-item-meta">
                <i class="bi bi-award me-1"></i><?php echo htmlspecialchars($mv['election_position']); ?>
                &nbsp;·&nbsp;
                <span class="party-badge" style="background-color: <?php echo htmlspecialchars($pc['bg']); ?>;"><?php echo htmlspecialchars($pc['label']); ?></span>
                <?php echo htmlspecialchars($mv['party_affiliation'] ?? 'Independent'); ?>
              </div>
            </div>
            
            <div class="vote-item-time"><?php echo htmlspecialchars($mv['vote_timestamp']); ?></div>
            <span class="badge bg-success"><i class="bi bi-check-circle-fill"></i></span>
          </div>
          <?php } ?>
          
          <div class="text-center mt-4">
            <a href="voter_dashboard.php?section=resultsSection" class="btn btn-outline-danger">
              <i class="bi bi-bar-chart me-1"></i>View Overall Results
            </a>
          </div>
        <?php } else { ?>
          <div class="text-center py-5">
            <i class="bi bi-inbox display-4 text-muted d-block mb-3"></i>
            <p class="text-muted">You haven't voted yet.</p>
            <a href="voter_dashboard.php?section=voteSection" class="btn btn-danger">
              <i class="bi bi-check-square me-1"></i>Cast Your Vote
            </a>
          </div>
        <?php } ?>
      </div>
    </div>
  </div>
  <?php } ?>

  <!-- 4. OVERALL RESULTS SECTION -->
  <?php if ($activeSection == 'resultsSection') { ?>
  <div id="resultsSection">
    <div class="mb-3">
      <h1 class="h4 fw-bold">Overall Results</h1>
      <p class="text-muted small mb-0">Live vote tally – <strong><?php echo $totalVotes; ?></strong> total votes cast.</p>
    </div>
    
    <?php if (empty($resultsByPos)) { ?>
      <div class="card border-light shadow-sm">
        <div class="card-body p-4">
          <p class="text-muted text-center mb-0 py-4">No results available yet.</p>
        </div>
      </div>
    <?php } else { ?>
      <?php foreach ($resultsByPos as $pos => $res) { 
        $mx = 1;
        if (isset($maxByPos[$pos])) {
            $mx = $maxByPos[$pos];
        }
        if ($mx <= 0) {
            $mx = 1;
        }
      ?>
        <div class="card border-light shadow-sm mb-3">
          <div class="card-body p-4">
            <h5 class="fw-bold mb-3"><?php echo htmlspecialchars($pos); ?></h5>
            
            <?php foreach ($res as $i => $r) {
              $votes = intval($r['votes']);
              
              // Calculate live percentage of total votes cast
              $pct = 0;
              if ($totalVotes > 0) {
                  $pct = round(($votes / $totalVotes) * 100, 1);
              }
              
              $barPct = round(($votes / $mx) * 100);
              
              // Decide if this candidate is the current leader
              $isWin = false;
              if ($i === 0 && $votes > 0) {
                  $isWin = true;
              }
            ?>
            <div class="result-row">
              <div class="result-name">
                <?php if ($isWin) { ?>
                  <i class="bi bi-trophy-fill text-warning me-1" title="Leading"></i>
                <?php } ?>
                <strong><?php echo htmlspecialchars($r['candidate_name']); ?></strong>
                <div class="result-candidate-party"><?php echo htmlspecialchars($r['party_affiliation'] ?? ''); ?></div>
              </div>
              
              <div class="result-bar-wrap">
                <div class="result-bar" style="width: <?php echo $barPct; ?>%; background-color: <?php echo $isWin ? '#b52232' : '#6c757d'; ?>;"></div>
              </div>
              
              <div class="result-count">
                <strong><?php echo $votes; ?></strong><br>
                <span class="result-pct-text"><?php echo $pct; ?>%</span>
              </div>
            </div>
            <?php } ?>
          </div>
        </div>
      <?php } ?>
    <?php } ?>
  </div>
  <?php } ?>

</main>

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


// --- CANDIDATE SELECTION HIGHLIGHT ---
var candidateCards = document.querySelectorAll('.cand-card');
candidateCards.forEach(function(card) {
    card.addEventListener('click', function() {
        var radio = card.querySelector('.cand-radio');
        if (radio) {
            radio.checked = true;
            
            // Remove selection border highlights from other candidate cards in same position
            var groupName = radio.name;
            var groupRadios = document.querySelectorAll('input[name="' + groupName + '"]');
            
            groupRadios.forEach(function(r) {
                var otherCard = r.closest('.cand-card');
                if (otherCard) {
                    otherCard.classList.remove('selected');
                    
                    var checkIcon = otherCard.querySelector('.check-icon');
                    if (checkIcon) {
                        checkIcon.classList.remove('bi-check-circle-fill');
                        checkIcon.classList.remove('text-danger');
                        checkIcon.classList.add('bi-circle');
                    }
                }
            });
            
            // Highlight current selection card
            card.classList.add('selected');
            
            var cardCheckIcon = card.querySelector('.check-icon');
            if (cardCheckIcon) {
                cardCheckIcon.classList.remove('bi-circle');
                cardCheckIcon.classList.add('bi-check-circle-fill');
                cardCheckIcon.classList.add('text-danger');
            }
        }
    });
});


// --- CONFIRMATION OF CASTING VOTE ---
function confirmVote() {
    var radios = document.querySelectorAll('.cand-radio');
    var anySelected = false;
    
    // Check if at least one candidate radio has been checked
    for (var i = 0; i < radios.length; i++) {
        if (radios[i].checked) {
            anySelected = true;
            break;
        }
    }
    
    if (!anySelected) {
        Swal.fire({
            title: 'No Selection',
            text: 'Please select at least one candidate.',
            icon: 'warning',
            confirmButtonColor: '#b52232'
        });
        return;
    }
    
    // Show SweetAlert confirmation before submitting form
    Swal.fire({
        title: 'Confirm Your Vote',
        text: 'Your votes cannot be changed after submission.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#b52232',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, submit!'
    }).then(function(result) {
        if (result.isConfirmed) {
            document.getElementById('voteForm').submit();
        }
    });
}
</script>
</body>
</html>