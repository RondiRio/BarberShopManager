<?php
session_start();
require_once '../config/database.php';
header('Content-Type: application/json');

// Resposta padrão
$response = ['success' => false, 'message' => 'Ação inválida.'];

// Segurança
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION["loggedin"]) || $_SESSION["role"] !== 'barber') {
    echo json_encode($response);
    exit;
}
$barbeiro_id_logado = $_SESSION['user_id'];

$action = $_POST['action'] ?? '';
$agendamento_id = isset($_POST['agendamento_id']) ? (int)$_POST['agendamento_id'] : 0;

if (empty($action) || empty($agendamento_id)) {
    $response['message'] = 'Dados insuficientes.';
    echo json_encode($response);
    exit;
}

// --- LÓGICA DAS AÇÕES ---
switch ($action) {
    case 'nao_compareceu':
        $sql = "UPDATE agendamentos SET status = 'NaoCompareceu' WHERE agendamento_id = ? AND barbeiro_id = ?";
        if ($stmt = $mysqli->prepare($sql)) {
            $stmt->bind_param("ii", $agendamento_id, $barbeiro_id_logado);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $response = ['success' => true, 'message' => 'Status atualizado para Não Compareceu.'];
            } else {
                $response['message'] = 'Falha ao atualizar o status.';
            }
            $stmt->close();
        }
        break;

    case 'realizado':
        // Dados do formulário do modal
        $cliente_id = (int)$_POST['cliente_id'];
        $servico_id = (int)$_POST['servico_id'];
        $preco_cobrado = (float)$_POST['preco_cobrado'];
        $gorjeta = isset($_POST['gorjeta']) && is_numeric($_POST['gorjeta']) ? (float)$_POST['gorjeta'] : 0.00;
        $metodo_pagamento = $_POST['metodo_pagamento'];

        // Inicia uma TRANSAÇÃO: as duas queries devem ter sucesso, ou nenhuma será executada.
        $mysqli->begin_transaction();
        
        try {
            // 1. Atualiza o status do agendamento
            $sql_update = "UPDATE agendamentos SET status = 'Realizado' WHERE agendamento_id = ? AND barbeiro_id = ?";
            $stmt_update = $mysqli->prepare($sql_update);
            $stmt_update->bind_param("ii", $agendamento_id, $barbeiro_id_logado);
            $stmt_update->execute();

            if ($stmt_update->affected_rows === 0) {
                throw new Exception('Não foi possível atualizar o agendamento.');
            }
            $stmt_update->close();

            // 2. Insere o registro financeiro na tabela de atendimentos
            $sql_insert = "INSERT INTO atendimentos (servico_id, barbeiro_id, cliente_id, preco_cobrado, gorjeta, metodo_pagamento, registrado_em, agendamento_id_origem) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)";
            $stmt_insert = $mysqli->prepare($sql_insert);
            $stmt_insert->bind_param("iiidssi", $servico_id, $barbeiro_id_logado, $cliente_id, $preco_cobrado, $gorjeta, $metodo_pagamento, $agendamento_id);
            $stmt_insert->execute();

            if ($stmt_insert->affected_rows === 0) {
                throw new Exception('Não foi possível registrar o atendimento financeiro.');
            }
            $stmt_insert->close();
            
            // Se tudo deu certo, efetiva as alterações
            $mysqli->commit();
            $response = ['success' => true, 'message' => 'Atendimento finalizado e registrado com sucesso!'];

        } catch (Exception $e) {
            // Se algo deu errado, desfaz tudo
            $mysqli->rollback();
            $response['message'] = 'Erro na transação: ' . $e->getMessage();
        }
        break;
}

$mysqli->close();
echo json_encode($response);
?>