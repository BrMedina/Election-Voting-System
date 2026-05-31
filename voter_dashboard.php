<?php
require_once 'dbelection.php';
session_start();

// Auth guard – Voters only
if (!isset($_SESSION['user_type']) || ($_SESSION['user_type'] !== 'Voters' && $_SESSION['user_type'] !== 'Voter')) {
    header('Location: login.php');
    exit();
}

// Logout
if (isset($_GET['logout']) && $_GET['logout'] == '1') {
    session_destroy();
    header('Location: login.php');
    exit();
}

$userId    = intval($_SESSION['id'] ?? 0);
$fullName  = $_SESSION['fullname'] ?? 'Voter';
$firstName = explode(' ', $fullName)[0];
$initials  = strtoupper(substr($fullName, 0, 1));

// Active section
$activeSection = 'candidatesSection';
$validSections = ['candidatesSection','voteSection','myVoteSection','resultsSection'];
if (isset($_GET['section']) && in_array($_GET['section'], $validSections)) {
    $activeSection = $_GET['section'];
}

// ── SUBMIT VOTES ─────────────────────────────────────────────
$voteMsg = '';
if (isset($_POST['submitVotes'])) {
    // Get all positions
    $positionsRes = $conn->query("SELECT DISTINCT election_position FROM tbl_candidate WHERE election_position IS NOT NULL AND election_position != '' ORDER BY election_position");
    $positions = [];
    while ($row = $positionsRes->fetch_assoc()) $positions[] = $row['election_position'];

    // Check already voted positions
    $alreadyVotedRes = $conn->query("SELECT c.election_position FROM vote v JOIN tbl_candidate c ON v.candidate_id = c.candidate_id WHERE v.voter_id = $userId");
    $alreadyVotedPositions = [];
    while ($row = $alreadyVotedRes->fetch_assoc()) $alreadyVotedPositions[] = $row['election_position'];

    $inserted = 0;
    $skipped  = 0;
    foreach ($positions as $pos) {
        $fieldName = 'vote_' . preg_replace('/[^a-zA-Z0-9]/', '_', $pos);
        if (isset($_POST[$fieldName]) && !empty($_POST[$fieldName])) {
            if (in_array($pos, $alreadyVotedPositions)) {
                $skipped++;
                continue;
            }
            $candidateId = intval($_POST[$fieldName]);
            // Verify candidate belongs to this position
            $check = $conn->query("SELECT candidate_id FROM tbl_candidate WHERE candidate_id=$candidateId AND election_position='".$conn->real_escape_string($pos)."'");
            if ($check && $check->num_rows > 0) {
                $conn->query("INSERT INTO vote (voter_id, candidate_id, vote_timestamp) VALUES ($userId, $candidateId, NOW())");
                $inserted++;
            }
        }
    }

    if ($inserted > 0) {
        echo "<script>document.addEventListener('DOMContentLoaded',()=>Swal.fire({title:'Vote Submitted!',text:'You voted for $inserted position(s).',icon:'success',confirmButtonColor:'#1a4a8e'}).then(()=>{window.location.href='voter_dashboard.php?section=myVoteSection';}));</script>";
    } elseif ($skipped > 0) {
        echo "<script>document.addEventListener('DOMContentLoaded',()=>Swal.fire({title:'Already Voted',text:'You have already voted for all selected positions.',icon:'info',confirmButtonColor:'#1a4a8e'}));</script>";
    } else {
        echo "<script>document.addEventListener('DOMContentLoaded',()=>Swal.fire({title:'No Selection',text:'Please select at least one candidate to vote.',icon:'warning',confirmButtonColor:'#1a4a8e'}));</script>";
    }
    $activeSection = 'myVoteSection';
}

// ── DATA FETCH ───────────────────────────────────────────────
// Candidates grouped by position
$candidatesRes = $conn->query("SELECT * FROM tbl_candidate ORDER BY election_position, candidate_name");
$candidatesByPos = [];
if ($candidatesRes) {
    while ($row = $candidatesRes->fetch_assoc()) {
        $pos = $row['election_position'] ?? 'Other';
        $candidatesByPos[$pos][] = $row;
    }
}

// Positions the user has already voted for
$myVotedPosRes = $conn->query("SELECT c.election_position FROM vote v JOIN tbl_candidate c ON v.candidate_id=c.candidate_id WHERE v.voter_id=$userId");
$myVotedPositions = [];
if ($myVotedPosRes) {
    while ($row = $myVotedPosRes->fetch_assoc()) $myVotedPositions[] = $row['election_position'];
}

