<?php
session_start();
require_once '../config/database.php'; // Ajuste o caminho se necessário

// Verifica se o usuário está logado e se é admin
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'admin') {
    header("location: ../login.php");
    exit;
}

$current_page = basename($_SERVER['PHP_SELF']);

// Buscar lista de barbeiros para o dropdown (incluindo a taxa de comissão para evitar query no loop)
$barbeiros_lista = [];
// Assumindo que sua tabela Users tem colunas user_id, name (ou nome), commission_rate (ou taxa_comissao)
$sql_barbeiros = "SELECT user_id, name, commission_rate FROM users WHERE role = 'barber' AND is_active = 1 ORDER BY name ASC";
if ($result_barbs = $mysqli->query($sql_barbeiros)) {
    while ($barb = $result_barbs->fetch_assoc()) {
        $barbeiros_lista[] = $barb;
    }
    $result_barbs->free();
}

$selected_barbeiro_id = null;
$selected_week_start_input = null; // Para manter o valor do input date
$selected_week_start_display = null; // Para exibir na tela YYYY-MM-DD
$selected_week_end_display = null; // Para exibir na tela YYYY-MM-DD
$report_data = null;
$barbeiro_nome_report = "";

if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['week_start']) && isset($_GET['barbeiro_id'])) {
    $selected_barbeiro_id = $_GET['barbeiro_id'];
    $selected_week_start_input = $_GET['week_start'];

    try {
        $start_date_obj = new DateTime($selected_week_start_input);
        // Forçar para a segunda-feira daquela semana
        if ($start_date_obj->format('N') != 1) {
            $start_date_obj->modify('last monday');
        }
        $selected_week_start_display = $start_date_obj->format('Y-m-d');

        $end_date_obj = clone $start_date_obj;
        $end_date_obj->modify('+5 days'); // Segunda + 5 dias = Sábado
        $selected_week_end_display = $end_date_obj->format('Y-m-d');

        // Período para SQL: Segunda 00:00:00 até Sábado 23:59:59
        $inicio_semana_sql = $start_date_obj->format('Y-m-d 00:00:00');
        $fim_semana_sql = (clone $end_date_obj)->setTime(23,59,59)->format('Y-m-d H:i:s');

        $report_data = [];
        $barbeiros_a_processar = [];

        if ($selected_barbeiro_id == 'all') {
            $barbeiros_a_processar = $barbeiros_lista;
        } else {
            foreach ($barbeiros_lista as $b) {
                if ($b['user_id'] == $selected_barbeiro_id) {
                    $barbeiros_a_processar[] = $b; // Adiciona o array completo do barbeiro
                    $barbeiro_nome_report = $b['name']; // ou $b['nome'] se sua coluna for 'nome'
                    break;
                }
            }
        }

        foreach ($barbeiros_a_processar as $barbeiro_info) {
            $barbeiro_id_process = $barbeiro_info['user_id'];
            // Assumindo que 'commission_rate' é o nome da coluna na tabela Users
            $taxa_comissao_barbeiro = isset($barbeiro_info['commission_rate']) ? (float)$barbeiro_info['commission_rate'] : 0.0;

            $current_barber_report = [
                'nome' => $barbeiro_info['name'], // ou $barbeiro_info['nome']
                'user_id' => $barbeiro_id_process,
                'total_servicos_valor' => 0.00,
                'total_gorjetas' => 0.00,
                'total_produtos_valor' => 0.00,
                'total_vales_valor' => 0.00,
                'taxa_comissao' => $taxa_comissao_barbeiro,
                'comissao_calculada' => 0.00,
                'total_a_pagar' => 0.00,
                'contagem_cabelo' => 0,
                'contagem_barba' => 0,
                'contagem_sobrancelha' => 0,
                'contagem_pezinho' => 0,
                'servicos_detalhados' => []
            ];

            // 1. Valor Total de Serviços e Gorjetas (Tabela Atendimentos)
            // Assumindo nomes de colunas: preco_cobrado, gorjeta, barbeiro_id, registrado_em
            $sql_serv_totals = "SELECT SUM(preco_cobrado) as total_s, SUM(gorjeta) as total_g 
                                FROM atendimentos 
                                WHERE barbeiro_id = ? AND registrado_em BETWEEN ? AND ?";
            if ($stmt_s_total = $mysqli->prepare($sql_serv_totals)) {
                $stmt_s_total->bind_param("iss", $barbeiro_id_process, $inicio_semana_sql, $fim_semana_sql);
                $stmt_s_total->execute();
                $res_s_total = $stmt_s_total->get_result();
                if ($data_s_total = $res_s_total->fetch_assoc()) {
                    $current_barber_report['total_servicos_valor'] = $data_s_total['total_s'] ?? 0.00;
                    $current_barber_report['total_gorjetas'] = $data_s_total['total_g'] ?? 0.00;
                }
                $stmt_s_total->close();
            } else {
                 error_log("Erro ao preparar query de totais de serviços: " . $mysqli->error);
            }

            // 2. Contagem de Tipos de Serviço e Lista Detalhada (Tabelas Atendimentos e servicos)
            // **CORREÇÃO PRINCIPAL AQUI: Nome da tabela 'servicos' e coluna 'service_id'**
            // Assumindo nomes: Atendimentos (barbeiro_id, servico_id, registrado_em, preco_cobrado, gorjeta)
            //                   servicos (service_id, nome)
            $sql_serv_details = "SELECT s.nome AS nome_servico, a.preco_cobrado, a.gorjeta, a.registrado_em 
                                 FROM Atendimentos a
                                 JOIN servicos s ON a.servico_id = s.service_id 
                                 WHERE a.barbeiro_id = ? AND a.registrado_em BETWEEN ? AND ?
                                 ORDER BY a.registrado_em ASC";
            if ($stmt_serv_det = $mysqli->prepare($sql_serv_details)) {
                $stmt_serv_det->bind_param("iss", $barbeiro_id_process, $inicio_semana_sql, $fim_semana_sql);
                $stmt_serv_det->execute();
                $result_serv_det = $stmt_serv_det->get_result();
                while ($serv_det = $result_serv_det->fetch_assoc()) {
                    $current_barber_report['servicos_detalhados'][] = $serv_det;
                    $nome_servico_lower = strtolower($serv_det['nome_servico']); // Para comparação case-insensitive
                    if (strpos($nome_servico_lower, 'cabelo') !== false || strpos($nome_servico_lower, 'corte') !== false) {
                        $current_barber_report['contagem_cabelo']++;
                    }
                    if (strpos($nome_servico_lower, 'barba') !== false) {
                        $current_barber_report['contagem_barba']++;
                    }
                    if (strpos($nome_servico_lower, 'sobrancelha') !== false) {
                        $current_barber_report['contagem_sobrancelha']++;
                    }
                    if (strpos($nome_servico_lower, 'pezinho') !== false || strpos($nome_servico_lower, 'acabamento') !== false) {
                        $current_barber_report['contagem_pezinho']++;
                    }
                }
                $stmt_serv_det->close();
            } else {
                error_log("Erro ao preparar query de detalhes de serviços: " . $mysqli->error);
            }


            // 3. Vendas de Produtos (Tabela Vendas_Produtos)
            // Assumindo nomes: valor_total_venda, barbeiro_id, vendido_em
            $sql_prod = "SELECT SUM(valor_total_venda) as total_p FROM Vendas_Produtos WHERE barbeiro_id = ? AND vendido_em BETWEEN ? AND ?";
            if ($stmt_p = $mysqli->prepare($sql_prod)) {
                $stmt_p->bind_param("iss", $barbeiro_id_process, $inicio_semana_sql, $fim_semana_sql);
                $stmt_p->execute();
                $res_p = $stmt_p->get_result();
                if ($data_p = $res_p->fetch_assoc()) {
                    $current_barber_report['total_produtos_valor'] = $data_p['total_p'] ?? 0.00;
                }
                $stmt_p->close();
            } else {
                 error_log("Erro ao preparar query de vendas de produtos: " . $mysqli->error);
            }

            // 4. Vales (Tabela Vales_Barbeiro)
            // Assumindo nomes: valor, barbeiro_id, registrado_em
            $sql_val = "SELECT SUM(valor) as total_v FROM Vales_Barbeiro WHERE barbeiro_id = ? AND registrado_em BETWEEN ? AND ?";
            if ($stmt_v = $mysqli->prepare($sql_val)) {
                $stmt_v->bind_param("iss", $barbeiro_id_process, $inicio_semana_sql, $fim_semana_sql);
                $stmt_v->execute();
                $res_v = $stmt_v->get_result();
                if ($data_v = $res_v->fetch_assoc()) {
                    $current_barber_report['total_vales_valor'] = $data_v['total_v'] ?? 0.00;
                }
                $stmt_v->close();
            } else {
                error_log("Erro ao preparar query de vales: " . $mysqli->error);
            }

            // 5. Comissão
            $current_barber_report['comissao_calculada'] = $current_barber_report['total_servicos_valor'] * $current_barber_report['taxa_comissao'];
            
            // 6. Total a Pagar
            $current_barber_report['total_a_pagar'] = $current_barber_report['comissao_calculada'] + $current_barber_report['total_gorjetas'] - $current_barber_report['total_vales_valor'];
            
            $report_data[] = $current_barber_report;
        }
    } catch (Exception $e) {
        $_SESSION['message'] = "ERRO: " . $e->getMessage() . 
                               "<br>Arquivo: " . $e->getFile() . " (Linha: " . $e->getLine() . ")" .
                               "<br>Input de data original: " . htmlspecialchars($selected_week_start_input ?? 'N/A');
        $_SESSION['message_type'] = "error";
        error_log("Exception in view_reports.php: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios Semanais - Painel Admin</title>
    <link rel="stylesheet" href="css/admin_style.css">
    <style>
        .service-details ul { list-style-type: disc; padding-left: 20px; margin-top:5px; font-size:0.9em; color:#ccc;}
        .service-details li { margin-bottom: 3px; }
        .service-details strong {color: #f0f0f0;}
    </style>
</head>
<body>
    <header class="admin-header">
        <h1>Painel Administrativo - Barbearia JB</h1>
        <a href="../logout.php">Sair (<?php echo htmlspecialchars($_SESSION["name"]); ?>)</a>
    </header>

    <div class="admin-container">
        <aside class="admin-sidebar">
            <h2>Menu</h2>
            <ul>
                <li><a href="dashboard.php" class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">Gerenciar Barbeiros</a></li>
                <li><a href="manage_services.php" class="<?php echo ($current_page == 'manage_services.php') ? 'active' : ''; ?>">Gerenciar Serviços</a></li>
                <li><a href="manage_products.php" class="<?php echo ($current_page == 'manage_products.php') ? 'active' : ''; ?>">Gerenciar Produtos</a></li>
                <li><a href="view_reports.php" class="<?php echo ($current_page == 'view_reports.php') ? 'active' : ''; ?>">Relatórios Semanais</a></li>
                <li><a href="manage_recommendations.php" class="<?php echo ($current_page == 'manage_recommendations.php') ? 'active' : ''; ?>">Moderar Recomendações</a></li>
            </ul>
        </aside>

        <main class="admin-main-content">
            <h2>Relatórios Semanais dos Barbeiros</h2>

            <?php
            if (isset($_SESSION['message'])) {
                echo '<div class="message ' . htmlspecialchars($_SESSION['message_type']) . '">' . nl2br(htmlspecialchars($_SESSION['message'])) . '</div>';
                unset($_SESSION['message']);
                unset($_SESSION['message_type']);
            }
            ?>

            <section class="form-section">
                <h3>Selecionar Período e Barbeiro</h3>
                <form action="view_reports.php" method="GET">
                    <div class="form-group">
                        <label for="week_start">Selecione uma data na semana desejada (relatório de Seg a Sáb):</label>
                        <input type="date" id="week_start" name="week_start" value="<?php echo htmlspecialchars($selected_week_start_input ?? date('Y-m-d')); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="barbeiro_id">Barbeiro:</label>
                        <select name="barbeiro_id" id="barbeiro_id" required>
                            <option value="all" <?php echo ($selected_barbeiro_id == 'all') ? 'selected' : ''; ?>>Todos os Barbeiros</option>
                            <?php foreach($barbeiros_lista as $barb): ?>
                                <option value="<?php echo $barb['user_id']; ?>" <?php echo ($selected_barbeiro_id == $barb['user_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($barb['name']); // ou $barb['nome'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <button type="submit">Gerar Relatório</button>
                    </div>
                </form>
            </section>

            <?php if ($report_data !== null && !empty($report_data)): ?>
            <section>
                <h3>Relatório para a Semana de <?php echo htmlspecialchars(date('d/m/Y', strtotime($selected_week_start_display))); ?> a <?php echo htmlspecialchars(date('d/m/Y', strtotime($selected_week_end_display))); ?> (Sábado)</h3>
                <?php if ($selected_barbeiro_id != 'all' && !empty($barbeiro_nome_report)): ?>
                    <h4>Barbeiro: <?php echo htmlspecialchars($barbeiro_nome_report); ?></h4>
                <?php endif; ?>

                <table class="admin-table">
                    <thead>
                        <tr>
                            <?php if ($selected_barbeiro_id == 'all'): ?>
                                <th>Barbeiro</th>
                            <?php endif; ?>
                            <th>Serviços (R$)</th>
                            <th>Gorjetas (R$)</th>
                            <th>Produtos (R$)</th>
                            <th>Vales (R$)</th>
                            <th>Tx. Comissão</th>
                            <th>Comissão (R$)</th>
                            <th>Qtd. Cabelo</th>
                            <th>Qtd. Barba</th>
                            <th>Qtd. Sobrancelha</th>
                            <th>Qtd. Pezinho</th>
                            <th style="color: #f39c12;">A Pagar (R$)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($report_data as $barber_report_item): ?>
                        <tr>
                            <?php if ($selected_barbeiro_id == 'all'): ?>
                                <td><?php echo htmlspecialchars($barber_report_item['nome']); ?></td>
                            <?php endif; ?>
                            <td><?php echo number_format((float)$barber_report_item['total_servicos_valor'], 2, ',', '.'); ?></td>
                            <td><?php echo number_format((float)$barber_report_item['total_gorjetas'], 2, ',', '.'); ?></td>
                            <td><?php echo number_format((float)$barber_report_item['total_produtos_valor'], 2, ',', '.'); ?></td>
                            <td><?php echo number_format((float)$barber_report_item['total_vales_valor'], 2, ',', '.'); ?></td>
                            <td><?php echo number_format((float)$barber_report_item['taxa_comissao'] * 100, 0); ?>%</td>
                            <td><?php echo number_format((float)$barber_report_item['comissao_calculada'], 2, ',', '.'); ?></td>
                            <td><?php echo $barber_report_item['contagem_cabelo']; ?></td>
                            <td><?php echo $barber_report_item['contagem_barba']; ?></td>
                            <td><?php echo $barber_report_item['contagem_sobrancelha']; ?></td>
                            <td><?php echo $barber_report_item['contagem_pezinho']; ?></td>
                            <td style="font-weight:bold; color: #f39c12;"><?php echo number_format((float)$barber_report_item['total_a_pagar'], 2, ',', '.'); ?></td>
                        </tr>
                        <?php if ($selected_barbeiro_id != 'all' && !empty($barber_report_item['servicos_detalhados'])): ?>
                            <tr>
                                <td colspan="11" class="service-details"> <strong>Detalhes dos Serviços Realizados nesta Semana:</strong>
                                    <ul>
                                    <?php foreach($barber_report_item['servicos_detalhados'] as $serv_det): ?>
                                        <li>
                                            <?php echo htmlspecialchars($serv_det['nome_servico']); ?>
                                            - R$ <?php echo number_format((float)$serv_det['preco_cobrado'], 2, ',', '.'); ?>
                                            | Gorjeta: R$ <?php echo number_format((float)$serv_det['gorjeta'], 2, ',', '.'); ?>
                                            | Data: <?php echo date('d/m/Y H:i', strtotime($serv_det['registrado_em'])); ?>
                                        </li>
                                    <?php endforeach; ?>
                                    </ul>
                                </td>
                            </tr>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
            <?php elseif ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['week_start'])): ?>
                <p>Nenhum dado encontrado para o período e barbeiro selecionado.</p>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>