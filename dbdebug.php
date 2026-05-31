<?php
require 'dbelection.php';

echo "=== tbl_position ===\n";
$r = $conn->query("DESCRIBE tbl_position");
if ($r) { while($row=$r->fetch_assoc()) print_r($row); } else echo $conn->error."\n";

echo "\n=== tbl_position rows ===\n";
$r = $conn->query("SELECT * FROM tbl_position");
if ($r) { while($row=$r->fetch_assoc()) print_r($row); } else echo $conn->error."\n";

echo "\n=== tbl_election ===\n";
$r = $conn->query("DESCRIBE tbl_election");
if ($r) { while($row=$r->fetch_assoc()) print_r($row); } else echo $conn->error."\n";

echo "\n=== tbl_election rows ===\n";
$r = $conn->query("SELECT * FROM tbl_election");
if ($r) { while($row=$r->fetch_assoc()) print_r($row); } else echo $conn->error."\n";

echo "\n=== election_position values in tbl_candidate ===\n";
$r = $conn->query("SELECT DISTINCT election_position FROM tbl_candidate ORDER BY election_position");
if ($r) { while($row=$r->fetch_assoc()) echo $row['election_position']."\n"; } else echo $conn->error."\n";
