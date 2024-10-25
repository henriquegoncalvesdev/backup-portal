<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$uploadMessage = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file'])) {
    $user_id = $_SESSION['user_id'];
    $folder = $_POST['folder'] ?? 'root';
    $filename = $_FILES['file']['name'];
    $filepath = 'uploads/' . $user_id . '/' . $folder . '/' . basename($filename);
    $size = $_FILES['file']['size'];

    if (!file_exists("uploads/$user_id/$folder")) {
        mkdir("uploads/$user_id/$folder", 0777, true);
    }

    if (move_uploaded_file($_FILES['file']['tmp_name'], $filepath)) {
        $stmt = $conn->prepare("INSERT INTO files (user_id, filename, filepath, size, folder) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute([$user_id, $filename, $filepath, $size, $folder])) {
            $uploadMessage = "<div class='alert alert-success'>Upload realizado com sucesso!</div>";
        } else {
            $uploadMessage = "<div class='alert alert-danger'>Erro ao salvar as informações do arquivo no banco de dados.</div>";
        }
    } else {
        $uploadMessage = "<div class='alert alert-danger'>Erro ao enviar o arquivo. Tente novamente.</div>";
    }
}

if (isset($_POST['new_folder'])) {
    $folder_name = $_POST['new_folder'];
    if (!file_exists("uploads/{$_SESSION['user_id']}/$folder_name")) {
        mkdir("uploads/{$_SESSION['user_id']}/$folder_name", 0777, true);
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        .progress { display: none; margin-top: 10px; }
        .progress-bar { width: 0%; }
        .folder { cursor: pointer; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <a class="navbar-brand" href="#">Seu Aplicativo</a>
    <div class="collapse navbar-collapse">
        <ul class="navbar-nav mr-auto"></ul>
        <a href="logout.php" class="btn btn-danger my-2 my-sm-0">Logout</a>
    </div>
</nav>

<div class="container">
    <h2>Bem-vindo ao seu Portal de Backup</h2>

    <!-- Formulário de upload -->
    <form id="uploadForm" enctype="multipart/form-data">
        <div class="form-group">
            <label for="file">Enviar Arquivo:</label>
            <input type="file" class="form-control" id="file" name="file" required>
            <label for="folder">Selecione a pasta:</label>
            <select class="form-control" name="folder" id="folder">
                <option value="root">Pasta Raiz</option>
                <?php
                $user_id = $_SESSION['user_id'];
                $stmt = $conn->prepare("SELECT DISTINCT folder FROM files WHERE user_id = ?");
                $stmt->execute([$user_id]);
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    echo "<option value='{$row['folder']}'>{$row['folder']}</option>";
                }
                ?>
            </select>
        </div>
        <button type="button" class="btn btn-primary" onclick="uploadFile()">Fazer Upload</button>
        <button type="button" id="cancelButton" class="btn btn-secondary" style="display:none;" onclick="cancelUpload()">Cancelar</button>
    </form>

    <form method="POST" class="mt-3">
        <div class="form-group">
            <input type="text" class="form-control" name="new_folder" placeholder="Nova Pasta">
        </div>
        <button type="submit" class="btn btn-success">Criar Pasta</button>
    </form>

    <!-- Barra de Progresso e Status -->
    <div class="progress">
        <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
    </div>
    <div id="uploadStatus"></div>

    <!-- Listar arquivos por pasta -->
    <h3>Seus Arquivos</h3>
    <?php
    $stmt = $conn->prepare("SELECT DISTINCT folder FROM files WHERE user_id = ?");
    $stmt->execute([$user_id]);
    while ($folderRow = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $folderName = $folderRow['folder'];
        echo "<h4 class='folder'>$folderName</h4>";
        echo "<table class='table table-striped'>
                <thead>
                    <tr>
                        <th>Nome do Arquivo</th>
                        <th>Data do Upload</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>";
        $fileStmt = $conn->prepare("SELECT * FROM files WHERE user_id = ? AND folder = ? ORDER BY upload_date DESC");
        $fileStmt->execute([$user_id, $folderName]);
        while ($row = $fileStmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>
                    <td>{$row['filename']}</td>
                    <td>{$row['upload_date']}</td>
                    <td>
                        <a href='download.php?id={$row['id']}' class='btn btn-success'>Baixar</a>
                        <a href='delete.php?id={$row['id']}' class='btn btn-danger'>Deletar</a>
                    </td>
                  </tr>";
        }
        echo "</tbody></table>";
    }
    ?>

</div>

<script>
    let xhr;

    function uploadFile() {
        const fileInput = document.getElementById('file');
        const file = fileInput.files[0];
        if (!file) return;

        const formData = new FormData();
        formData.append('file', file);
        formData.append('folder', document.getElementById('folder').value);

        xhr = new XMLHttpRequest();
        document.querySelector('.progress').style.display = 'block';
        document.getElementById('cancelButton').style.display = 'inline-block';
        document.getElementById('uploadStatus').innerText = '';

        xhr.upload.onprogress = function (event) {
            if (event.lengthComputable) {
                const percentComplete = (event.loaded / event.total) * 100;
                document.querySelector('.progress-bar').style.width = percentComplete + '%';
                document.querySelector('.progress-bar').innerText = Math.floor(percentComplete) + '%';
            }
        };

        xhr.onload = function () {
            location.reload();
        };

        xhr.onerror = function () {
            document.getElementById('uploadStatus').innerHTML = "<div class='alert alert-danger'>Erro ao enviar o arquivo.</div>";
        };

        xhr.open('POST', 'upload.php', true);
        xhr.send(formData);
    }

    function cancelUpload() {
        if (xhr) {
            xhr.abort();
            document.getElementById('uploadStatus').innerHTML = "<div class='alert alert-warning'>Upload cancelado pelo usuário.</div>";
            document.querySelector('.progress').style.display = 'none';
            document.getElementById('cancelButton').style.display = 'none';
        }
    }
</script>

</body>
</html>
