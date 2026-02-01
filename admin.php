<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/db.php';

function generateTicketCode(int $len = 8): string {
  $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
  $bytes = random_bytes($len);
  $code = '';
  $n = strlen($alphabet);
  for ($i = 0; $i < $len; $i++) {
    $code .= $alphabet[ord($bytes[$i]) % $n];
  }
  return $code;
}

function createOneTicket(PDO $pdo): string {
  // Retry on collision (extremely rare)
  for ($attempt = 0; $attempt < 20; $attempt++) {
    $code = generateTicketCode(8);
    try {
      $stmt = $pdo->prepare("INSERT INTO tickets (code) VALUES (?)");
      $stmt->execute([$code]);
      return $code;
    } catch (PDOException $e) {
      // 23000 = unique constraint violation (collision)
      if (($e->getCode() ?? '') === '23000') {
        continue;
      }
      throw $e;
    }
  }
  throw new RuntimeException('Could not generate a unique code after many attempts.');
}

$pdo = db();

$created = [];
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $count = (int)($_POST['count'] ?? 1);
  if ($count < 1) $count = 1;
  if ($count > 200) $count = 200; // safety cap

  try {
    $pdo->beginTransaction();
    for ($i = 0; $i < $count; $i++) {
      $created[] = createOneTicket($pdo);
    }
    $pdo->commit();
  } catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $error = $e->getMessage();
  }
}

// Show newest tickets
$stmt = $pdo->query("SELECT code, created_at FROM tickets ORDER BY id DESC LIMIT 50");
$latest = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Admin - Create Tickets</title>
  <link rel="stylesheet" href="public/assets/style.css" />
</head>
<body>
  <div class="container stack">
    <div class="topbar">
      <h1>Admin: Create Tickets</h1>
      <a class="link" href="index.php">User page</a>
    </div>

    <div class="card">
      <h2>Create new ticket codes</h2>

      <?php if ($error): ?>
        <p class="muted" style="color:#b91c1c;"><strong>Error:</strong> <?= htmlspecialchars($error) ?></p>
      <?php endif; ?>

      <?php if ($created): ?>
        <p class="muted"><strong>Created <?= count($created) ?> ticket(s):</strong></p>
        <div style="display:grid; gap:6px; grid-template-columns: repeat(2, minmax(0, 1fr));">
          <?php foreach ($created as $c): ?>
            <code style="background:#f3f4f6; padding:8px; border-radius:10px; border:1px solid #e5e7eb;"><?= htmlspecialchars($c) ?></code>
          <?php endforeach; ?>
        </div>
        <p class="muted" style="margin-top:10px;">
          Tip: open <strong>manage.php?code=CODE</strong> to test.
        </p>
      <?php endif; ?>

      <form method="POST" class="row" style="margin-top:14px;">
        <div style="flex:1;">
          <label for="count">How many tickets?</label>
          <input id="count" name="count" type="number" min="1" max="200" value="1" />
        </div>
        <div style="align-self:flex-end;">
          <button type="submit">Generate</button>
        </div>
      </form>
    </div>

    <div class="card">
      <h2>Latest tickets</h2>
      <?php if (!$latest): ?>
        <p class="muted">No tickets yet.</p>
      <?php else: ?>
        <div style="overflow:auto;">
          <table style="width:100%; border-collapse: collapse;">
            <thead>
              <tr>
                <th style="text-align:left; padding:8px; border-bottom:1px solid #e5e7eb;">Code</th>
                <th style="text-align:left; padding:8px; border-bottom:1px solid #e5e7eb;">Created</th>
                <th style="text-align:left; padding:8px; border-bottom:1px solid #e5e7eb;">Open</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($latest as $row): ?>
                <tr>
                  <td style="padding:8px; border-bottom:1px solid #f1f5f9;"><code><?= htmlspecialchars($row['code']) ?></code></td>
                  <td style="padding:8px; border-bottom:1px solid #f1f5f9;"><?= htmlspecialchars((string)$row['created_at']) ?></td>
                  <td style="padding:8px; border-bottom:1px solid #f1f5f9;">
                    <a class="link" href="manage.php?code=<?= urlencode($row['code']) ?>">Manage</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

    <div class="card">
      <h2>Security note</h2>
      <p class="muted">
        This page should be protected (e.g., HTTP Basic Auth or IP restriction) before production.
      </p>
    </div>
  </div>
</body>
</html>
