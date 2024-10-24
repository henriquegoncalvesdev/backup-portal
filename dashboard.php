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
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
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

    <!-- Formulário de upload com barra de progresso -->
    <form id="uploadForm" method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label for="file">Enviar Arquivo:</label>
            <input type="file" class="form-control" name="file" id="fileInput" required>
        </div>
        <button type="submit" class="btn btn-primary">Fazer Upload</button>
    </form>

    <!-- Barra de progresso -->
    <div class="progress mt-3" style="display: none;" id="progressWrapper">
        <div class="progress-bar progress-bar-striped progress-bar-animated" id="progressBar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%"></div>
    </div>
    <div id="progressText" class="mt-2"></div>

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
                        <a href='delete.php?id={$row['id']}' class='btn btn-danger'>Deletar</a>
                    </td>
                  </tr>";
        }
        ?>
        </tbody>
    </table>
</div>

<script>
// Função para lidar com o upload e mostrar a barra de progresso
$('#uploadForm').on('submit', function(e) {
    e.preventDefault(); // Impede o envio padrão do formulário
    
    var formData = new FormData(this); // Cria o FormData
    var fileInput = $('#fileInput').val();
    
    if (!fileInput) {
        alert('Selecione um arquivo.');
        return;
    }

    $.ajax({
        xhr: function() {
            var xhr = new window.XMLHttpRequest();
            // Progresso do upload
            xhr.upload.addEventListener('progress', function(e) {
                if (e.lengthComputable) {
                    var percent = Math.round((e.loaded / e.total) * 100);
                    $('#progressWrapper').show();
                    $('#progressBar').css('width', percent + '%');
                    $('#progressBar').attr('aria-valuenow', percent);
                    $('#progressText').text('Progresso: ' + percent + '%');
                }
            }, false);
            return xhr;
        },
        type: 'POST',
        url: 'dashboard.php', // Envia o arquivo para a própria página
        data: formData,
        contentType: false,
        processData: false,
        success: function(response) {
            location.reload(); // Recarrega a página ao finalizar o upload
        },
        error: function() {
            alert('Erro ao enviar o arquivo. Tente novamente.');
        }
    });
});
</script>

</body>
</html>
