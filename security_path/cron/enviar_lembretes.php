<?php
require_once __DIR__ . '../../config/database.php'; // Conexão com o banco de dados
require_once __DIR__ . '../../customer/includes/email_sender.php'; // Inclui o arquivo de envio de e-mails

$sql_24h = "SELECT a.agendamento_id, a.data_hora_agendamento, u.name as barbeiro_nome, s.nome as servico_nome, c.email as cliente_email, c.name as cliente_nome FROM agendamentos a JOIN users u ON a.barbeiro_id = u.user_id JOIN servicos s ON a.servico_id = s.service_id JOIN users c ON a.cliente_id = c.user_id WHERE a.status = 'Confirmado' AND a.lembrete_24h_enviado = 0 AND a.data_hora_agendamento BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 25 HOUR)";
$result_24h = $mysqli->query($sql_24h);

while ($agendamento = $result_24h->fetch_assoc()) {
    $agendamento_dt = new DateTime($agendamento['data_hora_agendamento']);
    $email_details = [
        "Barbeiro" => $agendamento['barbeiro_nome'],
        "Serviço" => $agendamento['servico_nome'],
        "Data" => $agendamento_dt->format('d/m/Y'),
        "Horário" => $agendamento_dt->format('H:i')
    ];
    $html_body = generateEmailHTML("Lembrete de Agendamento", "Olá, " . $agendamento['cliente_nome'] . "! Passando para lembrar do seu horário conosco amanhã. Mal podemos esperar!", $email_details);
    
    if (sendAppointmentEmail($agendamento['cliente_email'], $agendamento['cliente_nome'], "⏰ Lembrete: Seu horário na Barbearia JB é amanhã!", $html_body)) {
        // Marca como enviado para não enviar de novo
        $mysqli->query("UPDATE agendamentos SET lembrete_24h_enviado = 1 WHERE agendamento_id = " . $agendamento['agendamento_id']);
    }
}

$sql_2h = "SELECT ... WHERE a.status = 'Confirmado' AND a.lembrete_2h_enviado = 0 AND a.data_hora_agendamento BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 3 HOUR)";

echo "Processo de lembretes finalizado em " . date('Y-m-d H:i:s');