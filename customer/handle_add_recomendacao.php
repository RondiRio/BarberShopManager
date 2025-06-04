<?php
session_start();
require_once '../config/database.php'; // Ajuste o caminho

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'customer') {
    $_SESSION['customer_message'] = "Você precisa estar logado como cliente para enviar uma recomendação.";
    $_SESSION['customer_message_type'] = "error";
    header("location: ../login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $cliente_id = $_SESSION["user_id"];
    $nome_cliente = $_SESSION["name"]; // Usaremos o nome da sessão
    $barbeiro_id = isset($_POST['barbeiro_id']) ? (int)$_POST['barbeiro_id'] : null;
    $texto_recomendacao = isset($_POST['texto_recomendacao']) ? trim($_POST['texto_recomendacao']) : '';

    if (empty($barbeiro_id)) {
        $_SESSION['customer_message'] = "Por favor, selecione o barbeiro.";
        $_SESSION['customer_message_type'] = "error";
    } elseif (empty($texto_recomendacao)) {
        $_SESSION['customer_message'] = "Por favor, escreva sua recomendação.";
        $_SESSION['customer_message_type'] = "error";
    } else {
        // Insere a recomendação com aprovado = 0 (pendente)
        $sql = "INSERT INTO Recomendacoes (cliente_id, nome_cliente, barbeiro_id, texto_recomendacao, aprovado) VALUES (?, ?, ?, ?, 0)";
        if ($stmt = $mysqli->prepare($sql)) {
            $stmt->bind_param("isss", $cliente_id, $nome_cliente, $barbeiro_id, $texto_recomendacao);
            if ($stmt->execute()) {
                $_SESSION['customer_message'] = "Obrigado! Sua recomendação foi enviada e será analisada.";
                $_SESSION['customer_message_type'] = "success";
            } else {
                $_SESSION['customer_message'] = "Erro ao enviar sua recomendação: " . $stmt->error;
                $_SESSION['customer_message_type'] = "error";
            }
            $stmt->close();
        } else {
            $_SESSION['customer_message'] = "Erro ao preparar sua recomendação: " . $mysqli->error;
            $_SESSION['customer_message_type'] = "error";
        }
    }
    $mysqli->close();
    header("location: dashboard.php");
    exit;
} else {
    header("location: dashboard.php");
    exit;
}
?>