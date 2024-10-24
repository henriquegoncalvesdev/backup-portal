<?php
include 'db.php';
session_start();

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Variável para armazenar mensagem de feedback
$uploadMessage = "";

// Processa o upload diretamente na página dashboard.php
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file'])) {
    $user_id = $_SESSION['user_id'];
    $filename = $_FILES['file']['name'];
    $filepath = 'uploads/' . basename($filename);
    $size = $_FILES['file']['size'];

    // Verifica se o arquivo foi enviado com sucesso
    if (move_uploaded_file($_FILES['file']['tmp_name'], $filepath)) {
        // Insere os detalhes do arquivo no banco de dados
        $stmt = $conn->prepare("INSERT INTO files (user_id, filename, filepath, size) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$user_id, $filename, $filepath, $size])) {
            $uploadMessage = "<div class='alert alert-success'>Upload realizado com sucesso!</div>";
        } else {
            $uploadMessage = "<div class='alert alert-danger'>Erro ao salvar as informações do arquivo no banco de dados.</div>";
        }
    } else {
        $uploadMessage = "<div class='alert alert-danger'>Erro ao enviar o arquivo. Tente novamente.</div>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light bg-light">
        <a class="navbar-brand" href="#">Seu Aplicativo</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav mr-auto">
                <!-- Outros itens do menu -->
            </ul>
            <a href="logout.php" class="btn btn-danger my-2 my-sm-0">Logout</a>
        </div>
</nav>
<div class="container">
    <h2>Bem-vindo ao seu Portal de Backup</h2>

    <!-- Formulário de upload -->
    <form action="dashboard.php" method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label for="file">Enviar Arquivo:</label>
            <input type="file" class="form-control" name="file" required>
        </div>
        <button type="submit" class="btn btn-primary">Fazer Upload</button>
    </form>

    <!-- Mensagem de Feedback do Upload -->
    <?php echo $uploadMessage; ?>

    <!-- Listar arquivos enviados pelo usuário -->
    <h3>Seus Arquivos</h3>
    <table class="table table-striped">
        <thead>
        <tr>
            <th>Nome do Arquivo</th>
            <th>Data do Upload</th>
            <th>Ações</th>
        </tr>
        </thead>
        <tbody>
        <?php
        // Seleciona apenas os arquivos do usuário logado
        $user_id = $_SESSION['user_id'];
        $stmt = $conn->prepare("SELECT * FROM files WHERE user_id = ? ORDER BY upload_date DESC");
        $stmt->execute([$user_id]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>
                    <td>{$row['filename']}</td>
                    <td>{$row['upload_date']}</td>
                    <td>
                        <a href='download.php?id={$row['id']}' class='btn btn-success'>Baixar</a>
                        <a href='edit.php?id={$row['id']}' class='btn btn-warning'>Editar</a>
                        <a href='delete.php?id={$row['id']}' class='btn btn-danger'>Deletar</a>
                    </td>
                  </tr>";
        }
        ?>
        </tbody>
    </table>
</div>
</body>
</html>
