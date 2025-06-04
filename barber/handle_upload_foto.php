<?php
session_start();
require_once '../config/database.php'; // Ajuste o caminho conforme necessário

// Verifica se o usuário está logado e se é barbeiro
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'barber') {
    // Redirecionar ou enviar erro se não for autorizado
    $_SESSION['form_message'] = "Acesso não autorizado para upload.";
    $_SESSION['form_message_type'] = "error";
    header("location: ../login.php"); // Ou para dashboard.php com mensagem de erro
    exit;
}

$barbeiro_id = $_SESSION["user_id"];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $legenda = isset($_POST['caption']) ? trim($_POST['caption']) : null;

    // Verifica se o arquivo foi enviado sem erros
    if (isset($_FILES["fotoCliente"]) && $_FILES["fotoCliente"]["error"] == 0) {
        $allowed_types = array("jpg" => "image/jpeg", "jpeg" => "image/jpeg", "png" => "image/png", "gif" => "image/gif");
        $filename = $_FILES["fotoCliente"]["name"];
        $filetype = $_FILES["fotoCliente"]["type"];
        $filesize = $_FILES["fotoCliente"]["size"];
        $temp_filepath = $_FILES["fotoCliente"]["tmp_name"];

        // Verifica a extensão do arquivo
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        if (!array_key_exists(strtolower($ext), $allowed_types)) {
            $_SESSION['form_message'] = "Erro: Formato de arquivo inválido. Apenas JPG, JPEG, PNG e GIF são permitidos.";
            $_SESSION['form_message_type'] = "error";
            header("location: dashboard.php");
            exit;
        }

        // Verifica o tipo MIME do arquivo
        if (!in_array($filetype, $allowed_types)) {
            $_SESSION['form_message'] = "Erro: Tipo de arquivo inválido.";
            $_SESSION['form_message_type'] = "error";
            header("location: dashboard.php");
            exit;
        }

        // Verifica o tamanho do arquivo (ex: 5MB limite)
        $maxsize = 5 * 1024 * 1024; // 5MB
        if ($filesize > $maxsize) {
            $_SESSION['form_message'] = "Erro: O arquivo é maior que o limite de 5MB.";
            $_SESSION['form_message_type'] = "error";
            header("location: dashboard.php");
            exit;
        }

        // Define o caminho de destino e cria um nome de arquivo único
        // Usar '../' para voltar um nível da pasta 'barber' para a raiz do projeto
        $upload_dir = "../uploads/fotos_mural/";
        // Cria um nome de arquivo único para evitar sobrescrever arquivos existentes
        $new_filename = $barbeiro_id . "_" . uniqid() . "." . $ext;
        $destination_path = $upload_dir . $new_filename;

        // Tenta mover o arquivo para o diretório de uploads
        if (move_uploaded_file($temp_filepath, $destination_path)) {
            // Arquivo movido com sucesso, agora insere no banco de dados
            $caminho_imagem_db = "uploads/fotos_mural/" . $new_filename; // Caminho relativo para armazenar no BD

            $sql = "INSERT INTO Fotos_Barbeiro (barbeiro_id, caminho_imagem, legenda) VALUES (?, ?, ?)";
            if ($stmt = $mysqli->prepare($sql)) {
                $stmt->bind_param("iss", $barbeiro_id, $caminho_imagem_db, $legenda);
                if ($stmt->execute()) {
                    $_SESSION['form_message'] = "Foto enviada com sucesso!";
                    $_SESSION['form_message_type'] = "success";
                } else {
                    $_SESSION['form_message'] = "Erro ao salvar informações da foto no banco de dados: " . $stmt->error;
                    $_SESSION['form_message_type'] = "error";
                    // Opcional: deletar o arquivo se o registro no BD falhar
                    // unlink($destination_path);
                }
                $stmt->close();
            } else {
                $_SESSION['form_message'] = "Erro ao preparar para salvar no BD: " . $mysqli->error;
                $_SESSION['form_message_type'] = "error";
                // unlink($destination_path);
            }
        } else {
            $_SESSION['form_message'] = "Erro ao mover o arquivo enviado. Verifique as permissões da pasta uploads.";
            $_SESSION['form_message_type'] = "error";
        }
    } else {
        $_SESSION['form_message'] = "Erro no upload: " . $_FILES["fotoCliente"]["error"];
        if ($_FILES["fotoCliente"]["error"] == UPLOAD_ERR_INI_SIZE || $_FILES["fotoCliente"]["error"] == UPLOAD_ERR_FORM_SIZE) {
            $_SESSION['form_message'] = "Erro: O arquivo enviado excede o tamanho máximo permitido.";
        } elseif ($_FILES["fotoCliente"]["error"] == UPLOAD_ERR_NO_FILE) {
             $_SESSION['form_message'] = "Erro: Nenhum arquivo foi enviado.";
        }
        $_SESSION['form_message_type'] = "error";
    }

    $mysqli->close();
    header("location: dashboard.php");
    exit;

} else {
    // Se não for POST, redireciona
    $_SESSION['form_message'] = "Método não permitido para upload.";
    $_SESSION['form_message_type'] = "error";
    header("location: dashboard.php");
    exit;
}
?>