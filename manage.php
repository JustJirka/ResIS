<?php
declare(strict_types=1);

$code = strtoupper(preg_replace('/\s+/', '', (string)($_GET['code'] ?? '')));

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Manage Reservation</title>
  <link rel="stylesheet" href="public/assets/style.css" />
</head>
<body>
  <div class="container">
    <div class="topbar">
      <h1>Manage Reservation</h1>
      <a class="link" href="index.php">Change code</a>
    </div>

    <div id="app" class="stack" data-code="<?= htmlspecialchars($code) ?>">
      <div class="card">
        <div class="skeleton" style="height:18px; width:45%"></div>
        <div class="skeleton" style="height:14px; width:30%"></div>
        <div class="skeleton" style="height:14px; width:55%"></div>
      </div>
    </div>
  </div>

<script>
  window.APP_BASE = "<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') ?>";
</script>
<script src="public/assets/app.js?v=2"></script>

</body>
</html>
