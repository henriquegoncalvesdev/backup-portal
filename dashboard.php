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
        </div>
        <button type="button" class="btn btn-primary" onclick="uploadFile()">Fazer Upload</button>
        <button type="button" id="cancelButton" class="btn btn-secondary" style="display:none;" onclick="cancelUpload()">Cancelar</button>
    </form>

    <!-- Barra de Progresso e Status -->
    <div class="progress">
        <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
    </div>
    <div id="uploadStatus"></div>
    <div id="uploadInfo"></div>

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
        $user_id = $_SESSION['user_id'];
        $stmt = $conn->prepare("SELECT * FROM files WHERE user_id = ? ORDER BY upload_date DESC");
        $stmt->execute([$user_id]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>
                    <td>{$row['filename']}</td>
                    <td>{$row['upload_date']}</td>
                    <td>
                        <a href='download.php?id={$row['id']}' class='btn btn-success'>Baixar</a>
                        <a href='delete.php?id={$row['id']}' class='btn btn-danger'>Deletar</a>
                    </td>
                  </tr>";
        }
        ?>
        </tbody>
    </table>
</div>

<script>
    let xhr;
    let startTime; // Define o início do upload para calcular a velocidade e o tempo restante

    function uploadFile() {
        const fileInput = document.getElementById('file');
        const file = fileInput.files[0];
        if (!file) return;

        const formData = new FormData();
        formData.append('file', file);

        xhr = new XMLHttpRequest();

        // Exibir a barra de progresso e o botão cancelar
        document.querySelector('.progress').style.display = 'block';
        document.getElementById('cancelButton').style.display = 'inline-block';
        document.getElementById('uploadStatus').innerText = '';
        document.getElementById('uploadInfo').innerText = '';

        startTime = new Date().getTime(); // Captura o tempo de início em milissegundos

        xhr.upload.addEventListener('progress', updateProgress);
        xhr.open('POST', 'upload.php', true);

        // Função para atualizar a barra de progresso e exibir informações adicionais
        xhr.upload.onprogress = function (event) {
            if (event.lengthComputable) {
                const percentComplete = (event.loaded / event.total) * 100;
                const currentTime = new Date().getTime();
                const timeElapsed = (currentTime - startTime) / 1000; // Tempo em segundos

                const uploadRate = (event.loaded / 1024 / 1024 / timeElapsed).toFixed(2); // Taxa em MB/s
                const remainingTime = ((event.total - event.loaded) / (event.loaded / timeElapsed)).toFixed(2); // Tempo restante em segundos

                document.querySelector('.progress-bar').style.width = percentComplete + '%';
                document.querySelector('.progress-bar').innerText = Math.floor(percentComplete) + '%';
                document.getElementById('uploadInfo').innerText = `Velocidade: ${uploadRate} MB/s | Tempo restante: ${remainingTime} s`;
            }
        };

        // Função de resposta
        xhr.onload = function () {
            const response = JSON.parse(xhr.responseText);
            if (response.status === 'success') {
                document.getElementById('uploadStatus').innerHTML = "<div class='alert alert-success'>" + response.message + "</div>";
                document.querySelector('.progress-bar').style.width = '100%';
                document.querySelector('.progress-bar').innerText = '100%';
                location.reload();
            } else {
                document.getElementById('uploadStatus').innerHTML = "<div class='alert alert-danger'>" + response.message + "</div>";
            }
            document.getElementById('cancelButton').style.display = 'none';
        };

        xhr.onerror = function () {
            document.getElementById('uploadStatus').innerHTML = "<div class='alert alert-danger'>Erro ao enviar o arquivo.</div>";
        };

        xhr.send(formData);
    }

    function updateProgress(event) {
        if (event.lengthComputable) {
            const percentComplete = Math.floor((event.loaded / event.total) * 100);
            document.querySelector('.progress-bar').style.width = percentComplete + '%';
            document.querySelector('.progress-bar').innerText = percentComplete + '%';
        }
    }

    // Função para cancelar o upload
    function cancelUpload() {
        if (xhr) {
            xhr.abort();
            document.getElementById('uploadStatus').innerHTML = "<div class='alert alert-warning'>Upload cancelado pelo usuário.</div>";
            document.querySelector('.progress').style.display = 'none';
            document.getElementById('cancelButton').style.display = 'none';
            document.getElementById('uploadInfo').innerText = '';
        }
    }
</script>

</body>
</html>