// My vote details
$myVotesRes = $conn->query("SELECT c.candidate_name, c.party_affiliation, c.election_position, v.vote_timestamp FROM vote v JOIN tbl_candidate c ON v.candidate_id=c.candidate_id WHERE v.voter_id=$userId ORDER BY c.election_position");

// Overall results – live count from vote table
$resultsRes = $conn->query("SELECT c.candidate_name, c.party_affiliation, c.election_position, COUNT(v.vote_id) as votes FROM tbl_candidate c LEFT JOIN vote v ON c.candidate_id=v.candidate_id GROUP BY c.candidate_id ORDER BY c.election_position, votes DESC");
$resultsByPos = [];
$maxByPos = [];
if ($resultsRes) {
    while ($row = $resultsRes->fetch_assoc()) {
        $pos = $row['election_position'] ?? 'Other';
        $resultsByPos[$pos][] = $row;
        $maxByPos[$pos] = max($maxByPos[$pos] ?? 0, intval($row['votes']));
    }
}

$totalVotes = $conn->query("SELECT COUNT(*) c FROM vote")->fetch_assoc()['c'];
$alreadyVotedAll = count($myVotedPositions) >= count($candidatesByPos);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Voter Dashboard – Election System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root{--brand:#1a4a8e;--midnight:#0f1d38;--sw:260px;--th:64px;}
    *,*::before,*::after{box-sizing:border-box;}
    body{font-family:'Poppins',system-ui,sans-serif;background:#f4f6fb;color:#1f2430;margin:0;}
    .topbar{position:fixed;top:0;left:0;right:0;height:var(--th);background:#fff;border-bottom:1px solid #e5e7ef;display:flex;align-items:center;padding:0 1.5rem;z-index:1030;box-shadow:0 2px 12px rgba(15,29,56,.08);}
    .topbar-brand{font-size:1.05rem;font-weight:700;color:var(--midnight);text-decoration:none;}
    .topbar-brand span{color:var(--brand);}
    .sidebar-toggle{background:none;border:none;cursor:pointer;color:var(--midnight);font-size:1.4rem;margin-right:1rem;padding:4px 8px;border-radius:6px;transition:background .2s;}
    .sidebar-toggle:hover{background:#f0f4ff;}
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
    .sc{background:#fff;border-radius:16px;box-shadow:0 2px 20px rgba(15,29,56,.07);padding:2rem;}
    .sc-title{font-size:1.2rem;font-weight:700;color:var(--midnight);}
    /* Candidate card */
    .cand-card{border:2px solid #e5e7ef;border-radius:14px;padding:1rem 1.25rem;cursor:pointer;transition:all .2s;display:flex;align-items:center;gap:1rem;margin-bottom:.65rem;}
    .cand-card:hover{border-color:var(--brand);background:#f6f9ff;}
    .cand-card.selected{border-color:var(--brand);background:#eef3ff;}
    .cand-card.voted-done{border-color:#198754;background:#f0fff4;cursor:default;}
    .cand-avatar{width:44px;height:44px;border-radius:50%;background:linear-gradient(135deg,var(--midnight),#3b4f7a);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.9rem;flex-shrink:0;}
    .cand-name{font-weight:600;font-size:.9rem;color:var(--midnight);}
    .cand-party{font-size:.78rem;color:#888;}
    /* Position section */
    .pos-section{border-radius:14px;border:1px solid #e5e7ef;padding:1.25rem;margin-bottom:1.25rem;background:#fafbff;}
    .pos-title{font-weight:700;color:var(--midnight);font-size:1rem;display:flex;align-items:center;gap:.5rem;margin-bottom:1rem;}
    /* Results */
    .result-bar-wrap{background:#eef3ff;border-radius:8px;height:12px;overflow:hidden;flex:1;}
    .result-bar{background:var(--brand);height:100%;border-radius:8px;transition:width .8s ease;}
    .result-row{display:flex;align-items:center;gap:.75rem;margin-bottom:.75rem;}
    .result-name{min-width:160px;font-size:.88rem;font-weight:500;}
    .result-count{min-width:40px;text-align:right;font-size:.82rem;color:#888;}
    /* Vote summary */
    .my-vote-item{display:flex;align-items:center;gap:1rem;padding:.75rem 1rem;border:1px solid #e5e7ef;border-radius:12px;margin-bottom:.65rem;background:#fafbff;}
    .sb-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.35);z-index:1015;}
    .sb-overlay.show{display:block;}
    @media(max-width:991px){.layout-main{margin-left:0!important;}.sidebar{transform:translateX(calc(-1 * var(--sw)));}.sidebar.open{transform:translateX(0);}}
  </style>
</head>
<body>

<header class="topbar">
  <button class="sidebar-toggle" id="sidebarToggle"><i class="bi bi-list"></i></button>
  <a class="topbar-brand" href="voter_dashboard.php">🗳️ Election <span>Portal</span></a>
  <div class="ms-auto d-flex align-items-center gap-2">
    <div class="user-badge"><?= $initials ?></div>
    <div class="d-none d-sm-block">
      <div style="font-size:.85rem;font-weight:600;"><?= htmlspecialchars($fullName) ?></div>
      <div style="font-size:.72rem;color:#888;">Voter</div>
    </div>
  </div>
</header>

<div class="sb-overlay" id="sbOverlay"></div>

<nav class="sidebar" id="sidebar">
  <div class="sidebar-lbl">Voter Menu</div>
  <ul class="snav">
    <li><a href="voter_dashboard.php?section=candidatesSection" class="<?= $activeSection==='candidatesSection'?'act':'' ?>"><i class="bi bi-person-badge-fill"></i> Candidates</a></li>
    <li>
      <a href="voter_dashboard.php?section=voteSection" class="<?= $activeSection==='voteSection'?'act':'' ?>">
        <i class="bi bi-check-square-fill"></i> Cast Vote
        <?php if($alreadyVotedAll && count($candidatesByPos)>0): ?>
          <span class="ms-auto badge bg-success" style="font-size:.65rem;">Done</span>
        <?php endif; ?>
      </a>
    </li>
    <li><a href="voter_dashboard.php?section=myVoteSection" class="<?= $activeSection==='myVoteSection'?'act':'' ?>"><i class="bi bi-card-checklist"></i> My Vote Summary</a></li>
    <li><a href="voter_dashboard.php?section=resultsSection" class="<?= $activeSection==='resultsSection'?'act':'' ?>"><i class="bi bi-bar-chart-fill"></i> Overall Results</a></li>
  </ul>
  <hr class="sdiv">
  <ul class="snav mb-3">
    <li><a href="voter_dashboard.php?logout=1"><i class="bi bi-box-arrow-left"></i> Logout</a></li>
  </ul>
</nav>

<main class="layout-main" id="layoutMain">

  <!-- ══ CANDIDATES LIST ══ -->
  <div id="candidatesSection" <?= $activeSection!=='candidatesSection'?'style="display:none"':'' ?>>
    <div class="mb-3">
      <h1 style="font-size:1.5rem;font-weight:700;">Candidate List</h1>
      <p class="text-muted small mb-0">Browse all registered candidates by position.</p>
    </div>
    <?php if(empty($candidatesByPos)): ?>
      <div class="sc"><p class="text-muted text-center py-4">No candidates registered yet.</p></div>
    <?php else: foreach($candidatesByPos as $pos => $cands): ?>
      <div class="sc mb-3">
        <h2 class="sc-title mb-3"><i class="bi bi-award-fill text-primary me-2"></i><?= htmlspecialchars($pos) ?></h2>
        <div class="row g-3">
          <?php foreach($cands as $c): $init=strtoupper(substr($c['candidate_name'],0,1)); ?>
          <div class="col-md-6 col-lg-4">
            <div style="border:1px solid #e5e7ef;border-radius:14px;padding:1rem;background:#fafbff;display:flex;align-items:center;gap:.9rem;">
              <div class="cand-avatar"><?= $init ?></div>
              <div>
                <div class="cand-name"><?= htmlspecialchars($c['candidate_name']) ?></div>
                <div class="cand-party"><i class="bi bi-flag me-1"></i><?= htmlspecialchars($c['party_affiliation'] ?? 'Independent') ?></div>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; endif; ?>
    <div class="text-center mt-3">
      <a href="voter_dashboard.php?section=voteSection" class="btn btn-primary btn-lg px-4"><i class="bi bi-check-square me-2"></i>Proceed to Vote</a>
    </div>
  </div>

  <!-- ══ CAST VOTE ══ -->
  <div id="voteSection" <?= $activeSection!=='voteSection'?'style="display:none"':'' ?>>
    <div class="mb-3">
      <h1 style="font-size:1.5rem;font-weight:700;">Cast Your Vote</h1>
      <p class="text-muted small mb-0">Select one candidate per position. You can only vote once per position.</p>
    </div>
    <?php if(empty($candidatesByPos)): ?>
      <div class="sc"><p class="text-muted text-center py-4">No candidates available to vote for.</p></div>
    <?php else: ?>
    <form method="POST" action="voter_dashboard.php" id="voteForm">
      <?php foreach($candidatesByPos as $pos => $cands):
        $fieldName  = 'vote_' . preg_replace('/[^a-zA-Z0-9]/', '_', $pos);
        $alreadyVoted = in_array($pos, $myVotedPositions);
        // Get what the voter voted for in this position
        $myChoiceRes = $conn->query("SELECT c.candidate_name FROM vote v JOIN tbl_candidate c ON v.candidate_id=c.candidate_id WHERE v.voter_id=$userId AND c.election_position='".$conn->real_escape_string($pos)."' LIMIT 1");
        $myChoice = $myChoiceRes && $myChoiceRes->num_rows > 0 ? $myChoiceRes->fetch_assoc()['candidate_name'] : null;
      ?>
      <div class="pos-section <?= $alreadyVoted ? 'border-success' : '' ?>">
        <div class="pos-title">
          <i class="bi bi-award-fill text-primary"></i>
          <?= htmlspecialchars($pos) ?>
          <?php if($alreadyVoted): ?>
            <span class="badge bg-success ms-auto"><i class="bi bi-check-circle me-1"></i>Voted: <?= htmlspecialchars($myChoice ?? '') ?></span>
          <?php endif; ?>
        </div>
        <?php if($alreadyVoted): ?>
          <p class="text-muted small mb-0"><i class="bi bi-lock me-1"></i>You have already cast your vote for this position.</p>
        <?php else: ?>
          <?php foreach($cands as $c):
            $init = strtoupper(substr($c['candidate_name'],0,1));
          ?>
          <label class="cand-card w-100" for="<?= $fieldName.'_'.$c['candidate_id'] ?>">
            <input type="radio" name="<?= $fieldName ?>" id="<?= $fieldName.'_'.$c['candidate_id'] ?>" value="<?= $c['candidate_id'] ?>" style="display:none" class="cand-radio">
            <div class="cand-avatar"><?= $init ?></div>
            <div>
              <div class="cand-name"><?= htmlspecialchars($c['candidate_name']) ?></div>
              <div class="cand-party"><i class="bi bi-flag me-1"></i><?= htmlspecialchars($c['party_affiliation'] ?? 'Independent') ?></div>
            </div>
            <i class="bi bi-circle ms-auto text-muted check-icon"></i>
          </label>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>

      <?php if(!$alreadyVotedAll): ?>
      <div class="text-center mt-3">
        <button type="button" class="btn btn-primary btn-lg px-5" onclick="confirmVote()">
          <i class="bi bi-check-circle me-2"></i>Submit My Votes
        </button>
      </div>
      <?php else: ?>
      <div class="alert alert-success text-center mt-3">
        <i class="bi bi-check-circle-fill me-2"></i>You have completed your voting. Thank you!
        <div class="mt-2"><a href="voter_dashboard.php?section=myVoteSection" class="btn btn-success btn-sm">View My Votes</a></div>
      </div>
      <?php endif; ?>
      <input type="hidden" name="submitVotes" value="1">
    </form>
    <?php endif; ?>
  </div>

  <!-- ══ MY VOTE SUMMARY ══ -->
  <div id="myVoteSection" <?= $activeSection!=='myVoteSection'?'style="display:none"':'' ?>>
    <div class="sc">
      <div class="mb-4">
        <h1 class="sc-title"><i class="bi bi-card-checklist me-2 text-primary"></i>My Vote Summary</h1>
        <p class="text-muted small mb-0">A record of all votes you have cast.</p>
      </div>
      <?php if($myVotesRes && $myVotesRes->num_rows > 0): ?>
        <?php foreach($myVotesRes as $mv): ?>
        <div class="my-vote-item">
          <div class="cand-avatar" style="background:linear-gradient(135deg,#1a4a8e,#3b6fd6);"><?= strtoupper(substr($mv['candidate_name'],0,1)) ?></div>
          <div style="flex:1;">
            <div style="font-weight:600;font-size:.92rem;"><?= htmlspecialchars($mv['candidate_name']) ?></div>
            <div style="font-size:.78rem;color:#888;"><i class="bi bi-award me-1"></i><?= htmlspecialchars($mv['election_position']) ?> &nbsp;·&nbsp; <i class="bi bi-flag me-1"></i><?= htmlspecialchars($mv['party_affiliation'] ?? 'Independent') ?></div>
          </div>
          <div style="font-size:.75rem;color:#aaa;text-align:right;"><?= htmlspecialchars($mv['vote_timestamp']) ?></div>
          <span class="badge bg-success"><i class="bi bi-check-circle-fill"></i></span>
        </div>
        <?php endforeach; ?>
        <div class="text-center mt-4">
          <a href="voter_dashboard.php?section=resultsSection" class="btn btn-outline-primary"><i class="bi bi-bar-chart me-1"></i>View Overall Results</a>
        </div>
      <?php else: ?>
        <div class="text-center py-5">
          <i class="bi bi-inbox display-4 text-muted d-block mb-3"></i>
          <p class="text-muted">You haven't voted yet.</p>
          <a href="voter_dashboard.php?section=voteSection" class="btn btn-primary"><i class="bi bi-check-square me-1"></i>Cast Your Vote</a>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- ══ OVERALL RESULTS ══ -->
  <div id="resultsSection" <?= $activeSection!=='resultsSection'?'style="display:none"':'' ?>>
    <div class="mb-3">
      <h1 style="font-size:1.5rem;font-weight:700;"><i class="bi bi-bar-chart-fill me-2 text-primary"></i>Overall Results</h1>
      <p class="text-muted small mb-0">Live vote tally – <strong><?= $totalVotes ?></strong> total votes cast.</p>
    </div>
    <?php if(empty($resultsByPos)): ?>
      <div class="sc"><p class="text-muted text-center py-4">No results available yet.</p></div>
    <?php else: foreach($resultsByPos as $pos => $res): $mx = $maxByPos[$pos] ?? 1; ?>
      <div class="sc mb-3">
        <h2 class="sc-title mb-3"><i class="bi bi-trophy-fill text-warning me-2"></i><?= htmlspecialchars($pos) ?></h2>
        <?php foreach($res as $i => $r):
          $votes  = intval($r['votes']);
          $pct    = $mx > 0 ? round(($votes / max($totalVotes, 1)) * 100, 1) : 0;
          $barPct = $mx > 0 ? round(($votes / $mx) * 100) : 0;
          $isWin  = ($i === 0 && $votes > 0);
        ?>
        <div class="result-row">
          <div class="result-name">
            <?php if($isWin): ?><i class="bi bi-trophy-fill text-warning me-1" title="Leading"></i><?php endif; ?>
            <strong><?= htmlspecialchars($r['candidate_name']) ?></strong>
            <div style="font-size:.72rem;color:#888;"><?= htmlspecialchars($r['party_affiliation'] ?? '') ?></div>
          </div>
          <div class="result-bar-wrap">
            <div class="result-bar" style="width:<?= $barPct ?>%;background:<?= $isWin?'#1a4a8e':'#b52232' ?>;"></div>
          </div>
          <div class="result-count"><strong><?= $votes ?></strong><br><span style="font-size:.7rem;"><?= $pct ?>%</span></div>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endforeach; endif; ?>
  </div>

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Sidebar
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

// Candidate card selection highlight
document.querySelectorAll('.cand-card').forEach(card=>{
  card.addEventListener('click',()=>{
    const radio=card.querySelector('.cand-radio');
    if(radio){radio.checked=true;
      // Unselect siblings in same group
      const name=radio.name;
      document.querySelectorAll(`input[name="${name}"]`).forEach(r=>{
        r.closest('.cand-card').classList.remove('selected');
        r.closest('.cand-card').querySelector('.check-icon')?.classList.replace('bi-check-circle-fill','bi-circle');
        r.closest('.cand-card').querySelector('.check-icon')?.classList.remove('text-primary');
      });
      card.classList.add('selected');
      card.querySelector('.check-icon')?.classList.replace('bi-circle','bi-check-circle-fill');
      card.querySelector('.check-icon')?.classList.add('text-primary');
    }
  });
});

// Vote confirm
function confirmVote(){
  // Check at least one selected
  const allGroups=new Set([...document.querySelectorAll('.cand-radio')].map(r=>r.name));
  let anySelected=false;
  allGroups.forEach(g=>{if(document.querySelector(`input[name="${g}"]:checked`))anySelected=true;});
  if(!anySelected){Swal.fire({title:'No Selection',text:'Please select at least one candidate.',icon:'warning',confirmButtonColor:'#1a4a8e'});return;}
  Swal.fire({
    title:'Confirm Your Vote',
    text:'Your votes cannot be changed after submission.',
    icon:'question',showCancelButton:true,
    confirmButtonColor:'#1a4a8e',cancelButtonColor:'#6c757d',
    confirmButtonText:'Yes, submit!'
  }).then(r=>{if(r.isConfirmed)document.getElementById('voteForm').submit();});
}
</script>
</body>
</html>
