<?php
declare(strict_types=1);
require_once __DIR__ . '/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('Use POST.', 405);
if (!isset($_FILES['file'])) fail('No file uploaded.', 400);

$u = $_FILES['file'];
if ($u['error'] !== UPLOAD_ERR_OK) fail('Upload error: ' . $u['error'], 400);

$targetDir = __DIR__ . '/tmp/';
if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

$fname = basename($u['name']);
$target = $targetDir . $fname;

if (!move_uploaded_file($u['tmp_name'], $target)) {
  fail('Could not save file.', 500);
}

ok(['message'=>'Upload OK','file'=>$fname]);