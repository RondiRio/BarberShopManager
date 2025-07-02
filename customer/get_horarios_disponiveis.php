<?php
header('Content-Type: application/json');
require_once '../config/database.php';

// Define a mesma constante de tempo fixo.
define('DURACAO_FIXA_MINUTOS', 60);

// --- VALIDAÇÃO DOS PARÂMETROS ---
if (!isset($_GET['barbeiro_id'], $_GET['data'])) {
    echo json_encode(['error' => 'Parâmetros inválidos.']);
    exit;
}

$barbeiro_id = (int)$_GET['barbeiro_id'];
$data = $_GET['data'];
$duracao_servico = DURACAO_FIXA_MINUTOS;

// --- LÓGICA DE CÁLCULO DE HORÁRIOS ---
$inicio_expediente = new DateTime($data . ' 08:00:00');
$fim_expediente = new DateTime($data . ' 20:00:00');
$intervalo_minutos = 60; // O sistema verifica horários a cada 15 minutos

// 1. ACESSA O BANCO DE DADOS para buscar agendamentos existentes
$agendamentos_existentes = [];
$sql = "SELECT data_hora_agendamento, duracao_minutos FROM agendamentos WHERE barbeiro_id = ? AND DATE(data_hora_agendamento) = ? AND status = 'Confirmado'";
if ($stmt = $mysqli->prepare($sql)) {
    $stmt->bind_param("is", $barbeiro_id, $data);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $agendamentos_existentes[] = [
            'inicio' => new DateTime($row['data_hora_agendamento']),
            'fim' => (new DateTime($row['data_hora_agendamento']))->modify("+{$row['duracao_minutos']} minutes")
        ];
    }
    $stmt->close();
}

$horarios_disponiveis = [];
$slot_atual = clone $inicio_expediente;

// 2. VERIFICA E VALIDA todos os possíveis horários
while ($slot_atual < $fim_expediente) {
    $inicio_slot_novo = clone $slot_atual;
    $fim_slot_novo = (clone $slot_atual)->modify("+" . $duracao_servico . " minutes");

    if ($fim_slot_novo > $fim_expediente) {
        break;
    }

    $conflito = false;
    foreach ($agendamentos_existentes as $agendamento) {
        if ($inicio_slot_novo < $agendamento['fim'] && $fim_slot_novo > $agendamento['inicio']) {
            $conflito = true;
            break;
        }
    }

    if (!$conflito) {
        $horarios_disponiveis[] = $inicio_slot_novo->format('H:i');
    }

    $slot_atual->modify("+{$intervalo_minutos} minutes");
}

// 3. DEVOLVE A RESPOSTA para o JavaScript
echo json_encode($horarios_disponiveis);
?>