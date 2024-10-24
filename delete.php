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
        unlink($file['filepath']); // Delete the file from server
        $stmt = $conn->prepare("DELETE FROM files WHERE id = ?");
        $stmt->execute([$file_id]);
    }
    header('Location: dashboard.php');
}
?>
