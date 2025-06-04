<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'barber') {
    header("location: ../login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $barbeiro_id = $_SESSION["user_id"];
    $servico_id = isset($_POST['servico_id']) ? (int)$_POST['servico_id'] : 0;
    $cliente_nome = isset($_POST['cliente_nome']) ? trim($_POST['cliente_nome']) : null;
    $gorjeta = isset($_POST['gorjeta']) && is_numeric($_POST['gorjeta']) ? (float)$_POST['gorjeta'] : 0.00;
    $metodo_pagamento = isset($_POST['metodo_pagamento']) ? trim($_POST['metodo_pagamento']) : 'dinheiro';

    if (empty($servico_id)) {
        $_SESSION['form_message'] = "Erro: Serviço não selecionado.";
        $_SESSION['form_message_type'] = "error";
        header("location: dashboard.php");
        exit;
    }

    // Buscar o preço do serviço no momento do cadastro para garantir consistência
    $preco_cobrado = 0.00;
    $sql_preco_servico = "SELECT preco FROM servicos WHERE service_id = ?"; // Adapte 'servicos' e 'service_id'
    if ($stmt_preco = $mysqli->prepare($sql_preco_servico)) {
        $stmt_preco->bind_param("i", $servico_id);
        $stmt_preco->execute();
        $result_preco = $stmt_preco->get_result();
        if ($serv_info = $result_preco->fetch_assoc()) {
            $preco_cobrado = (float)$serv_info['preco'];
        } else {
            $_SESSION['form_message'] = "Erro: Serviço selecionado é inválido ou não tem preço definido.";
            $_SESSION['form_message_type'] = "error";
            header("location: dashboard.php");
            exit;
        }
        $stmt_preco->close();
    } else {
        $_SESSION['form_message'] = "Erro ao buscar preço do serviço.";
        $_SESSION['form_message_type'] = "error";
        header("location: dashboard.php");
        exit;
    }


    $sql = "INSERT INTO atendimentos (barbeiro_id, servico_id, cliente_nome, preco_cobrado, gorjeta, metodo_pagamento) VALUES (?, ?, ?, ?, ?, ?)";
    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param("iisdds", $barbeiro_id, $servico_id, $cliente_nome, $preco_cobrado, $gorjeta, $metodo_pagamento);
        if ($stmt->execute()) {
            $_SESSION['form_message'] = "Atendimento registrado com sucesso!";
            $_SESSION['form_message_type'] = "success";
        } else {
            $_SESSION['form_message'] = "Erro ao registrar atendimento: " . $stmt->error;
            $_SESSION['form_message_type'] = "error";
        }
        $stmt->close();
    } else {
        $_SESSION['form_message'] = "Erro ao preparar registro de atendimento: " . $mysqli->error;
        $_SESSION['form_message_type'] = "error";
    }
    $mysqli->close();
    header("location: dashboard.php");
    exit;
}
?>