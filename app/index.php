<?php
require __DIR__ . '/vendor/autoload.php';

use Carbon\Carbon;

$now = Carbon::now();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>PHP site</title>
</head>
<body>
  <h1>PHP site</h1>
  <p>Served at <?= htmlspecialchars($now->toDayDateTimeString()) ?> (PHP <?= PHP_VERSION ?>).</p>
</body>
</html>
