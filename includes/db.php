<?php
declare(strict_types=1);

function loadEnv(string $path): array {
  if (!file_exists($path)) return [];
  $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
  $env = [];
  foreach ($lines as $line) {
    $line = trim($line);
    if ($line === '' || str_starts_with($line, '#')) continue;
    $pos = strpos($line, '=');
    if ($pos === false) continue;
    $key = trim(substr($line, 0, $pos));
    $val = trim(substr($line, $pos + 1));
    $env[$key] = $val;
  }
  return $env;
}

function db(): PDO {
  static $pdo = null;
  if ($pdo) return $pdo;

  $env = loadEnv(__DIR__ . '/../.env');

  $host = 'xxx';
  $name = 'xxx_resis';
  $user = 'xxx_resis';
  $pass = 'xxx';

  $dsn = "mysql:host={$host};dbname={$name};charset=utf8mb4";
  $pdo = new PDO($dsn, $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);

  return $pdo;
}
