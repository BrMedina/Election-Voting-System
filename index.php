<?php
require_once 'dbelection.php';

$candidates = [];
$dbError = '';

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
		}

		body {
			background: var(--paper);
			color: var(--ink);
		}

		.brand-accent {
			color: var(--brand);
		}
	</style>
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
						<a class="nav-link" href="#main">Main page</a>
					</li>
					<li class="nav-item">
						<a class="nav-link" href="#candidates">Candidate information</a>
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
				<div class="table-responsive">
					<table class="table table-bordered align-middle">
						<thead class="table-light">
							<tr>
								<th scope="col">ID</th>
								<th scope="col">Name</th>
								<th scope="col">Party</th>
								<th scope="col">Position</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($candidates as $candidate): ?>
								<tr>
									<td><?php echo htmlspecialchars($candidate['candidate_id']); ?></td>
									<td><?php echo htmlspecialchars($candidate['candidate_name']); ?></td>
									<td><?php echo htmlspecialchars($candidate['party_affiliation']); ?></td>
									<td><?php echo htmlspecialchars($candidate['election_position']); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php endif; ?>
		</div>
	</section>

	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
