<?php
require_once 'dbelection.php';

$candidates = [];
$dbError = '';
$imageDir = 'images';
$imageExtensions = ['jpg', 'jpeg', 'png', 'webp'];

function getCandidateLastNameSlug($fullName)
{
	$cleanName = trim(preg_replace('/\s+/', ' ', $fullName));
	if ($cleanName === '') {
		return '';
	}
	$parts = explode(' ', $cleanName);
	$lastName = end($parts);
	return preg_replace('/[^a-z0-9]/', '', strtolower($lastName));
}

function getCandidateInitials($fullName)
{
	$cleanName = trim(preg_replace('/\s+/', ' ', $fullName));
	if ($cleanName === '') {
		return '?';
	}
	$parts = explode(' ', $cleanName);
	$first = strtoupper(substr($parts[0], 0, 1));
	$last = count($parts) > 1 ? strtoupper(substr(end($parts), 0, 1)) : '';
	return $first . $last;
}

function getCandidatePhotoPath($candidateName, $imageDir, $extensions)
{
	$slug = getCandidateLastNameSlug($candidateName);
	if ($slug === '') {
		return null;
	}
	foreach ($extensions as $ext) {
		$path = $imageDir . '/' . $slug . '.' . $ext;
		if (file_exists($path)) {
			return $path;
		}
	}
	return null;
}

$query = "SELECT candidate_id, candidate_name, party_affiliation, election_position FROM tbl_candidate";
$result = $conn->query($query);

