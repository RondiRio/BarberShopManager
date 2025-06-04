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
    $service_name = trim($_POST['service_name']);
    $service_price_input = trim($_POST['service_price']);
    $service_description = trim($_POST['service_description']); // Pode ser vazio
    $is_active = 1; // Novos serviços começam ativos por padrão

    $errors = [];

    // Validações
    if (empty($service_name)) {
        $errors[] = "O nome do serviço é obrigatório.";
    } else {
        // Verifica se o nome do serviço já existe
        $sql_check_name = "SELECT service_id FROM servicos WHERE nome = ?";
        if ($stmt_check = $mysqli->prepare($sql_check_name)) {
            $stmt_check->bind_param("s", $service_name);
            $stmt_check->execute();
            $stmt_check->store_result();
            if ($stmt_check->num_rows > 0) {
                $errors[] = "Um serviço com este nome já existe.";
            }
            $stmt_check->close();
        } else {
            $errors[] = "Erro ao verificar nome do serviço: " . $mysqli->error;
        }
    }

    if (empty($service_price_input)) {
        $errors[] = "O preço do serviço é obrigatório.";
    } elseif (!is_numeric($service_price_input) || $service_price_input < 0) {
        $errors[] = "O preço deve ser um número positivo.";
    }
    $service_price = (float) $service_price_input; // Converte para float

    if (empty($errors)) {
        $sql_insert = "INSERT INTO servicos (nome, preco, descricao, ativo) VALUES (?, ?, ?, ?)";
        if ($stmt_insert = $mysqli->prepare($sql_insert)) {
            $stmt_insert->bind_param("sdsi", $service_name, $service_price, $service_description, $is_active);

            if ($stmt_insert->execute()) {
                $_SESSION['message'] = "Serviço '".htmlspecialchars($service_name)."' adicionado com sucesso!";
                $_SESSION['message_type'] = "success";
            } else {
                $_SESSION['message'] = "Erro ao adicionar serviço: " . $stmt_insert->error;
                $_SESSION['message_type'] = "error";
            }
            $stmt_insert->close();
        } else {
            $_SESSION['message'] = "Erro ao preparar para adicionar serviço: " . $mysqli->error;
            $_SESSION['message_type'] = "error";
        }
    } else {
        $_SESSION['message'] = implode("<br>", $errors);
        $_SESSION['message_type'] = "error";
    }

    $mysqli->close();
    header("location: manage_services.php");
    exit;

} else {
    $_SESSION['message'] = "Método não permitido.";
    $_SESSION['message_type'] = "error";
    header("location: manage_services.php");
    exit;
}
?>