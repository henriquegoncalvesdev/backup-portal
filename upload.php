<?php
include 'db.php';
session_start();
if (!isset($_SESSION['user_id'])) header('Location: index.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file'])) {
    $user_id = $_SESSION['user_id'];
    $filename = $_FILES['file']['name'];
    $filepath = 'uploads/' . basename($filename);
    $size = $_FILES['file']['size'];

    if (move_uploaded_file($_FILES['file']['tmp_name'], $filepath)) {
        $stmt = $conn->prepare("INSERT INTO files (user_id, filename, filepath, size) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$user_id, $filename, $filepath, $size])) {
            header('Location: dashboard.php');
        }
    }
}
?>
