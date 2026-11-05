<?php
require_once 'config.php';

// Session authentication
if (
    !isset($_SESSION['user_id'], $_SESSION['username'])
) {
    http_response_code(401);
    exit('Unauthorized');
}

// Document ID from route
$document_id = isset($id) ? (int) $id : 0;

if ($document_id <= 0) {
    http_response_code(400);
    exit('Invalid document ID.');
}

// Fetch document
$statement = $conn->prepare(
    'SELECT file_path, doc_type
     FROM member_documents
     WHERE id = ?
     LIMIT 1'
);

$statement->execute([$document_id]);
$document = $statement->fetch(PDO::FETCH_ASSOC);

// Validate filename
if (
    !$document ||
    !preg_match(
        '/^[a-f0-9]{32}\.(pdf|jpg|png|webp)$/i',
        (string) $document['file_path']
    )
) {
    http_response_code(404);
    exit('Document not found.');
}

// Resolve file path
$path = dirname(__DIR__, 3)
      . '/storage/member_documents/'
      . $document['file_path'];

// Check file
if (!is_file($path) || !is_readable($path)) {
    http_response_code(404);
    exit('Document file not found.');
}

// Detect actual MIME type
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($path);

$allowed_mimes = [
    'application/pdf',
    'image/jpeg',
    'image/png',
    'image/webp'
];

if (!in_array($mime, $allowed_mimes, true)) {
    http_response_code(415);
    exit('Unsupported document type.');
}

// Download mode
$download = isset($_GET['download'])
    && $_GET['download'] === '1';

// Clear output buffers to avoid corrupting the file
while (ob_get_level() > 0) {
    ob_end_clean();
}

// Response headers
header('Content-Type: ' . $mime);
header('Content-Length: ' . (string) filesize($path));
header(
    'Content-Disposition: '
    . ($download ? 'attachment' : 'inline')
    . '; filename="' . basename($path) . '"'
);
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');

// Output file
readfile($path);
exit;

