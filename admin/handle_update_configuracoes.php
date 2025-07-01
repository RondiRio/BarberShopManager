<?php
session_start();
require_once '../config/database.php'; // Conexão com o banco

// --- SEGURANÇA ---
if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_SESSION["loggedin"]) || $_SESSION["role"] !== 'admin') {
    header("location: ../login.php");
    exit;
}

// --- RECUPERAÇÃO E LIMPEZA DOS DADOS ---
// REMOVIDO: A variável $barbearia_id não é mais necessária.

$agendamento_ativo = isset($_POST['agendamento_ativo']) ? 1 : 0;
$permitir_agendamento_cliente = isset($_POST['permitir_agendamento_cliente']) ? 1 : 0;
$taxa_cancelamento_ativa = isset($_POST['taxa_cancelamento_ativa']) ? 1 : 0;
$valor_taxa_cancelamento = isset($_POST['valor_taxa_cancelamento']) ? str_replace(',', '.', $_POST['valor_taxa_cancelamento']) : '0.00';
$prazo_cancelamento = isset($_POST['prazo_cancelamento_sem_taxa_horas']) ? $_POST['prazo_cancelamento_sem_taxa_horas'] : 24;

// --- LÓGICA DO BANCO DE DADOS SIMPLIFICADA ---

// ALTERADO: A query agora é um simples UPDATE na linha onde o ID é sempre 1.
$sql = "UPDATE configuracoes SET 
            agendamento_ativo = ?, 
            permitir_agendamento_cliente = ?, 
            taxa_cancelamento_ativa = ?, 
            valor_taxa_cancelamento = ?, 
            prazo_cancelamento_sem_taxa_horas = ?
        WHERE config_id = 1";

if ($stmt = $mysqli->prepare($sql)) {
    // ALTERADO: O bind_param não precisa mais do primeiro 'i' para o barbeiro_id.
    $stmt->bind_param("iiidi", 
        $agendamento_ativo, 
        $permitir_agendamento_cliente, 
        $taxa_cancelamento_ativa, 
        $valor_taxa_cancelamento, 
        $prazo_cancelamento
    );

    if ($stmt->execute()) {
        $_SESSION['form_message'] = "Configurações atualizadas com sucesso!";
        $_SESSION['form_message_type'] = "success";
    } else {
        $_SESSION['form_message'] = "Erro ao atualizar as configurações.";
        $_SESSION['form_message_type'] = "error";
    }
    $stmt->close();
} else {
    $_SESSION['form_message'] = "Erro ao preparar a consulta ao banco de dados.";
    $_SESSION['form_message_type'] = "error";
}

$mysqli->close();

// --- REDIRECIONAMENTO ---
header("location: configuracoes.php");
exit();
?>