<?php
// api/process.php - debug verzia, vráti JSON a loguje
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

$logFile = __DIR__ . '/tmp/upload.log';

// jednoduchý logger
function dbg($msg) {
    global $logFile;
    $t = date('Y-m-d H:i:s');
    @file_put_contents($logFile, "[$t] " . print_r($msg, true) . PHP_EOL, FILE_APPEND);
}

// základné info
dbg("=== request start ===");
dbg(['method'=>$_SERVER['REQUEST_METHOD'],'uri'=>$_SERVER['REQUEST_URI']]);
dbg(['post'=>$_POST]);

// ensure tmp dir exists and je writeable
$targetDir = __DIR__ . '/tmp';
if (!is_dir($targetDir)) {
    if (!mkdir($targetDir, 0775, true)) {
        $err = ['error'=>'mkdir_failed','path'=>$targetDir];
        dbg($err);
        echo json_encode($err);
        exit;
    }
    // pokúsme sa nastaviť vlastníka (ak posix_getpwuid dostupné)
    if (function_exists('posix_getpwuid')) {
        @chown($targetDir, 'www-data');
        @chgrp($targetDir, 'www-data');
    }
}
dbg(['tmp_exists'=>is_dir($targetDir),'tmp_writable'=>is_writable($targetDir)]);

// php upload limits
dbg([
    'upload_max_filesize'=>ini_get('upload_max_filesize'),
    'post_max_size'=>ini_get('post_max_size'),
    'max_file_uploads'=>ini_get('max_file_uploads')
]);

// dump files
dbg(['_FILES'=>$_FILES]);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $out = ['status'=>'error','message'=>'Use POST.'];
    dbg($out);
    echo json_encode($out);
    exit;
}

if (!isset($_FILES['file'])) {
    $out = ['status'=>'error','message'=>'No file uploaded (no FILES[\"file\"])','files'=>$_FILES];
    dbg($out);
    echo json_encode($out);
    exit;
}

$f = $_FILES['file'];

dbg(['file_meta'=>$f]);

if ($f['error'] !== UPLOAD_ERR_OK) {
    $out = ['status'=>'error','message'=>'Upload error','code'=>$f['error']];
    dbg($out);
    echo json_encode($out);
    exit;
}

$target = $targetDir . '/' . basename($f['name']);

// pokus o presun
$moved = move_uploaded_file($f['tmp_name'], $target);
dbg(['move_uploaded_file_result'=>$moved,'tmp_name'=>$f['tmp_name'],'target'=>$target]);

if ($moved) {
    @chmod($target, 0644);
    $out = ['status'=>'ok','path'=> $target, 'name'=>$f['name']];
    dbg($out);
    echo json_encode($out);
    exit;
} else {
    $err = ['status'=>'error','message'=>'Could not move uploaded file','target'=>$target,'is_writable_tmp'=>is_writable($targetDir)];
    dbg($err);
    echo json_encode($err);
    exit;
}
