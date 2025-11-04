<?php
declare(strict_types=1);
require_once __DIR__ . '/helpers.php';

$envFile = __DIR__ . '/.env.php';
if (!file_exists($envFile)) fail('Missing .env.php', 500);
$env = include $envFile;
$OPENAI_API_KEY = $env['OPENAI_API_KEY'] ?? '';
if ($OPENAI_API_KEY === '') fail('Missing OPENAI_API_KEY in .env.php', 500);

$file = $_GET['file'] ?? '';
if (!$file) fail('Missing parameter ?file=', 400);

$path = __DIR__ . '/tmp/' . basename($file);
if (!is_file($path)) fail('File not found in tmp.', 404);

// simple preview for now
$size = filesize($path);
$preview = substr(file_get_contents($path), 0, 1000);

ok([
  'message' => 'Ready (stub).',
  'file' => basename($path),
  'size' => $size,
  'preview' => $preview
]);