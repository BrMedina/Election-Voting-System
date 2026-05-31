<?php
require_once 'dbelection.php';
session_start();

// Logout from index
if (isset($_GET['logout']) && $_GET['logout'] == '1') {
    session_destroy();
    header('Location: index.php');
    exit();
}

$loggedIn   = isset($_SESSION['user_type']);
$sessionName = $loggedIn ? ($_SESSION['fullname'] ?? 'User') : '';
$sessionRole = $loggedIn ? ($_SESSION['user_type'] ?? '') : '';
$sessionInit = $loggedIn ? strtoupper(substr($sessionName, 0, 1)) : '';

$candidatesByPos = [];
$dbError = '';
$totalCandidates = 0;

function getCandidateImage($candidateName) {
    $dir = 'images/';
    if (!is_dir($dir)) return null;
    $files = @scandir($dir);
    if (!$files) return null;
    $normalizedName = strtolower(trim($candidateName));
    $parts = preg_split('/[\s,\.]+/', $normalizedName);
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        $fileInfo = pathinfo($file);
        $fileName = strtolower($fileInfo['filename']);
        if (in_array($fileName, $parts) || strpos($normalizedName, $fileName) !== false) {
            return $dir . $file;
        }
    }
    
    // Fallback to original last name slug logic
    $cleanName = trim(preg_replace('/\s+/', ' ', $candidateName));
    if ($cleanName !== '') {
        $parts = explode(' ', $cleanName);
        $lastName = end($parts);
        $slug = preg_replace('/[^a-z0-9]/', '', strtolower($lastName));
        $extensions = ['jpg', 'jpeg', 'png', 'webp'];
        foreach ($extensions as $ext) {
            $path = $dir . $slug . '.' . $ext;
            if (file_exists($path)) {
                return $path;
            }
        }
    }
    
    return null;
}

function getPartyColor($party) {
    $map = [
        'Building Leadership & Momentum'           => ['bg' => '#1D4ED8', 'label' => 'BLM'],
        'Celestial ng Pagbabago'                    => ['bg' => '#15803D', 'label' => 'CNP'],
        'Montoya Independent Leadership & Freedom'  => ['bg' => '#B45309', 'label' => 'MILF'],
    ];
    $key = trim($party);
    return isset($map[$key]) ? $map[$key] : ['bg' => '#6B7280', 'label' => 'IND'];
}

$query = "SELECT candidate_id, candidate_name, party_affiliation, election_position FROM tbl_candidate ORDER BY election_position, candidate_name";
$result = $conn->query($query);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $pos = $row['election_position'] ?? 'Other';
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
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
  <style>
    :root {
      --brand-red: #b52232;
      --brand-gray: #6c757d;
      --brand-dark: #212529;
      --brand-light: #f8f9fa;
    }
    body {
      font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
      background-color: var(--brand-light);
      color: #212529;
    }
    .topbar {
      border-bottom: 3px solid var(--brand-red);
    }
    .navbar-brand span {
      color: var(--brand-red);
      font-weight: 700;
    }
    .hero-section {
      background-color: var(--brand-dark);
      color: #ffffff;
      border-left: 8px solid var(--brand-red);
      padding: 3.5rem 2rem;
      margin-bottom: 2rem;
    }
    .section-title {
      font-weight: 700;
      border-bottom: 2px solid var(--brand-red);
      padding-bottom: 0.5rem;
      margin-bottom: 1.5rem;
      color: var(--brand-dark);
      text-transform: uppercase;
      font-size: 1.25rem;
      letter-spacing: 0.05em;
    }
    .candidate-row-card {
      background-color: #ffffff;
      border: 1px solid #dee2e6;
      border-radius: 4px;
      transition: border-color 0.15s;
    }
    .candidate-row-card:hover {
      border-color: var(--brand-red);
    }
    .cand-photo-frame {
      width: 120px;
      height: 120px;
      background-color: #e9ecef;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
      border-radius: 4px;
      border: 1px solid #dee2e6;
    }
    .cand-photo-frame img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
    .cand-avatar-fallback {
      width: 100%;
      height: 100%;
      background-color: var(--brand-gray);
      color: #ffffff;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      font-size: 2rem;
    }
  </style>
