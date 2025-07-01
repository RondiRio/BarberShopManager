<?php
session_start();
require_once '../config/database.php';

// --- PASSO 1: SEGURANÇA E VERIFICAÇÃO INICIAL ---

// Garante que o usuário está logado e é um cliente
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'customer') {
    header("location: ../login.php");
    exit;
}

// Valida o ID do agendamento recebido pela URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['customer_message'] = "Erro: Agendamento inválido.";
    $_SESSION['customer_message_type'] = "error";
    header("location: dashboard.php");
    exit;
}
$agendamento_id = (int)$_GET['id'];
$cliente_id_logado = $_SESSION['user_id'];


// --- PASSO 2: BUSCAR DADOS PARA VALIDAÇÃO ---

// Busca o agendamento e as regras de cancelamento
$agendamento = null;
$configuracoes = null;

// Busca detalhes do agendamento
$sql_agendamento = "SELECT cliente_id, data_hora_agendamento, status FROM agendamentos WHERE agendamento_id = ?";
if ($stmt_agend = $mysqli->prepare($sql_agendamento)) {
    $stmt_agend->bind_param("i", $agendamento_id);
    $stmt_agend->execute();
    $result_agend = $stmt_agend->get_result();
    if ($result_agend->num_rows === 1) {
        $agendamento = $result_agend->fetch_assoc();
    }
    $stmt_agend->close();
}

// Busca as configurações da barbearia
$sql_configs = "SELECT taxa_cancelamento_ativa, prazo_cancelamento_sem_taxa_horas FROM configuracoes WHERE config_id = 1";
if ($result_configs = $mysqli->query($sql_configs)) {
    $configuracoes = $result_configs->fetch_assoc();
}


// --- PASSO 3: CADEIA DE VALIDAÇÕES CRÍTICAS ---

// Validação 1: O agendamento existe?
if ($agendamento === null || $configuracoes === null) {
    $_SESSION['customer_message'] = "Agendamento não encontrado ou erro nas configurações.";
    $_SESSION['customer_message_type'] = "error";
    header("location: dashboard.php");
    exit;
}

// Validação 2: O cliente logado é o dono do agendamento? (MUITO IMPORTANTE)
if ($agendamento['cliente_id'] !== $cliente_id_logado) {
    $_SESSION['customer_message'] = "Você não tem permissão para cancelar este agendamento.";
    $_SESSION['customer_message_type'] = "error";
    header("location: dashboard.php");
    exit;
}

// Validação 3: O agendamento já não está cancelado ou foi realizado?
if ($agendamento['status'] !== 'Confirmado') {
    $_SESSION['customer_message'] = "Este agendamento não pode mais ser cancelado.";
    $_SESSION['customer_message_type'] = "error";
    header("location: dashboard.php");
    exit;
}

// Validação 4: O agendamento já passou?
$agendamento_dt = new DateTime($agendamento['data_hora_agendamento']);
if ($agendamento_dt < new DateTime()) {
    $_SESSION['customer_message'] = "Não é possível cancelar um agendamento que já passou.";
    $_SESSION['customer_message_type'] = "error";
    header("location: dashboard.php");
    exit;
}

// Validação 5: Verificar a política de cancelamento (prazo)
if ($configuracoes['taxa_cancelamento_ativa']) {
    $prazo_horas = (int)$configuracoes['prazo_cancelamento_sem_taxa_horas'];
    $agora = new DateTime();
    $intervalo = $agora->diff($agendamento_dt);
    $horas_para_o_agendamento = ($intervalo->days * 24) + $intervalo->h;

    if ($horas_para_o_agendamento < $prazo_horas) {
        // Ação para cancelamento tardio: Informa o usuário e impede o cancelamento automático.
        // Numa versão futura, isso poderia gerar uma cobrança ou notificar o admin.
        $_SESSION['customer_message'] = "O cancelamento não é permitido com menos de {$prazo_horas} horas de antecedência. Por favor, entre em contato com a barbearia.";
        $_SESSION['customer_message_type'] = "error";
        header("location: dashboard.php");
        exit;
    }
}


// --- PASSO 4: ATUALIZAR O BANCO DE DADOS ---

// Se todas as validações passaram, atualiza o status para 'Cancelado'
$sql_update = "UPDATE agendamentos SET status = 'Cancelado' WHERE agendamento_id = ?";
if ($stmt_update = $mysqli->prepare($sql_update)) {
    $stmt_update->bind_param("i", $agendamento_id);
    
    if ($stmt_update->execute() && $stmt_update->affected_rows > 0) {
        $_SESSION['customer_message'] = "Agendamento cancelado com sucesso.";
        $_SESSION['customer_message_type'] = "success";
    } else {
        $_SESSION['customer_message'] = "Ocorreu um erro ao tentar cancelar o agendamento.";
        $_SESSION['customer_message_type'] = "error";
    }
    $stmt_update->close();
}

$mysqli->close();

// --- PASSO 5: REDIRECIONAR DE VOLTA PARA O PAINEL ---
header("location: dashboard.php");
exit();
?>