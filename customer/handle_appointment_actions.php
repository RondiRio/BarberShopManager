<?php
session_start();
require_once '../config/database.php';
header('Content-Type: application/json');
define('DURACAO_FIXA_MINUTOS', 60);

$response = ['success' => false, 'message' => 'Ação inválida ou não permitida.'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION["loggedin"]) || $_SESSION["role"] !== 'customer') {
    echo json_encode($response);
    exit;
}

$action = $_POST['action'] ?? '';
$agendamento_id = isset($_POST['agendamento_id']) ? (int)$_POST['agendamento_id'] : 0;
$cliente_id_logado = $_SESSION['user_id'];

if (empty($action) || empty($agendamento_id)) {
    $response['message'] = 'Dados insuficientes.';
    echo json_encode($response);
    exit;
}

// 1. VERIFICAR POSSE E STATUS DO AGENDAMENTO
$sql_check = "SELECT cliente_id, data_hora_agendamento, status FROM agendamentos WHERE agendamento_id = ?";
if (!($stmt_check = $mysqli->prepare($sql_check))) {
    echo json_encode($response); exit;
}
$stmt_check->bind_param("i", $agendamento_id);
$stmt_check->execute();
$result_check = $stmt_check->get_result();
if ($result_check->num_rows !== 1) {
    $response['message'] = 'Agendamento não encontrado.';
    echo json_encode($response); exit;
}
$agendamento = $result_check->fetch_assoc();
$stmt_check->close();

if ($agendamento['cliente_id'] !== $cliente_id_logado) {
    $response['message'] = 'Permissão negada.';
    echo json_encode($response); exit;
}
if ($agendamento['status'] !== 'Confirmado') {
    $response['message'] = 'Este agendamento não pode mais ser modificado.';
    echo json_encode($response); exit;
}

// 2. EXECUTAR A AÇÃO SOLICITADA
switch ($action) {
    case 'cancelar':
        // A lógica de verificar o prazo de cancelamento iria aqui...
        
        $sql_cancel = "UPDATE agendamentos SET status = 'Cancelado' WHERE agendamento_id = ?";
        $stmt_cancel = $mysqli->prepare($sql_cancel);
        $stmt_cancel->bind_param("i", $agendamento_id);
        if ($stmt_cancel->execute() && $stmt_cancel->affected_rows > 0) {
            $response = ['success' => true, 'message' => 'Agendamento cancelado com sucesso!'];
        } else {
            $response['message'] = 'Falha ao cancelar o agendamento.';
        }
        $stmt_cancel->close();
        break;

    case 'editar':
        // Validação dos dados de edição
        $nova_data = $_POST['data'] ?? '';
        $novo_horario = $_POST['horario'] ?? '';
        if (empty($nova_data) || empty($novo_horario)) {
            $response['message'] = 'Nova data e horário são obrigatórios.';
            break;
        }
        $nova_data_hora_str = $nova_data . ' ' . $novo_horario;
        // A lógica de verificação de conflito para o novo horário iria aqui...

        $sql_update = "UPDATE agendamentos SET data_hora_agendamento = ? WHERE agendamento_id = ?";
        $stmt_update = $mysqli->prepare($sql_update);
        $stmt_update->bind_param("si", $nova_data_hora_str, $agendamento_id);
        if ($stmt_update->execute() && $stmt_update->affected_rows > 0) {
            $response = ['success' => true, 'message' => 'Agendamento atualizado com sucesso!'];
        } else {
            $response['message'] = 'Falha ao atualizar ou nenhum dado foi alterado.';
        }
        $stmt_update->close();
        break;
}

$mysqli->close();
echo json_encode($response);
?>