if ($result) {
	while ($row = $result->fetch_assoc()) {
		$candidates[] = $row;
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
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
	<title>Election Voting System</title>
	<style>
		:root {
			--brand: #b52232;
			--ink: #393939;
			--paper: #f6f5f3;
			--midnight: #1f2430;
			--slate: #5b667a;
		}

		body {
			background: var(--paper);
			color: var(--ink);
			font-family: "Poppins", "Segoe UI", system-ui, -apple-system, sans-serif;
		}

		.brand-accent {
			color: var(--brand);
		}

		h1, h2, h3, .navbar-brand {
			font-family: "Poppins", "Segoe UI", system-ui, -apple-system, sans-serif;
			letter-spacing: 0.02em;
		}

		.candidate-card {
			background: #ffffff;
			border: 1px solid rgba(0, 0, 0, 0.06);
			border-radius: 18px;
			box-shadow: 0 10px 24px rgba(23, 28, 40, 0.08);
			overflow: hidden;
			height: 100%;
			display: flex;
			flex-direction: column;
		}

		.candidate-image {
			position: relative;
			background: linear-gradient(180deg, rgba(31, 36, 48, 0.15), rgba(31, 36, 48, 0));
			padding: 10px;
		}

		.candidate-image-frame {
			border-radius: 14px;
			overflow: hidden;
			background: #e9edf4;
			aspect-ratio: 4 / 5;
			display: flex;
			align-items: center;
			justify-content: center;
		}

		.candidate-image-frame img {
			width: 100%;
			height: 100%;
			object-fit: cover;
		}

		.candidate-fallback {
			width: 100%;
			height: 100%;
			display: flex;
			align-items: center;
			justify-content: center;
			font-size: 2.4rem;
			font-weight: 700;
			color: #ffffff;
			background: linear-gradient(135deg, var(--midnight), #3b465f);
		}

		.candidate-ribbon {
			position: absolute;
			left: 16px;
			bottom: 12px;
			background: var(--brand);
			color: #ffffff;
			padding: 4px 10px;
			border-radius: 999px;
			font-size: 0.75rem;
			font-weight: 600;
			letter-spacing: 0.04em;
			text-transform: uppercase;
		}

		.candidate-body {
			padding: 14px 16px 18px;
			display: flex;
			flex-direction: column;
			gap: 8px;
		}

		.candidate-name {
			font-size: 1rem;
			font-weight: 700;
			margin: 0;
			color: var(--midnight);
		}

		.candidate-meta {
			font-size: 0.82rem;
			color: var(--slate);
			letter-spacing: 0.02em;
			text-transform: uppercase;
		}

		.candidate-details {
			font-size: 0.9rem;
			color: var(--ink);
		}

		.candidate-details span {
			font-weight: 600;
		}
	</style>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
	<nav class="navbar navbar-expand-lg bg-white border-bottom">
		<div class="container">
			<a class="navbar-brand fw-semibold" href="#main">Election Voting System</a>
			<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
				<span class="navbar-toggler-icon"></span>
			</button>
			<div class="collapse navbar-collapse" id="mainNav">
				<ul class="navbar-nav ms-auto">
					<li class="nav-item">
						<a class="nav-link" href="votingforms.php">Vote Now!</a>
					</li>
				</ul>
			</div>
		</div>
	</nav>

	<section id="main" class="py-5">
		<div class="container">
			<div class="row align-items-center">
				<div class="col-lg-7">
					<h1 class="display-6 fw-semibold">Main page</h1>
					<p class="lead">Welcome to the Election Voting System. Review candidates and stay informed before casting your vote.</p>
					<p class="text-muted mb-0">Use the navigation to jump to the candidate list.</p>
				</div>
				<div class="col-lg-5">
					<div class="p-4 bg-white border rounded-4 shadow-sm">
						<h2 class="h5 mb-2">Quick actions</h2>
						<ul class="list-unstyled mb-0">
							<li class="mb-2">Browse candidates</li>
							<li class="mb-2">Check party affiliations</li>
							<li class="mb-0">Review positions</li>
						</ul>
					</div>
				</div>
			</div>
		</div>
	</section>

	<section id="candidates" class="py-5 bg-white border-top">
		<div class="container">
			<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
				<div>
					<h2 class="h4 mb-1">Candidate Information</h2>
					<p class="text-muted mb-0">List of registered candidates.</p>
				</div>
				<div class="btn-group" role="group" aria-label="Candidate views">
					<button type="button" class="btn btn-outline-secondary candidate-filter active" data-target="candidate-table" aria-pressed="true">Candidate Information</button>
					<button type="button" class="btn btn-outline-secondary candidate-filter" data-target="party-list">Party Lists</button>
				</div>
			</div>

			<?php if ($dbError): ?>
				<div class="alert alert-danger" role="alert">
					Unable to load candidates: <?php echo htmlspecialchars($dbError); ?>
				</div>
			<?php elseif (count($candidates) === 0): ?>
				<div class="alert alert-secondary" role="alert">
					No candidates found.
				</div>
			<?php else: ?>
				<div id="party-list" class="candidate-view d-none">
					<div class="row g-3">
						<?php
						$partyGroups = [];
						foreach ($candidates as $candidate) {
							$party = trim($candidate['party_affiliation'] ?? '');
							if ($party !== '') {
								$partyGroups[$party] = true;
							}
						}
						foreach (array_keys($partyGroups) as $partyName):
						?>
							<div class="col-sm-6 col-lg-4 col-xl-3">
								<div class="p-3 border rounded-4 h-100 bg-light-subtle">
									<h3 class="h6 mb-1"><?php echo htmlspecialchars($partyName); ?></h3>
									<p class="text-muted small mb-0">Party list</p>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				</div>

				<div id="candidate-table" class="candidate-view">
					<div class="row g-3">
						<?php foreach ($candidates as $candidate): ?>
							<?php
							$photoPath = getCandidatePhotoPath($candidate['candidate_name'], $imageDir, $imageExtensions);
							$initials = getCandidateInitials($candidate['candidate_name']);
							$positionLabel = trim((string) $candidate['election_position']) !== ''
								? 'Position ' . $candidate['election_position']
								: 'Candidate';
							?>
							<div class="col-sm-6 col-lg-4 col-xl-3">
								<div class="candidate-card">
									<div class="candidate-image">
										<div class="candidate-image-frame">
											<?php if ($photoPath): ?>
												<img src="<?php echo htmlspecialchars($photoPath); ?>" alt="Photo of <?php echo htmlspecialchars($candidate['candidate_name']); ?>">
											<?php else: ?>
												<div class="candidate-fallback"><?php echo htmlspecialchars($initials); ?></div>
											<?php endif; ?>
										</div>
										<span class="candidate-ribbon"><?php echo htmlspecialchars($positionLabel); ?></span>
									</div>
									<div class="candidate-body">
										<h3 class="candidate-name"><?php echo htmlspecialchars($candidate['candidate_name']); ?></h3>
										<div class="candidate-meta"><?php echo htmlspecialchars($candidate['party_affiliation']); ?></div>
										<div class="candidate-details">
											<div><span>ID:</span> <?php echo htmlspecialchars($candidate['candidate_id']); ?></div>
											<div><span>Position:</span> <?php echo htmlspecialchars($candidate['election_position']); ?></div>
										</div>
									</div>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>
		</div>
	</section>

	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
	<script>
		const filterButtons = document.querySelectorAll('.candidate-filter');
		const candidateViews = document.querySelectorAll('.candidate-view');

		const showCandidateView = (targetId) => {
			candidateViews.forEach((view) => {
				view.classList.toggle('d-none', view.id !== targetId);
			});
			filterButtons.forEach((button) => {
				const isActive = button.dataset.target === targetId;
				button.classList.toggle('active', isActive);
				button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
			});
		};

		filterButtons.forEach((button) => {
			button.addEventListener('click', () => showCandidateView(button.dataset.target));
		});
	</script>
</body>
</html>
