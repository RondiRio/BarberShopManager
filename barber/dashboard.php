<?php
// Em produção, comente ou remova estas linhas.
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

session_start();
require_once '../config/database.php';

// Verifica se o usuário está logado e se tem a permissão de 'barber'
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'barber') {
    header("location: ../login.php");
    exit;
}

// Atribui variáveis de sessão com segurança
$barbeiro_id = $_SESSION["user_id"];
$barbearia_id = $_SESSION["barbearia_id"] ?? 0;
$taxa_comissao = isset($_SESSION["commission_rate"]) ? floatval($_SESSION["commission_rate"]) : 0.0;

// --- Bloco de cálculo de data ---
$hoje = new DateTime(); // Objeto DateTime com data e hora atuais
$inicio_semana_dt = (clone $hoje)->modify('last monday')->setTime(0, 0, 0);
if ($hoje->format('N') != 1) { // Se hoje não for segunda, ajusta para a segunda desta semana
    $inicio_semana_dt = (clone $hoje)->modify('-' . ($hoje->format('N') - 1) . ' days')->setTime(0, 0, 0);
}
$fim_semana_dt = (clone $hoje)->setTime(23, 59, 59);
$inicio_semana_sql = $inicio_semana_dt->format('Y-m-d H:i:s');
$fim_semana_sql = $fim_semana_dt->format('Y-m-d H:i:s');


// --- LÓGICA DE CONFIGURAÇÕES (NOVO: Busca ambas as flags) ---
$agendamento_habilitado = false;
$permitir_agendamento_cliente = false;
$sql_check_config = "SELECT agendamento_ativo, permitir_agendamento_cliente FROM configuracoes WHERE config_id = 1";
if ($result_config = $mysqli->query($sql_check_config)) {
    if ($config = $result_config->fetch_assoc()) {
        $agendamento_habilitado = (bool)$config['agendamento_ativo'];
        $permitir_agendamento_cliente = (bool)$config['permitir_agendamento_cliente'];
    }
    $result_config->free();
}
// NOVA CONDIÇÃO: Flag para controlar se as métricas de agendamento devem ser contadas
$contar_metricas_agendamento = ($agendamento_habilitado && $permitir_agendamento_cliente);


// --- LÓGICA PARA BUSCAR AGENDAMENTOS PENDENTES (VISUALIZAÇÃO DA AGENDA) ---
$agendamentos_da_semana = [];
if ($agendamento_habilitado) {
    $fim_agenda_dt = (clone $inicio_semana_dt)->modify('+6 days')->setTime(23, 59, 59);
    $fim_agenda_sql = $fim_agenda_dt->format('Y-m-d H:i:s');
    $sql_agenda = "SELECT a.agendamento_id, a.cliente_id, a.servico_id, a.preco_servico,
                          a.data_hora_agendamento, u.name AS cliente_nome, s.nome AS servico_nome
                   FROM agendamentos a
                   JOIN Users u ON a.cliente_id = u.user_id
                   JOIN servicos s ON a.servico_id = s.service_id
                   WHERE a.barbeiro_id = ? AND a.data_hora_agendamento BETWEEN ? AND ? AND a.status = 'Confirmado'
                   ORDER BY a.data_hora_agendamento ASC";
    if ($stmt_agenda = $mysqli->prepare($sql_agenda)) {
        $stmt_agenda->bind_param("iss", $barbeiro_id, $inicio_semana_sql, $fim_agenda_sql);
        $stmt_agenda->execute();
        $result_agenda = $stmt_agenda->get_result();
        while ($row = $result_agenda->fetch_assoc()) {
            $data = date('Y-m-d', strtotime($row['data_hora_agendamento']));
            $agendamentos_da_semana[$data][] = $row;
        }
        $stmt_agenda->close();
    }
}

// --- BUSCAR DADOS PARA ESTATÍSTICAS DA SEMANA (LÓGICA ATUALIZADA) ---

