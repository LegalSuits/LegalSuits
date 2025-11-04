<?php
// api/helpers.php
if (!function_exists('ok')) {
  function ok(array $data = []) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(['status' => 'ok'], $data), JSON_UNESCAPED_UNICODE);
    exit;
  }
}
if (!function_exists('fail')) {
  function fail(string $msg, int $code = 400) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'error', 'message' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
  }
}