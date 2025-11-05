<?php
declare(strict_types=1);

// process.php - spracovanie uploadu súboru, uloženie do ./tmp a JSON odpoveď

header('Content-Type: application/json; charset=utf-8');

// iba POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Use POST.']);
    exit;
}

// skontroluj existenciu pola
if (!isset($_FILES['file'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'No file uploaded.']);
    exit;
}

$file = $_FILES['file'];

// bežné chyby PHP uploadu
if (!isset($file['error']) || is_array($file['error'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid upload parameters.']);
    exit;
}

if ($file['error'] !== UPLOAD_ERR_OK) {
    $msg = 'Upload error code: ' . $file['error'];
    echo json_encode(['status' => 'error', 'message' => $msg]);
    exit;
}

// overit, ci je to doopravdy upload
if (!is_uploaded_file($file['tmp_name'])) {
    echo json_encode(['status' => 'error', 'message' => 'File not uploaded via HTTP POST.']);
    exit;
}

// vytvorenie (alebo overenie) tmp adresara relativne k tomuto skriptu
$baseDir = __DIR__;                // api/
$tmpDir = $baseDir . DIRECTORY_SEPARATOR . 'tmp';

// ak tmp neexistuje, vytvor ho (and set permissions)
if (!is_dir($tmpDir)) {
    if (!mkdir($tmpDir, 0755, true) && !is_dir($tmpDir)) {
        echo json_encode(['status' => 'error', 'message' => 'Cannot create tmp dir.']);
        exit;
    }
}

// sanitizuj nazov suboru (odstranime diakritiku a nebezpecne znaky)
$originalName = $file['name'];
// nahradenie diakritiky - jednoduché odtranenie UTF znakov na ASCII fallback
// Lepšie riešenie je iconv, ale nie vždy je iconv povolený. Skúsime kombináciu:
$san = $originalName;
// nahradíme lomítka, nulové byty atď.
$san = str_replace(["\0", "/", "\\", ".."], '_', $san);
// odstránime riadky kontroly
$san = trim($san);

// odstránime diakritiku cez translit (ak možno)
$trans = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $san);
if ($trans !== false) {
    $san = $trans;
}

// teraz povolíme iba znaky, písmena, čísla, pomlčky, bodky a podtržníky
$san = preg_replace('/[^A-Za-z0-9\-\._ ]+/', '', $san);
// nahraď medzery podtržníkom
$san = str_replace(' ', '_', $san);
// skráť názov ak je príliš dlhý
$san = mb_substr($san, 0, 200);

// ak po sanitizacii nic nezostalo, vytvor fallback meno
if ($san === '') {
    $san = 'upload_' . bin2hex(random_bytes(6));
}

// pridáme timestamp, aby sa minimalizovala kolízia mien
$ext = pathinfo($san, PATHINFO_EXTENSION);
$base = pathinfo($san, PATHINFO_FILENAME);
$timestamp = date('Ymd_His');
$targetName = $base . '_' . $timestamp . ($ext ? '.' . $ext : '');

$targetPath = $tmpDir . DIRECTORY_SEPARATOR . $targetName;

// presunieme subor
if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
    echo json_encode(['status' => 'error', 'message' => 'Unable to move uploaded file.']);
    exit;
}

// nastav pristupne prava
@chmod($targetPath, 0644);

// priprav URL pre prezeranie cez web (relativne k document root)
$publicUrl = '/tmp/' . rawurlencode($targetName);

// vratime json s informaciami
echo json_encode([
    'status' => 'ok',
    'path' => $targetPath,       // interná cesta (uľahčí debugging)
    'name' => $targetName,
    'url'  => $publicUrl
]);
exit;
