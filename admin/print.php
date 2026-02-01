<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

// Build base URL like https://lehnerp.eu (and include subfolder if project is in one)
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

// If your project is in a subfolder, set it here explicitly, e.g. "/reservation-system"
// If it is at domain root, keep it ""
$PROJECT_PREFIX = ""; // <-- CHANGE IF NEEDED (example: "/reservation-system")

$baseUrl = $scheme . '://' . $host . $PROJECT_PREFIX;

// Ticket destination (DIRECT page)
$manageUrl = function(string $code) use ($baseUrl): string {
  return $baseUrl . '/manage.php?code=' . urlencode($code);
};

// QR provider (no composer, no libs)
// qrserver returns a PNG for given data
$qrUrl = function(string $data, int $size = 300): string {
  return 'https://quickchart.io/qr'
    . '?size=' . $size
    . '&format=png'
    . '&ecLevel=H'
    . '&margin=2'
    // Center logo
    . '&centerImageUrl=' . urlencode('https://vizual.utb.cz/fai/fai-symbol.jpg')
    . '&centerImageSize=0.28'
    // Data
    . '&text=' . urlencode($data);
};

$pdo = db();
$tickets = $pdo->query("SELECT code, created_at FROM tickets ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Print Tickets</title>
  <style>
    @page { size: A4; margin: 10mm; }
    body { margin: 0; font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; color: #111; }

    .no-print { padding: 10px 10px 0; }
    .topbar { display:flex; justify-content:space-between; align-items:baseline; gap: 12px; }
    .topbar h1 { margin: 0; font-size: 22px; }
    .topbar button { padding: 10px 14px; border-radius: 10px; border: 1px solid #111; background: #111; color: #fff; font-weight: 700; cursor: pointer; }
    .note { margin: 8px 0 10px; color: #444; font-size: 13px; }

    .sheet {
      padding: 10px;
      display: grid;
      grid-template-columns: repeat(3, 1fr); /* 3 tickets per row on A4 */
      gap: 8mm;
    }

    .ticket {
      border: 1px solid #111;
      border-radius: 10px;
      padding: 6mm;
      min-height: 70mm;
      display: grid;
      grid-template-rows: auto auto;
      gap: 4mm;
      break-inside: avoid;
      align-content: start;
    }

    .qr {
      display: flex;
      justify-content: center;
      align-items: center;
    }
    .qr img {
      width: 42mm;
      height: 42mm;
      image-rendering: pixelated;
    }

    .code {
      text-align: center;
      font-size: 20px;
      font-weight: 900;
      letter-spacing: 1.5px;
    }

    @media print {
      .no-print { display: none; }
      .sheet { padding: 0; }
    }
  </style>
</head>
<body>

  <div class="no-print">
    <div class="topbar">
      <h1>Tickets (<?= count($tickets) ?>)</h1>
      <button onclick="window.print()">Print</button>
    </div>
    <div class="note">
      QR opens the direct manage page. Ticket shows only the code.
      <?php if ($PROJECT_PREFIX !== ""): ?>
        <br><strong>Project prefix:</strong> <?= h($PROJECT_PREFIX) ?>
      <?php else: ?>
        <br><strong>Project prefix:</strong> (none)
      <?php endif; ?>
    </div>
  </div>

  <div class="sheet">
    <?php foreach ($tickets as $t):
      $code = normalize_code($t['code']);
      if (!is_valid_code($code)) continue; // skip bad data safely
      $dest = $manageUrl($code);
      $img = $qrUrl($dest, 220);
    ?>
      <div class="ticket">
        <div class="qr">
          <img src="<?= h($img) ?>" alt="QR <?= h($code) ?>">
        </div>
        <div class="code"><?= h($code) ?></div>
      </div>
    <?php endforeach; ?>
  </div>

</body>
</html>
