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
    if (!isset($_POST['produto_id']) || !is_numeric($_POST['produto_id'])) {
        $_SESSION['message'] = "ID do produto inválido.";
        $_SESSION['message_type'] = "error";
        header("location: manage_products.php");
        exit;
    }

    $produto_id = (int)$_POST['produto_id'];
    $nome = trim($_POST['product_nome']);
    $preco_input = trim($_POST['product_preco']);
    $descricao = trim($_POST['product_descricao']);
    $ativo = isset($_POST['product_ativo']) ? (int)$_POST['product_ativo'] : 0;

    $errors = [];

    if (empty($nome)) {
        $errors[] = "O nome do produto é obrigatório.";
    } else {
        $sql_check_name = "SELECT produto_id FROM produtos WHERE nome = ? AND produto_id != ?";
        if ($stmt_check = $mysqli->prepare($sql_check_name)) {
            $stmt_check->bind_param("si", $nome, $produto_id);
            $stmt_check->execute();
            $stmt_check->store_result();
            if ($stmt_check->num_rows > 0) {
                $errors[] = "Já existe outro produto cadastrado com este nome.";
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

    if (!in_array($ativo, [0, 1])) {
        $errors[] = "Status inválido.";
    }

    if (empty($errors)) {
        $sql_update = "UPDATE produtos SET nome = ?, preco = ?, descricao = ?, ativo = ? WHERE produto_id = ?";
        if ($stmt_update = $mysqli->prepare($sql_update)) {
            $stmt_update->bind_param("sdsii", $nome, $preco, $descricao, $ativo, $produto_id);

            if ($stmt_update->execute()) {
                $_SESSION['message'] = "Produto '".htmlspecialchars($nome)."' atualizado com sucesso!";
                $_SESSION['message_type'] = "success";
                header("location: manage_products.php");
                exit;
            } else {
                $_SESSION['message'] = "Erro ao atualizar produto: " . $stmt_update->error;
                $_SESSION['message_type'] = "error";
            }
            $stmt_update->close();
        } else {
            $_SESSION['message'] = "Erro ao preparar para atualizar produto: " . $mysqli->error;
            $_SESSION['message_type'] = "error";
        }
    } else {
        // Se houver erro, armazena na sessão para exibir no formulário de edição
        $_SESSION['message_form'] = implode("<br>", $errors);
        $_SESSION['message_form_type'] = "error";
    }

    $mysqli->close();
    header("location: edit_product.php?id=" . $produto_id); // Volta para o form de edição com o ID
    exit;

} else {
    $_SESSION['message'] = "Método não permitido.";
    $_SESSION['message_type'] = "error";
    header("location: manage_products.php");
    exit;
}
?>