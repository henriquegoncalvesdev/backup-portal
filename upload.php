<?php
include 'db.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Usuário não autenticado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file'])) {
    $user_id = $_SESSION['user_id'];
    $filename = $_FILES['file']['name'];
    $filepath = 'uploads/' . basename($filename);
    $size = $_FILES['file']['size'];

    if (move_uploaded_file($_FILES['file']['tmp_name'], $filepath)) {
        $stmt = $conn->prepare("INSERT INTO files (user_id, filename, filepath, size) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$user_id, $filename, $filepath, $size])) {
            echo json_encode(['status' => 'success', 'message' => 'Upload realizado com sucesso!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Erro ao salvar as informações do arquivo no banco de dados.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Erro ao enviar o arquivo. Tente novamente.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Nenhum arquivo enviado.']);
}
?>