</head>
<body>

  <!-- NAVBAR -->
  <nav class="navbar navbar-expand-lg navbar-light bg-white topbar shadow-sm">
    <div class="container">
      <a class="navbar-brand fw-bold fs-4" href="index.php">Election <span>Portal</span></a>
      <div class="ms-auto d-flex align-items-center gap-3">
        <?php if ($loggedIn): ?>
          <!-- Vote Now / Dashboard CTA -->
          <?php if ($sessionRole === 'Voters' || $sessionRole === 'Voter'): ?>
            <a class="btn btn-danger text-white px-4 fw-bold" href="voter_dashboard.php"><i class="bi bi-check-square me-2"></i>Vote Now!</a>
          <?php elseif ($sessionRole === 'Admin'): ?>
            <a class="btn btn-outline-secondary btn-sm" href="admin_dashboard.php"><i class="bi bi-speedometer2 me-1"></i>Dashboard</a>
          <?php elseif ($sessionRole === 'Organizer'): ?>
            <a class="btn btn-outline-secondary btn-sm" href="organizer_dashboard.php"><i class="bi bi-speedometer2 me-1"></i>Dashboard</a>
          <?php endif; ?>
          <!-- Account dropdown -->
          <div class="dropdown">
            <button class="btn p-0 border-0 bg-transparent d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="cursor:pointer;">
              <div style="width:34px; height:34px; border-radius:50%; background-color:#b52232; color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:0.88rem; flex-shrink:0;"><?= htmlspecialchars($sessionInit) ?></div>
              <div class="d-none d-sm-block text-start" style="line-height:1.2;">
                <div style="font-size:0.85rem; font-weight:600; color:#212529;"><?= htmlspecialchars($sessionName) ?></div>
                <div style="font-size:0.72rem; color:#6c757d;"><?= htmlspecialchars($sessionRole) ?></div>
              </div>
              <i class="bi bi-chevron-down text-muted" style="font-size:0.75rem;"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm border" style="min-width:180px; font-size:0.88rem;">
              <li><span class="dropdown-item-text text-muted" style="font-size:0.75rem; padding:0.4rem 1rem 0.2rem;">Signed in as</span></li>
              <li><span class="dropdown-item-text fw-semibold" style="padding:0 1rem 0.5rem; font-size:0.88rem; color:#212529;"><?= htmlspecialchars($sessionName) ?></span></li>
              <li><hr class="dropdown-divider my-1"></li>
              <li><a class="dropdown-item text-danger" href="index.php?logout=1"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
            </ul>
          </div>
        <?php else: ?>
          <!-- Not logged in -->
          <a class="btn btn-danger text-white px-4 fw-bold" href="voter_dashboard.php"><i class="bi bi-check-square me-2"></i>Vote Now!</a>
          <a class="btn btn-outline-secondary btn-sm" href="login.php"><i class="bi bi-shield-lock me-1"></i>Login</a>
        <?php endif; ?>
      </div>
    </div>
  </nav>

  <!-- HERO SECTION -->
  <div class="container mt-4">
    <div class="hero-section rounded-1">
      <div class="row align-items-center">
        <div class="col-lg-8">
          <span class="badge bg-danger mb-2 text-uppercase fw-bold" style="letter-spacing: 0.1em;">Official Election Coverage</span>
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
            <ul class="list-unstyled mb-0 text-white-50 small" style="line-height: 1.6;">
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
    <h2 class="section-title">Official Ballot Candidates (<?= $totalCandidates ?> Registered)</h2>
    
    <?php if ($dbError): ?>
      <div class="alert alert-danger" role="alert">
        Unable to load ballot: <?= htmlspecialchars($dbError) ?>
      </div>
    <?php elseif (empty($candidatesByPos)): ?>
      <div class="alert alert-secondary text-center py-5" role="alert">
        <i class="bi bi-inbox fs-2 d-block mb-3"></i>
        No registered candidates found on the ballot yet.
      </div>
    <?php else: foreach ($candidatesByPos as $pos => $cands): ?>
      
      <div class="mb-4">
        <h4 class="fw-bold mb-3 text-dark border-start border-4 border-danger ps-2" style="font-size: 1.15rem;"><?= htmlspecialchars($pos) ?> Candidates</h4>
        <div class="row g-3">
          <?php foreach ($cands as $c): 
            $photoPath = getCandidateImage($c['candidate_name']);
            $init = strtoupper(substr($c['candidate_name'], 0, 1));
          ?>
            <div class="col-12">
              <div class="candidate-row-card p-3 shadow-sm">
                <div class="row align-items-center g-3">
                  <!-- Image box -->
                  <div class="col-auto">
                    <div class="cand-photo-frame">
                      <?php if ($photoPath): ?>
                        <img src="<?= htmlspecialchars($photoPath) ?>" alt="Photo of <?= htmlspecialchars($c['candidate_name']) ?>">
                      <?php else: ?>
                        <div class="cand-avatar-fallback"><?= $init ?></div>
                      <?php endif; ?>
                    </div>
                  </div>
                  <!-- Content Box -->
                  <div class="col">
                    <span class="badge bg-danger text-uppercase mb-1" style="font-size: 0.7rem; letter-spacing: 0.05em;"><?= htmlspecialchars($pos) ?></span>
                    <h5 class="fw-bold mb-1 text-dark"><?= htmlspecialchars($c['candidate_name']) ?></h5>
                    <?php $pc = getPartyColor($c['party_affiliation'] ?? 'Independent'); ?>
                    <p class="text-secondary mb-0 small">
                      <span style="display:inline-block; background-color:<?= htmlspecialchars($pc['bg']) ?>; color:#fff; font-size:0.68rem; font-weight:700; letter-spacing:0.06em; padding:2px 8px; border-radius:3px; text-transform:uppercase; margin-right:6px;"><?= htmlspecialchars($pc['label']) ?></span><?= htmlspecialchars($c['party_affiliation'] ?? 'Independent') ?>
                    </p>
                    <div class="text-muted mt-2 small">Candidate ID: <strong>#<?= htmlspecialchars($c['candidate_id']) ?></strong></div>
                  </div>
                  <!-- Action Box -->
                  <div class="col-md-auto text-md-end">
                    <a href="voter_dashboard.php?section=voteSection" class="btn btn-outline-danger fw-bold px-4 py-2"><i class="bi bi-check-circle me-2"></i>Vote for Candidate</a>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
      
    <?php endforeach; endif; ?>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