// Construção dinâmica da cláusula WHERE baseada nas configurações
// IMPORTANTE: Assumindo que a tabela `atendimentos` tem uma coluna `agendamento_id` que é NULL para atendimentos avulsos.
$where_condicao_metrica = "";
if (!$contar_metricas_agendamento) {
    // Se a condição não for atendida, conta APENAS atendimentos avulsos (onde agendamento_id é nulo)
    $where_condicao_metrica = " AND agendamento_id IS NULL";
}

// Cálculo de totais de serviços e gorjetas
$total_servicos_comissionaveis_semana = 0.00;
$total_gorjetas_semana = 0.00;
$sql_totais_servicos = "SELECT SUM(preco_cobrado) as total_servicos, SUM(gorjeta) as total_gorjetas
                        FROM atendimentos
                        WHERE barbeiro_id = ? AND registrado_em BETWEEN ? AND ? $where_condicao_metrica";

if ($stmt_totais = $mysqli->prepare($sql_totais_servicos)) {
    $stmt_totais->bind_param("iss", $barbeiro_id, $inicio_semana_sql, $fim_semana_sql);
    $stmt_totais->execute();
    $result_totais = $stmt_totais->get_result();
    if ($row = $result_totais->fetch_assoc()) {
        $total_servicos_comissionaveis_semana = $row['total_servicos'] ?? 0.00;
        $total_gorjetas_semana = $row['total_gorjetas'] ?? 0.00;
    }
    $stmt_totais->close();
}

// Contagem de clientes únicos com a mesma condição
$sql_clientes_unicos = "SELECT COUNT(DISTINCT COALESCE(CONCAT('user_', cliente_id), CONCAT('walkin_', LOWER(TRIM(cliente_nome))))) as total
                        FROM atendimentos
                        WHERE barbeiro_id = ? AND registrado_em BETWEEN ? AND ?
                        AND (cliente_id IS NOT NULL OR (cliente_nome IS NOT NULL AND cliente_nome != '')) $where_condicao_metrica";

if ($stmt_clientes = $mysqli->prepare($sql_clientes_unicos)) {
    $stmt_clientes->bind_param("iss", $barbeiro_id, $inicio_semana_sql, $fim_semana_sql);
    $stmt_clientes->execute();
    $stmt_clientes->bind_result($quantidade_clientes_semana);
    $stmt_clientes->fetch();
    $stmt_clientes->close();
    $quantidade_clientes_semana = $quantidade_clientes_semana ?? 0;
} else {
    $quantidade_clientes_semana = 0;
}

// Demais métricas (Vendas de produtos e Vales não são afetadas pela configuração de agendamento)
$total_vendas_produtos_semana = 0.00;
$sql_vendas = "SELECT SUM(valor_total_venda) as total_produtos FROM vendas_Produtos WHERE barbeiro_id = ? AND vendido_em BETWEEN ? AND ?";
if ($stmt_vendas = $mysqli->prepare($sql_vendas)) {
    $stmt_vendas->bind_param("iss", $barbeiro_id, $inicio_semana_sql, $fim_semana_sql);
    $stmt_vendas->execute();
    if ($venda = $stmt_vendas->get_result()->fetch_assoc()) {
        $total_vendas_produtos_semana = $venda['total_produtos'] ?? 0.00;
    }
    $stmt_vendas->close();
}

$total_vales_semana = 0.00;
$sql_vales = "SELECT SUM(valor) as total_vales FROM vales_Barbeiro WHERE barbeiro_id = ? AND registrado_em BETWEEN ? AND ?";
if ($stmt_vales = $mysqli->prepare($sql_vales)) {
    $stmt_vales->bind_param("iss", $barbeiro_id, $inicio_semana_sql, $fim_semana_sql);
    $stmt_vales->execute();
    if ($vale = $stmt_vales->get_result()->fetch_assoc()) {
        $total_vales_semana = $vale['total_vales'] ?? 0.00;
    }
    $stmt_vales->close();
}

