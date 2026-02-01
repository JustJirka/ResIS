<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

$body = require_json_body();
$code = normalize_code($body['code'] ?? '');

if (!is_valid_code($code)) {
  json_response(['error' => 'Invalid code'], 400);
}

$pdo = db();

try {
  $pdo->beginTransaction();

  // Lock the ticket row
  $stmt = $pdo->prepare("
    SELECT id
    FROM tickets
    WHERE code = ?
    FOR UPDATE
  ");
  $stmt->execute([$code]);
  $ticket = $stmt->fetch();

  if (!$ticket) {
    $pdo->rollBack();
    json_response(['error' => 'Ticket not found'], 404);
  }

  $ticketId = (int)$ticket['id'];

  // Remove assignment (frees capacity)
  $stmt = $pdo->prepare("
    DELETE FROM table_assignments
    WHERE ticket_id = ?
  ");
  $stmt->execute([$ticketId]);

  $pdo->commit();
  json_response(['ok' => true]);
} catch (Throwable $e) {
  if ($pdo->inTransaction()) {
    $pdo->rollBack();
  }
  json_response(['error' => 'Server error'], 500);
}
