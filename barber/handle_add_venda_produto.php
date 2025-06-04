<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'barber') {
    header("location: ../login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $barbeiro_id = $_SESSION["user_id"];
    $produto_id = isset($_POST['produto_id']) ? (int)$_POST['produto_id'] : 0;
    $quantidade = isset($_POST['quantidade']) && is_numeric($_POST['quantidade']) ? (int)$_POST['quantidade'] : 0;
    $metodo_pagamento = isset($_POST['metodo_pagamento']) ? trim($_POST['metodo_pagamento']) : 'dinheiro';

    if (empty($produto_id) || $quantidade <= 0) {
        $_SESSION['form_message'] = "Erro: Produto ou quantidade inválida.";
        $_SESSION['form_message_type'] = "error";
        header("location: dashboard.php");
        exit;
    }

    $preco_unitario = 0.00;
    $sql_preco_produto = "SELECT preco FROM produtos WHERE produto_id = ?"; // Adapte 'produtos' e 'produto_id'
     if ($stmt_preco_prod = $mysqli->prepare($sql_preco_produto)) {
        $stmt_preco_prod->bind_param("i", $produto_id);
        $stmt_preco_prod->execute();
        $result_preco_prod = $stmt_preco_prod->get_result();
        if ($prod_info = $result_preco_prod->fetch_assoc()) {
            $preco_unitario = (float)$prod_info['preco'];
        } else {
            $_SESSION['form_message'] = "Erro: Produto selecionado é inválido ou não tem preço.";
            $_SESSION['form_message_type'] = "error";
            header("location: dashboard.php");
            exit;
        }
        $stmt_preco_prod->close();
    } else {
        $_SESSION['form_message'] = "Erro ao buscar preço do produto.";
        $_SESSION['form_message_type'] = "error";
        header("location: dashboard.php");
        exit;
    }

    $valor_total_venda = $preco_unitario * $quantidade;

    $sql = "INSERT INTO vendas_produtos (barbeiro_id, produto_id, quantidade, preco_unitario_venda, valor_total_venda, metodo_pagamento) VALUES (?, ?, ?, ?, ?, ?)";
    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param("iiidds", $barbeiro_id, $produto_id, $quantidade, $preco_unitario, $valor_total_venda, $metodo_pagamento);
        if ($stmt->execute()) {
            $_SESSION['form_message'] = "Venda de produto registrada com sucesso!";
            $_SESSION['form_message_type'] = "success";
        } else {
            $_SESSION['form_message'] = "Erro ao registrar venda: " . $stmt->error;
            $_SESSION['form_message_type'] = "error";
        }
        $stmt->close();
    } else {
        $_SESSION['form_message'] = "Erro ao preparar registro de venda: " . $mysqli->error;
        $_SESSION['form_message_type'] = "error";
    }
    $mysqli->close();
    header("location: dashboard.php");
    exit;
}
?>