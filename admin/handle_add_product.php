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
    $nome = trim($_POST['product_nome']);
    $preco_input = trim($_POST['product_preco']);
    $descricao = trim($_POST['product_descricao']);
    $ativo = 1; // Novos produtos começam ativos

    $errors = [];

    if (empty($nome)) {
        $errors[] = "O nome do produto é obrigatório.";
    } else {
        $sql_check_name = "SELECT produto_id FROM produtos WHERE nome = ?";
        if ($stmt_check = $mysqli->prepare($sql_check_name)) {
            $stmt_check->bind_param("s", $nome);
            $stmt_check->execute();
            $stmt_check->store_result();
            if ($stmt_check->num_rows > 0) {
                $errors[] = "Um produto com este nome já existe.";
            }
            $stmt_check->close();
        } else {
            $errors[] = "Erro ao verificar nome do produto: " . $mysqli->error;
        }
    }

    if (empty($preco_input)) {
        $errors[] = "O preço do produto é obrigatório.";
    } elseif (!is_numeric($preco_input) || $preco_input < 0) {
        $errors[] = "O preço deve ser um número positivo.";
    }
    $preco = (float)$preco_input;

    if (empty($errors)) {
        $sql_insert = "INSERT INTO produtos (nome, preco, descricao, ativo) VALUES (?, ?, ?, ?)";
        if ($stmt_insert = $mysqli->prepare($sql_insert)) {
            $stmt_insert->bind_param("sdsi", $nome, $preco, $descricao, $ativo);

            if ($stmt_insert->execute()) {
                $_SESSION['message'] = "Produto '".htmlspecialchars($nome)."' adicionado com sucesso!";
                $_SESSION['message_type'] = "success";
            } else {
                $_SESSION['message'] = "Erro ao adicionar produto: " . $stmt_insert->error;
                $_SESSION['message_type'] = "error";
            }
            $stmt_insert->close();
        } else {
            $_SESSION['message'] = "Erro ao preparar para adicionar produto: " . $mysqli->error;
            $_SESSION['message_type'] = "error";
        }
    } else {
        $_SESSION['message'] = implode("<br>", $errors);
        $_SESSION['message_type'] = "error";
    }

    $mysqli->close();
    header("location: manage_products.php");
    exit;

} else {
    $_SESSION['message'] = "Método não permitido.";
    $_SESSION['message_type'] = "error";
    header("location: manage_products.php");
    exit;
}
?>