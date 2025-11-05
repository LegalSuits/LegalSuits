<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

// CONFIG
const MAX_FILE_BYTES = 10 * 1024 * 1024; // 10MB
const MAX_PAGES = 50; // maximum pages allowed (null ak nechceme obmedzovať)
const ALLOWED_MIMES = [
    'image/png',
    'image/jpeg',
    'application/pdf',
    'application/msword', // .doc
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document', // .docx
    'text/plain'
];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Use POST.']);
    exit;
}

if (!isset($_FILES['file'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'No file uploaded.']);
    exit;
}

$file = $_FILES['file'];
if (!isset($file['error']) || is_array($file['error'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid upload parameters.']);
    exit;
}
if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['status' => 'error', 'message' => 'Upload error code: ' . $file['error']]);
    exit;
}

// size check
if ($file['size'] > MAX_FILE_BYTES) {
    echo json_encode(['status' => 'error', 'message' => 'File too large. Max allowed: ' . (MAX_FILE_BYTES / (1024*1024)) . ' MB']);
    exit;
}

// ensure uploaded via HTTP POST
if (!is_uploaded_file($file['tmp_name'])) {
    echo json_encode(['status' => 'error', 'message' => 'File not uploaded via HTTP POST.']);
    exit;
}

// determine mime type using finfo
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = $finfo ? finfo_file($finfo, $file['tmp_name']) : null;
if ($finfo) finfo_close($finfo);

// extension and sanitized name (basic)
$origName = $file['name'] ?? 'upload';
$origName = str_replace(["\0", "/", "\\", ".."], '_', $origName);
$origName = trim($origName);
$trans = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $origName);
if ($trans !== false) $origName = $trans;
$origName = preg_replace('/[^A-Za-z0-9\-\._ ]+/', '', $origName);
$origName = str_replace(' ', '_', $origName);
if ($origName === '') $origName = 'upload_' . bin2hex(random_bytes(6));

$ext = pathinfo($origName, PATHINFO_EXTENSION);
$base = pathinfo($origName, PATHINFO_FILENAME);
$timestamp = date('Ymd_His');
$targetName = $base . '_' . $timestamp . ($ext ? '.' . $ext : '');

// mime/extension allowed check
$allowed = true;
if ($mime && !in_array($mime, ALLOWED_MIMES, true)) $allowed = false;
if (!$mime) {
    // pokus rozpoznat z extension
    $lower = strtolower($ext);
    $extAllowed = in_array($lower, ['pdf','doc','docx','txt','png','jpg','jpeg'], true);
    if (!$extAllowed) $allowed = false;
}
if (!$allowed) {
    echo json_encode(['status' => 'error', 'message' => 'File type not allowed. Detected MIME: ' . ($mime ?? 'unknown')]);
    exit;
}

// ensure tmp directory exists (relative to api/)
$baseDir = __DIR__;
$tmpDir = $baseDir . DIRECTORY_SEPARATOR . 'tmp';
if (!is_dir($tmpDir)) {
    if (!mkdir($tmpDir, 0755, true) && !is_dir($tmpDir)) {
        echo json_encode(['status' => 'error', 'message' => 'Cannot create tmp dir.']);
        exit;
    }
}

$targetPath = $tmpDir . DIRECTORY_SEPARATOR . $targetName;

// move file
if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
    echo json_encode(['status' => 'error', 'message' => 'Unable to move uploaded file.']);
    exit;
}
@chmod($targetPath, 0644);

// try to determine page count for PDF/DOCX (best-effort)
$pages = null;

function get_pdf_pages(string $path) {
    // prefer pdfinfo if available
    if (function_exists('shell_exec')) {
        $out = @shell_exec('pdfinfo ' . escapeshellarg($path) . ' 2>/dev/null');
        if ($out && preg_match('/^Pages:\s+(\d+)/mi', $out, $m)) {
            return (int)$m[1];
        }
    }
    // fallback: simple grep for /Count in file content (not 100% reliable)
    $contents = @file_get_contents($path, false, null, 0, 2000000); // prvých 2MB
    if ($contents && preg_match('/\/Count\s+(\d+)/', $contents, $m)) {
        return (int)$m[1];
    }
    return null;
}

function get_docx_pages(string $path) {
    if (!class_exists('ZipArchive')) return null;
    $zip = new ZipArchive();
    if ($zip->open($path) === true) {
        $idx = $zip->locateName('docProps/app.xml', ZIPARCHIVE::FL_NOCASE);
        if ($idx !== false) {
            $xml = $zip->getFromIndex($idx);
            if ($xml && preg_match('/<Pages.*?>(\d+)<\/Pages>/', $xml, $m)) {
                $zip->close();
                return (int)$m[1];
            }
        }
        $zip->close();
    }
    return null;
}

$lowerExt = strtolower($ext);
if ($lowerExt === 'pdf') {
    $pages = get_pdf_pages($targetPath);
} elseif ($lowerExt === 'docx') {
    $pages = get_docx_pages($targetPath);
}

// check pages limit
if (defined('MAX_PAGES') && MAX_PAGES !== null && $pages !== null) {
    if ($pages > MAX_PAGES) {
        // odstranime ulozeny subor a vratime chybu
        @unlink($targetPath);
        echo json_encode(['status' => 'error', 'message' => 'Document has too many pages (' . $pages . '). Max allowed ' . MAX_PAGES]);
        exit;
    }
}

// prepare public url (relative)
$publicUrl = '/tmp/' . rawurlencode($targetName);

echo json_encode([
    'status' => 'ok',
    'path' => $targetPath,
    'name' => $targetName,
    'pages' => $pages,
    'mime' => $mime,
    'url' => $publicUrl
]);
exit;
