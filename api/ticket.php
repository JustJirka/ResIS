<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

$code = normalize_code($_GET['code'] ?? '');
if (!is_valid_code($code)) {
  json_response(['error' => 'Invalid code format'], 400);
}

$pdo = db();

// Ticket info
$stmt = $pdo->prepare("SELECT id AS ticket_id, code FROM tickets WHERE code = ?");
$stmt->execute([$code]);
$ticket = $stmt->fetch();

if (!$ticket) {
  json_response(['error' => 'Ticket not found'], 404);
}

// Current selected table (if any)
$stmt = $pdo->prepare("
  SELECT ta.table_id, tb.label, tb.capacity
  FROM table_assignments ta
  JOIN tables tb ON tb.id = ta.table_id
  WHERE ta.ticket_id = ?
");
$stmt->execute([(int)$ticket['ticket_id']]);
$current = $stmt->fetch() ?: null;

// Available tables: remaining = capacity - count(assignments)
$stmt = $pdo->query("
  SELECT
    tb.id,
    tb.label,
    tb.capacity,
    (tb.capacity - COALESCE(used.used_count, 0)) AS remaining
  FROM tables tb
  LEFT JOIN (
    SELECT table_id, COUNT(*) AS used_count
    FROM table_assignments
    GROUP BY table_id
  ) used ON used.table_id = tb.id
  WHERE tb.is_active = 1
    AND (tb.capacity - COALESCE(used.used_count, 0)) >= 1
  ORDER BY tb.label
");
$available = $stmt->fetchAll();

json_response([
  'ticket' => [
    'code' => $ticket['code'],
    'ticket_id' => (int)$ticket['ticket_id'],
    'current_table' => $current ? [
      'table_id' => (int)$current['table_id'],
      'label' => $current['label'],
      'capacity' => (int)$current['capacity'],
    ] : null,
  ],
  'available_tables' => array_map(fn($t) => [
    'id' => (int)$t['id'],
    'label' => $t['label'],
    'capacity' => (int)$t['capacity'],
    'remaining' => (int)$t['remaining'],
  ], $available),
]);
