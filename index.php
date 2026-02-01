<?php
declare(strict_types=1);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Reservation Ticket</title>
  <link rel="stylesheet" href="public/assets/style.css" />
</head>
<body>
  <div class="container">
    <h1>Manage Your Table Reservation</h1>
    <p class="muted">Enter your 8-character ticket code.</p>

    <form class="card" method="GET" action="manage.php">
      <label for="code">Reservation code</label>
      <div class="row">
        <input id="code" name="code" maxlength="8" placeholder="AB12CD34" required />
        <button type="submit">Submit</button>
      </div>
      <p class="hint">Example: AB12CD34 (from seed.sql)</p>
    </form>
  </div>
</body>
</html>
