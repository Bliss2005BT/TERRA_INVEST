<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin();

$file = basename((string) ($_GET['file'] ?? ''));
$viewInline = isset($_GET['view']) && (string) $_GET['view'] === '1';
if ($file === '' || preg_match('/\.\./', $file)) {
    http_response_code(400);
    exit('Invalid file request.');
}

$baseDir = realpath(DOCUMENT_UPLOAD_DIR);
$fullPath = realpath(DOCUMENT_UPLOAD_DIR . $file);
if (!$baseDir || !$fullPath || !str_starts_with(str_replace('\\', '/', $fullPath), str_replace('\\', '/', $baseDir)) || !is_file($fullPath)) {
    http_response_code(404);
    exit('Document not found.');
}

$mime = 'application/octet-stream';
if (function_exists('finfo_open')) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    if ($finfo) {
        $detected = finfo_file($finfo, $fullPath);
        finfo_close($finfo);
        if (is_string($detected) && $detected !== '') {
            $mime = $detected;
        }
    }
}

header('Content-Description: File Transfer');
header('Content-Type: ' . $mime);
header('Content-Disposition: ' . ($viewInline ? 'inline' : 'attachment') . '; filename="' . basename($fullPath) . '"');
header('Content-Length: ' . (string) filesize($fullPath));
header('Cache-Control: private, no-store, no-cache, must-revalidate');
readfile($fullPath);
exit();
