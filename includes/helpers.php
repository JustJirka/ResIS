<?php
declare(strict_types=1);

function json_response(array $data, int $status = 200): void {
  http_response_code($status);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($data, JSON_UNESCAPED_UNICODE);
  exit;
}

function require_json_body(): array {
  $raw = file_get_contents('php://input');
  $data = json_decode($raw, true);
  if (!is_array($data)) {
    json_response(['error' => 'Invalid JSON body'], 400);
  }
  return $data;
}

function normalize_code(string $code): string {
  return strtoupper(trim($code));
}

function is_valid_code(string $code): bool {
  return (bool)preg_match('/^[A-Z0-9]{8}$/', $code);
}