// Buscar os nomes dos clientes 
$lista_clientes_semana = [];
$sql_lista_clientes = "SELECT DISTINCT cliente_nome FROM atendimentos WHERE barbeiro_id = ? AND registrado_em BETWEEN ? AND ? AND cliente_nome IS NOT NULL AND cliente_nome != '' ORDER BY cliente_nome ASC";
if ($stmt_lista_cli = $mysqli->prepare($sql_lista_clientes)) {
    $stmt_lista_cli->bind_param("iss", $barbeiro_id, $inicio_semana_sql, $fim_semana_sql);
    $stmt_lista_cli->execute();
    $result_lista_cli = $stmt_lista_cli->get_result();
    while ($cliente = $result_lista_cli->fetch_assoc()) {
        $lista_clientes_semana[] = $cliente['cliente_nome'];
    }
    $stmt_lista_cli->close();
}

// Cálculos Financeiros Finais
$comissao_calculada_semana = $total_servicos_comissionaveis_semana * $taxa_comissao;
$total_a_receber_semana = ($comissao_calculada_semana + $total_gorjetas_semana) - $total_vales_semana;

// --- BUSCAR LISTAS PARA FORMULÁRIOS ---
// (Nenhuma alteração necessária aqui)
$servicos_comissionaveis = [];
$sql_lista_servicos = "SELECT service_id, nome, preco FROM servicos WHERE ativo = 1 ORDER BY nome ASC";
if ($result = $mysqli->query($sql_lista_servicos)) { $servicos_comissionaveis = $result->fetch_all(MYSQLI_ASSOC); $result->free(); }

$produtos_lista = [];
$sql_lista_produtos = "SELECT produto_id, nome, preco FROM produtos WHERE ativo = 1 ORDER BY nome ASC";
if ($result = $mysqli->query($sql_lista_produtos)) { $produtos_lista = $result->fetch_all(MYSQLI_ASSOC); $result->free(); }

$fotos_mural = [];
$sql_fotos = "SELECT foto_id, caminho_imagem, legenda, DATE_FORMAT(data_upload, '%d/%m/%Y %H:%i') as data_formatada FROM fotos_barbeiro WHERE barbeiro_id = ? ORDER BY data_upload DESC";
if ($stmt_fotos = $mysqli->prepare($sql_fotos)) { $stmt_fotos->bind_param("i", $barbeiro_id); $stmt_fotos->execute(); $fotos_mural = $stmt_fotos->get_result()->fetch_all(MYSQLI_ASSOC); $stmt_fotos->close(); }

