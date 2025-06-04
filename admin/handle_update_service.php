<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'admin') {
    $_SESSION['message'] = "Acesso não autorizado.";
    $_SESSION['message_type'] = "error";
    header("location: ../login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['service_id']) || !is_numeric($_POST['service_id'])) {
        $_SESSION['message'] = "ID do serviço inválido.";
        $_SESSION['message_type'] = "error";
        header("location: manage_services.php");
        exit;
    }

    $service_id = (int)$_POST['service_id'];
    $nome = trim($_POST['service_nome']);
    $preco_input = trim($_POST['service_preco']);
    $descricao = trim($_POST['service_descricao']);
    $ativo = isset($_POST['service_ativo']) ? (int)$_POST['service_ativo'] : 0; // Garante que seja 0 ou 1

    $errors = [];

    // Validações
    if (empty($nome)) {
        $errors[] = "O nome do serviço é obrigatório.";
    } else {
        // Verifica se o novo nome já existe para OUTRO serviço
        $sql_check_name = "SELECT service_id FROM servicos WHERE nome = ? AND service_id != ?";
        if ($stmt_check = $mysqli->prepare($sql_check_name)) {
            $stmt_check->bind_param("si", $nome, $service_id);
            $stmt_check->execute();
            $stmt_check->store_result();
            if ($stmt_check->num_rows > 0) {
                $errors[] = "Já existe outro serviço cadastrado com este nome.";
            }
            $stmt_check->close();
        } else {
            $errors[] = "Erro ao verificar nome do serviço: " . $mysqli->error;
        }
    }

    if (empty($preco_input)) {
        $errors[] = "O preço do serviço é obrigatório.";
    } elseif (!is_numeric($preco_input) || $preco_input < 0) {
        $errors[] = "O preço deve ser um número positivo.";
    }
    $preco = (float)$preco_input;

    if (!in_array($ativo, [0, 1])) {
        $errors[] = "Status inválido."; // Validação extra
    }

    if (empty($errors)) {
        $sql_update = "UPDATE servicos SET nome = ?, preco = ?, descricao = ?, ativo = ? WHERE service_id = ?";
        if ($stmt_update = $mysqli->prepare($sql_update)) {
            $stmt_update->bind_param("sdsii", $nome, $preco, $descricao, $ativo, $service_id);

            if ($stmt_update->execute()) {
                $_SESSION['message'] = "Serviço '".htmlspecialchars($nome)."' atualizado com sucesso!";
                $_SESSION['message_type'] = "success";
                header("location: manage_services.php");
                exit;
            } else {
                $_SESSION['message'] = "Erro ao atualizar serviço: " . $stmt_update->error;
                $_SESSION['message_type'] = "error";
            }
            $stmt_update->close();
        } else {
            $_SESSION['message'] = "Erro ao preparar para atualizar serviço: " . $mysqli->error;
            $_SESSION['message_type'] = "error";
        }
    } else {
        $_SESSION['message'] = implode("<br>", $errors);
        $_SESSION['message_type'] = "error";
    }

    // Se houve erro, redireciona de volta para a página de edição com o ID
    $mysqli->close();
    header("location: edit_service.php?id=" . $service_id);
    exit;

} else {
    $_SESSION['message'] = "Método não permitido.";
    $_SESSION['message_type'] = "error";
    header("location: manage_services.php");
    exit;
}
?>