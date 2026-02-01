<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

$body = require_json_body();
$code = normalize_code($body['code'] ?? '');
$tableId = (int)($body['table_id'] ?? 0);

if (!is_valid_code($code) || $tableId <= 0) {
  json_response(['error' => 'Invalid code or table_id'], 400);
}

$pdo = db();

try {
  $pdo->beginTransaction();

  // Lock ticket
  $stmt = $pdo->prepare("SELECT id FROM tickets WHERE code = ? FOR UPDATE");
  $stmt->execute([$code]);
  $ticket = $stmt->fetch();
  if (!$ticket) {
    $pdo->rollBack();
    json_response(['error' => 'Ticket not found'], 404);
  }
  $ticketId = (int)$ticket['id'];

  // Lock table row
  $stmt = $pdo->prepare("SELECT id, capacity, is_active FROM tables WHERE id = ? FOR UPDATE");
  $stmt->execute([$tableId]);
  $tbl = $stmt->fetch();
  if (!$tbl || (int)$tbl['is_active'] !== 1) {
    $pdo->rollBack();
    json_response(['error' => 'Table not available'], 400);
  }
  $capacity = (int)$tbl['capacity'];

  // Switch behavior: remove existing assignment for this ticket first
  $stmt = $pdo->prepare("DELETE FROM table_assignments WHERE ticket_id = ?");
  $stmt->execute([$ticketId]);

  // Lock/count existing assignments for this table (prevents race oversubscribe)
  $stmt = $pdo->prepare("
    SELECT COUNT(*) AS used
    FROM table_assignments
    WHERE table_id = ?
    FOR UPDATE
  ");
  $stmt->execute([$tableId]);
  $used = (int)($stmt->fetch()['used'] ?? 0);

  if ($used + 1 > $capacity) {
    $pdo->rollBack();
    json_response([
      'error' => 'Table is full',
      'details' => ['capacity' => $capacity, 'used' => $used, 'requested' => 1]
    ], 409);
  }

  // Assign
  $stmt = $pdo->prepare("INSERT INTO table_assignments (ticket_id, table_id) VALUES (?, ?)");
  $stmt->execute([$ticketId, $tableId]);

  $pdo->commit();
  json_response(['ok' => true, 'table_id' => $tableId]);
} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  json_response(['error' => 'Server error'], 500);
}
