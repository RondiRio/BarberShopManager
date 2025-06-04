<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'barber') {
    header("location: ../login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $barbeiro_id = $_SESSION["user_id"];
    $valor_vale = isset($_POST['valor_vale']) && is_numeric($_POST['valor_vale']) ? (float)$_POST['valor_vale'] : 0.00;
    $descricao_vale = isset($_POST['descricao_vale']) ? trim($_POST['descricao_vale']) : null;

    if ($valor_vale <= 0) {
        $_SESSION['form_message'] = "Erro: Valor do vale deve ser positivo.";
        $_SESSION['form_message_type'] = "error";
        header("location: dashboard.php");
        exit;
    }

    $sql = "INSERT INTO vales_barbeiro (barbeiro_id, valor, descricao) VALUES (?, ?, ?)";
    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param("ids", $barbeiro_id, $valor_vale, $descricao_vale);
        if ($stmt->execute()) {
            $_SESSION['form_message'] = "Vale registrado com sucesso!";
            $_SESSION['form_message_type'] = "success";
        } else {
            $_SESSION['form_message'] = "Erro ao registrar vale: " . $stmt->error;
            $_SESSION['form_message_type'] = "error";
        }
        $stmt->close();
    } else {
        $_SESSION['form_message'] = "Erro ao preparar registro de vale: " . $mysqli->error;
        $_SESSION['form_message_type'] = "error";
    }
    $mysqli->close();
    header("location: dashboard.php");
    exit;
}
?>