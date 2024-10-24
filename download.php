<?php
include 'db.php';
session_start();
if (!isset($_SESSION['user_id'])) header('Location: index.php');

if (isset($_GET['id'])) {
    $user_id = $_SESSION['user_id'];
    $file_id = $_GET['id'];

    $stmt = $conn->prepare("SELECT * FROM files WHERE id = ? AND user_id = ?");
    $stmt->execute([$file_id, $user_id]);
    $file = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($file) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($file['filename']) . '"');
        header('Content-Length: ' . filesize($file['filepath']));
        readfile($file['filepath']);
        exit;
    }
}
?>