$mysqli->close();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Barbeiro - Barbearia JB</title>
    <style>
        /* (CSS permanece o mesmo, sem alterações) */
        body { font-family: Arial, sans-serif; background-color: #121212; color: #FFF; padding: 20px; margin:0; }
        .main-container { max-width: 1200px; margin: auto; background-color: #1E1E1E; padding: 20px; border-radius: 8px; }
        .admin-header { display: flex; justify-content: space-between; align-items: center; background-color: #000; color: #FFF; padding: 15px 20px; margin-bottom:20px; border-bottom:3px solid #f39c12;}
        .admin-header h1 {margin:0; font-size: 1.8em;}
        .admin-header a {color: #FFF; text-decoration:none; }
        h1, h2, h3 { color: #FFF; }
        a { color: #f39c12; }
        a:hover { color: #FFF; }
        .dashboard-stats { display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 30px;}
        .stat-card { background-color: #282828; padding: 20px; border-radius: 8px; flex-grow: 1; flex-basis: 200px; text-align: center; border-left: 5px solid #f39c12;}
        .stat-card h3 { margin-top: 0; font-size: 1.1em; color: #BBB; text-transform: uppercase;}
        .stat-card p { font-size: 1.8em; font-weight: bold; color: #FFF; margin: 5px 0 0 0;}
        .stat-card.positive { border-left-color: #27ae60; }
        .stat-card.negative { border-left-color: #c0392b; }
        .forms-section { display: flex; flex-wrap: wrap; gap: 30px; margin-top: 30px;}
        .form-container { background-color: #282828; padding: 20px; border-radius: 8px; flex: 1; min-width: 300px; }
        .form-container h3 { margin-top: 0; color: #f39c12; border-bottom: 1px solid #444; padding-bottom: 10px;}
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; font-size:0.9em; color:#DDD; }
        .form-group input, .form-group select, .form-group textarea { width: calc(100% - 22px); padding: 10px; background-color: #333; border: 1px solid #555; color: #FFF; border-radius: 4px; font-size: 1em; }
        .form-group input[type="number"] { appearance: textfield; -moz-appearance: textfield; }
        .form-group button { background-color: #f39c12; color: #000; border: none; padding: 10px 15px; border-radius: 4px; cursor: pointer; font-weight: bold; text-transform: uppercase; width:100%; }
        .form-group button:hover { background-color: #e67e22; }
        .message { padding: 10px; margin-bottom: 15px; border-radius:4px; text-align:center; color:white; }
        .message.success { background-color: #27ae60; }
        .message.error { background-color: #c0392b; }
        .photo-mural { margin-top: 30px; border-top:1px solid #444; padding-top:20px;}
        .photo-mural img { width: 150px; height: 150px; object-fit: cover; margin: 5px; border: 2px solid #444; border-radius: 4px;}
        .agenda-container { background-color: #282828; padding: 20px; border-radius: 8px; margin-top: 30px; }
        .agenda-container h2 { margin-top: 0; color: #f39c12; border-bottom: 1px solid #444; padding-bottom: 10px; }
        .dia-agenda h3 { font-size: 1.2em; color: #DDD; margin: 20px 0 10px 0; border-bottom: 1px solid #333; padding-bottom: 5px;}
        .agenda-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .agenda-table th, .agenda-table td { padding: 12px; text-align: left; border-bottom: 1px solid #444; }
        .agenda-table th { background-color: #333; color: #f39c12; text-transform: uppercase; font-size: 0.9em; }
        .agenda-table td { color: #ddd; }
        .agenda-table tr:last-child td { border-bottom: none; }
        .no-appointments { text-align: center; padding: 20px; color: #888; }
        .select-status-agendamento { background-color: #555; color: #fff; border: 1px solid #666; padding: 8px; border-radius: 4px; cursor: pointer; }
        .select-status-agendamento:disabled { background-color: #222; color: #666; cursor: not-allowed; }
        .row-realizado { background-color: rgba(39, 174, 96, 0.2); opacity: 0.7; }
        .row-nao-compareceu { background-color: rgba(192, 57, 43, 0.2); opacity: 0.7; text-decoration: line-through; }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.8); align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { position: relative; background-color: #1E1E1E; padding: 30px; border-radius: 8px; width: 90%; max-width: 450px; border-top: 4px solid #f39c12; }
        .modal-content h3 { color: #f39c12; margin-top:0; }
        .modal-close { position: absolute; top: 10px; right: 15px; color: #fff; font-size: 28px; font-weight: bold; cursor: pointer; }
        .modal-close:hover { color: #f39c12; }
        .modal-actions { display:flex; gap: 10px; margin-top:20px;}
        .modal-actions .btn-cancel { background-color: #555; color: #fff; }
        .modal-actions .btn-cancel:hover { background-color: #777; }
         .client-list-container {
            background-color: #282828;
            padding: 20px;
            border-radius: 8px;
            margin-top: 30px;
        }
        .client-list-container h3 {
            margin-top: 0;
            color: #f39c12;
            border-bottom: 1px solid #444;
            padding-bottom: 10px;
        }
        .client-list {
            list-style-type: none;
            padding: 0;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
        }
        .client-list li {
            background-color: #333;
            padding: 10px;
            border-radius: 4px;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <header class="admin-header">
        <h1>Barbearia JB</h1>
        <a href="../logout.php">Sair (<?php echo htmlspecialchars($_SESSION["name"]); ?>)</a>
    </header>

    <div class="main-container">
        <h1>Painel do Barbeiro</h1>

        <?php if (isset($_SESSION['form_message'])) { echo '<div class="message ' . htmlspecialchars($_SESSION['form_message_type']) . '">' . htmlspecialchars($_SESSION['form_message']) . '</div>'; unset($_SESSION['form_message'], $_SESSION['form_message_type']); } ?>

        <h2>Suas Métricas da Semana (<?php echo $inicio_semana_dt->format('d/m') . ' a ' . $hoje->format('d/m'); ?>)</h2>
        <div class="dashboard-stats">
            <div class="stat-card"><h3>Serviços (R$)</h3><p>R$ <?php echo number_format($total_servicos_comissionaveis_semana, 2, ',', '.'); ?></p></div>
            <div class="stat-card"><h3>Gorjetas (R$)</h3><p>R$ <?php echo number_format($total_gorjetas_semana, 2, ',', '.'); ?></p></div>
            <div class="stat-card"><h3>Produtos (R$)</h3><p>R$ <?php echo number_format($total_vendas_produtos_semana, 2, ',', '.'); ?></p></div>
            <div class="stat-card"><h3>Sua Comissão (R$)</h3><p>R$ <?php echo number_format($comissao_calculada_semana, 2, ',', '.'); ?></p></div>
            <div class="stat-card negative"><h3>Vales (R$)</h3><p>R$ <?php echo number_format($total_vales_semana, 2, ',', '.'); ?></p></div>
            <div class="stat-card"><h3>Clientes Atendidos</h3><p><?php echo $quantidade_clientes_semana; ?></p></div>
            <div class="stat-card <?php echo $total_a_receber_semana >= 0 ? 'positive' : 'negative'; ?>"><h3>Total a Receber (R$)</h3><p>R$ <?php echo number_format($total_a_receber_semana, 2, ',', '.'); ?></p></div>
        </div>
        <div class="client-list-container">
            <h3><span style="font-size: 1.2em;">👥</span> Clientes Atendidos na Semana</h3>
            <?php if (!empty($lista_clientes_semana)): ?>
                <ul class="client-list">
                    <?php foreach ($lista_clientes_semana as $nome_cliente): ?>
                        <li><?php echo htmlspecialchars($nome_cliente); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>Nenhum cliente com nome registrado foi atendido esta semana.</p>
            <?php endif; ?>
        </div>
        <br>
        <?php if ($agendamento_habilitado): ?>
        <div class="agenda-container">
            <h2>📅 Sua Agenda da Semana</h2>
            <?php
            $dias_da_semana_map = ['Domingo', 'Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sábado'];
            $dia_corrente_dt = clone $inicio_semana_dt;
            for ($i = 0; $i < 7; $i++):
                $chave_data = $dia_corrente_dt->format('Y-m-d');
                $nome_dia = $dias_da_semana_map[$dia_corrente_dt->format('w')];
            ?>
            <div class="dia-agenda">
                <h3><?php echo $nome_dia . " (" . $dia_corrente_dt->format('d/m') . ")"; ?></h3>
                <?php if (!empty($agendamentos_da_semana[$chave_data])): ?>
                    <table class="agenda-table">
                        <thead><tr><th>Horário</th><th>Cliente</th><th>Serviço</th><th>Status / Ação</th></tr></thead>
                        <tbody>
                            <?php foreach ($agendamentos_da_semana[$chave_data] as $agendamento): ?>
                                <?php
                                // NOVA LÓGICA DE RESTRIÇÃO DE TEMPO
                                $pode_alterar = false;
                                $data_agendamento_dt = new DateTime($agendamento['data_hora_agendamento']);
                                // Permite alteração apenas no mesmo dia e após o horário agendado.
                                if ($hoje->format('Y-m-d') === $data_agendamento_dt->format('Y-m-d') && $hoje >= $data_agendamento_dt) {
                                    $pode_alterar = true;
                                }
                                ?>
                                <tr id="agenda-row-<?php echo $agendamento['agendamento_id']; ?>">
                                    <td><?php echo $data_agendamento_dt->format('H:i'); ?></td>
                                    <td><?php echo htmlspecialchars($agendamento['cliente_nome']); ?></td>
                                    <td><?php echo htmlspecialchars($agendamento['servico_nome']); ?></td>
                                    <td>
                                        <select class="select-status-agendamento"
                                                data-agendamento-id="<?php echo $agendamento['agendamento_id']; ?>"
                                                data-cliente-id="<?php echo $agendamento['cliente_id']; ?>"
                                                data-servico-id="<?php echo $agendamento['servico_id']; ?>"
                                                data-preco-servico="<?php echo $agendamento['preco_servico']; ?>"
                                                <?php if (!$pode_alterar) echo 'disabled'; // Desabilita o select se estiver fora do tempo ?> >
                                            <option value="">Aguardando</option>
                                            <option value="realizado">Compareceu</option>
                                            <option value="nao_compareceu">Não Compareceu</option>
                                        </select>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="no-appointments">Nenhum agendamento para este dia.</p>
                <?php endif; ?>
            </div>
            <?php
                $dia_corrente_dt->modify('+1 day');
            endfor;
            ?>
        </div>
        <?php endif; ?>

        <div id="modal-confirmar-atendimento" class="modal">
            <div class="modal-content">
                <span class="modal-close">&times;</span>
                <h3>Finalizar Atendimento</h3>
                <p>Confirme os detalhes do atendimento realizado.</p>
                <form id="form-confirmar-atendimento">
                    <input type="hidden" name="action" value="realizado">
                    <input type="hidden" id="confirmar-agendamento-id" name="agendamento_id">
                    <input type="hidden" id="confirmar-cliente-id" name="cliente_id">
                    <input type="hidden" id="confirmar-servico-id" name="servico_id">
                    <input type="hidden" id="confirmar-preco-servico" name="preco_cobrado">
                    <div class="form-group">
                        <label for="modal-gorjeta">Gorjeta (R$) (Opcional)</label>
                        <input type="number" id="modal-gorjeta" name="gorjeta" step="0.01" min="0" placeholder="0,00">
                    </div>
                    <div class="form-group">
                        <label for="modal-metodo-pagamento">Método de Pagamento</label>
                        <select id="modal-metodo-pagamento" name="metodo_pagamento" required>
                            <option value="dinheiro">Dinheiro</option><option value="pix">PIX</option><option value="cartao_debito">Cartão de Débito</option><option value="cartao_credito">Cartão de Crédito</option>
                        </select>
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="btn-cancel">Cancelar</button>
                        <button type="submit">Confirmar Atendimento</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="forms-section">
            <div class="form-container">
                <h3>✂️ Registrar Atendimento Avulso</h3>
                <form action="handle_add_atendimento.php" method="post">
                    <div class="form-group"><label for="servico_id">Serviço:</label><select name="servico_id" id="servico_id" required><option value="">Selecione o serviço</option><?php foreach ($servicos_comissionaveis as $serv): ?><option value="<?php echo $serv['service_id']; ?>" data-preco="<?php echo $serv['preco']; ?>"><?php echo htmlspecialchars($serv['nome']) . " (R$ " . number_format($serv['preco'], 2, ',', '.') . ")"; ?></option><?php endforeach; ?></select></div>
                    <div class="form-group"><label for="cliente_nome">Nome do Cliente (Opcional):</label><input type="text" name="cliente_nome" id="cliente_nome" placeholder="Ex: João Silva"></div>
                    <div class="form-group"><label for="gorjeta">Gorjeta (R$):</label><input type="number" name="gorjeta" id="gorjeta" step="0.01" min="0" placeholder="0,00"></div>
                    <div class="form-group"><label for="metodo_pagamento_servico">Método de Pagamento:</label><select name="metodo_pagamento" id="metodo_pagamento_servico" required><option value="dinheiro">Dinheiro</option><option value="cartao_debito">Cartão de Débito</option><option value="cartao_credito">Cartão de Crédito</option><option value="pix">PIX</option></select></div>
                    <div class="form-group"><button type="submit">Registrar Atendimento</button></div>
                </form>
            </div>
            <div class="form-container">
                <h3>🍺 Registrar Venda de Produto</h3>
                <form action="handle_add_venda_produto.php" method="post">
                    <div class="form-group"><label for="produto_id">Produto:</label><select name="produto_id" id="produto_id" required><option value="">Selecione o produto</option><?php foreach ($produtos_lista as $prod): ?><option value="<?php echo $prod['produto_id']; ?>" data-preco="<?php echo $prod['preco']; ?>"><?php echo htmlspecialchars($prod['nome']) . " (R$ " . number_format($prod['preco'], 2, ',', '.') . ")"; ?></option><?php endforeach; ?></select></div>
                    <div class="form-group"><label for="quantidade">Quantidade:</label><input type="number" name="quantidade" id="quantidade" min="1" value="1" required></div>
                    <div class="form-group"><label for="metodo_pagamento_produto">Método de Pagamento:</label><select name="metodo_pagamento" id="metodo_pagamento_produto" required><option value="dinheiro">Dinheiro</option><option value="cartao_debito">Cartão de Débito</option><option value="cartao_credito">Cartão de Crédito</option><option value="pix">PIX</option></select></div>
                    <div class="form-group"><button type="submit">Registrar Venda</button></div>
                </form>
            </div>
            <div class="form-container">
                <h3>💰 Registrar Vale (Adiantamento)</h3>
                <form action="handle_add_vale.php" method="post">
                    <div class="form-group"><label for="valor_vale">Valor do Vale (R$):</label><input type="number" name="valor_vale" id="valor_vale" step="0.01" min="0.01" required placeholder="Ex: 50,00"></div>
                    <div class="form-group"><label for="descricao_vale">Descrição (Opcional):</label><textarea name="descricao_vale" id="descricao_vale" rows="2" placeholder="Ex: Adiantamento para almoço"></textarea></div>
                    <div class="form-group"><button type="submit">Registrar Vale</button></div>
                </form>
            </div>
        </div>
        <div class="photo-mural">
            <h3>📸 Seu Mural de Fotos</h3>
            <form action="handle_upload_foto.php" method="post" enctype="multipart/form-data" style="margin-bottom:25px; background-color: #282828; padding:20px; border-radius:8px;">
                <div class="form-group"><label for="fotoCliente">Adicionar foto ao mural (JPG, PNG, GIF - Máx 5MB):</label><input type="file" name="fotoCliente" id="fotoCliente" accept="image/jpeg,image/png,image/gif" required style="padding:10px; background-color: #333; border: 1px solid #555;"></div>
                <div class="form-group"><label for="caption">Legenda (Opcional):</label><input type="text" name="caption" id="caption" placeholder="Descreva o corte ou o cliente"></div>
                <button type="submit">Enviar Foto</button>
            </form>
            <div class="mural-gallery" style="display: flex; flex-wrap: wrap; gap: 15px; justify-content: center;">
                <?php if (!empty($fotos_mural)): ?>
                    <?php foreach ($fotos_mural as $foto): ?>
                        <div class="foto-item" style="background-color:#333; padding:10px; border-radius:5px; text-align:center; width: 200px;">
                            <img src="../<?php echo htmlspecialchars($foto['caminho_imagem']); ?>" alt="<?php echo htmlspecialchars($foto['legenda'] ?? 'Foto do Mural'); ?>" style="width: 100%; height: 180px; object-fit: cover; border-radius: 3px; margin-bottom: 8px;">
                            <?php if (!empty($foto['legenda'])): ?><p style="font-size: 0.85em; color: #ccc; margin-bottom: 5px;"><?php echo htmlspecialchars($foto['legenda']); ?></p><?php endif; ?>
                            <p style="font-size: 0.75em; color: #888;">Enviada em: <?php echo $foto['data_formatada']; ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>Você ainda não adicionou nenhuma foto ao seu mural.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
<script>
    // JAVASCRIPT permanece o mesmo, sem alterações necessárias.
    // A lógica de desabilitar o <select> no PHP já previne as ações indesejadas.
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('modal-confirmar-atendimento');
        const formConfirmar = document.getElementById('form-confirmar-atendimento');
        const btnCloseModal = modal.querySelector('.modal-close');
        const btnCancelModal = modal.querySelector('.btn-cancel');
        let activeSelect = null;
        function closeModal() { modal.classList.remove('active'); if (activeSelect) { activeSelect.value = ""; activeSelect = null; } formConfirmar.reset(); }
        btnCloseModal.addEventListener('click', closeModal);
        btnCancelModal.addEventListener('click', closeModal);
        modal.addEventListener('click', function(e) { if (e.target === modal) { closeModal(); } });
        document.body.addEventListener('change', async function(e) {
            if (e.target.matches('.select-status-agendamento')) {
                const select = e.target;
                const action = select.value;
                const agendamentoId = select.dataset.agendamentoId;
                const row = document.getElementById(`agenda-row-${agendamentoId}`);
                if (action === 'realizado') {
                    activeSelect = select;
                    document.getElementById('confirmar-agendamento-id').value = agendamentoId;
                    document.getElementById('confirmar-cliente-id').value = select.dataset.clienteId;
                    document.getElementById('confirmar-servico-id').value = select.dataset.servicoId;
                    document.getElementById('confirmar-preco-servico').value = select.dataset.precoServico;
                    modal.classList.add('active');
                } else if (action === 'nao_compareceu') {
                    if (confirm('Tem certeza que deseja marcar este cliente como NÃO COMPARECEU?')) {
                        const formData = new FormData();
                        formData.append('action', 'nao_compareceu');
                        formData.append('agendamento_id', agendamentoId);
                        try {
                            const response = await fetch('handle_agenda_action.php', { method: 'POST', body: formData });
                            const result = await response.json();
                            if (result.success) { row.classList.add('row-nao-compareceu'); select.disabled = true; }
                            alert(result.message);
                        } catch (error) { alert('Ocorreu um erro de comunicação com o servidor.'); select.value = ""; }
                    } else { select.value = ""; }
                }
            }
        });
        // Submissão do formulário do modal
    formConfirmar.addEventListener('submit', async function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        // Desabilitar o botão para evitar cliques duplos
        formConfirmar.querySelector('button[type="submit"]').disabled = true;

        try {
            const response = await fetch('handle_agenda_action.php', { method: 'POST', body: formData });
            const result = await response.json();

            if (result.success) {
                // ATUALIZADO: Mostra a mensagem de sucesso e recarrega a página.
                alert(result.message);
                location.reload(); // Esta linha força o recarregamento da página
            } else {
                alert('Erro: ' + result.message);
                // Habilita o botão novamente se houver erro
                formConfirmar.querySelector('button[type="submit"]').disabled = false;
            }
        } catch (error) {
            alert('Ocorreu um erro de comunicação com o servidor.');
            formConfirmar.querySelector('button[type="submit"]').disabled = false;
        }
    });
        const messageDiv = document.querySelector('.message');
        if(messageDiv) { setTimeout(() => { messageDiv.style.display = 'none'; }, 5000); }
    });
</script>
</body>
</html>