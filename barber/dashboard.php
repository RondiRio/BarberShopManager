<?php
session_start();
require_once '../config/database.php'; // Ajuste o caminho conforme necessário

// Verifica se o usuário está logado e se é barbeiro
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'barber') {
    header("location: ../login.php");
    exit;
}

// Assume-se que o barbearia_id está na sessão após o login
$barbeiro_id = $_SESSION["user_id"];
$barbearia_id = $_SESSION["barbearia_id"] ?? 0; // Garante que a variável exista
$taxa_comissao = isset($_SESSION["commission_rate"]) ? floatval($_SESSION["commission_rate"]) : 0.0;

// LÓGICA DE CONFIGURAÇÕES ATUALIZADA (Single-Tenant)
$agendamento_habilitado = false;
$sql_check_config = "SELECT agendamento_ativo FROM configuracoes WHERE config_id = 1";
if ($result_config = $mysqli->query($sql_check_config)) {
    if ($config = $result_config->fetch_assoc()) {
        $agendamento_habilitado = (bool)$config['agendamento_ativo'];
    }
    $result_config->free();
}

// --- SE AGENDAMENTO ESTIVER ATIVO, BUSCAR A AGENDA DO DIA ---
$agendamentos_de_hoje = [];
if ($agendamento_habilitado) {
    $sql_agenda = "SELECT 
                        a.data_hora_agendamento,
                        u.name AS cliente_nome,
                        s.nome AS servico_nome
                   FROM agendamentos a
                   JOIN Users u ON a.cliente_id = u.user_id
                   JOIN servicos s ON a.servico_id = s.service_id
                   WHERE 
                        a.barbeiro_id = ? AND
                        DATE(a.data_hora_agendamento) = CURDATE() AND
                        a.status = 'Confirmado'
                   ORDER BY a.data_hora_agendamento ASC";
    
    if ($stmt_agenda = $mysqli->prepare($sql_agenda)) {
        $stmt_agenda->bind_param("i", $barbeiro_id);
        $stmt_agenda->execute();
        $result_agenda = $stmt_agenda->get_result();
        while ($row = $result_agenda->fetch_assoc()) {
            $agendamentos_de_hoje[] = $row;
        }
        $stmt_agenda->close();
    }
}

// --- LÓGICA EXISTENTE PARA MÉTRICAS DA SEMANA ---
$hoje = new DateTime();
$dia_semana = $hoje->format('N'); // 1 (Segunda) a 7 (Domingo)
$inicio_semana_dt = clone $hoje;
if ($dia_semana == 7) { 
    $inicio_semana_dt->modify('last monday');
} else {
    $inicio_semana_dt->modify('-'.($dia_semana-1).' days');
}
$inicio_semana_dt->setTime(0,0,0);
$fim_semana_dt = clone $hoje;

$inicio_semana_sql = $inicio_semana_dt->format('Y-m-d H:i:s');
$fim_semana_sql = $fim_semana_dt->format('Y-m-d H:i:s');

// --- Buscar Dados para Estatísticas ---
$total_servicos_comissionaveis_semana = 0.00;
$total_gorjetas_semana = 0.00;
$total_vendas_produtos_semana = 0.00;
$total_vales_semana = 0.00;

// Query para totais de serviços e gorjetas
$sql_totais_servicos = "SELECT SUM(preco_cobrado) as total_servicos, SUM(gorjeta) as total_gorjetas FROM atendimentos WHERE barbeiro_id = ? AND registrado_em BETWEEN ? AND ?";
if ($stmt_totais_serv = $mysqli->prepare($sql_totais_servicos)) {
    $stmt_totais_serv->bind_param("iss", $barbeiro_id, $inicio_semana_sql, $fim_semana_sql);
    $stmt_totais_serv->execute();
    $result_totais_serv = $stmt_totais_serv->get_result();
    if ($totais_serv = $result_totais_serv->fetch_assoc()) {
        $total_servicos_comissionaveis_semana = $totais_serv['total_servicos'] ?? 0.00;
        $total_gorjetas_semana = $totais_serv['total_gorjetas'] ?? 0.00;
    }
    $stmt_totais_serv->close();
}

// Query para clientes únicos (contando nomes distintos não nulos)
$quantidade_clientes_semana = 0;
$sql_clientes_unicos = "SELECT COUNT(DISTINCT cliente_nome) as contagem_unicos FROM atendimentos WHERE barbeiro_id = ? AND registrado_em BETWEEN ? AND ? AND cliente_nome IS NOT NULL AND cliente_nome != ''";
if ($stmt_clientes = $mysqli->prepare($sql_clientes_unicos)) {
    $stmt_clientes->bind_param("iss", $barbeiro_id, $inicio_semana_sql, $fim_semana_sql);
    $stmt_clientes->execute();
    $result_clientes = $stmt_clientes->get_result();
    if($data_clientes = $result_clientes->fetch_assoc()) {
        $quantidade_clientes_semana = $data_clientes['contagem_unicos'];
    }
    $stmt_clientes->close();
}

// Total de Vendas de Produtos
$sql_vendas = "SELECT SUM(valor_total_venda) as total_produtos FROM Vendas_Produtos WHERE barbeiro_id = ? AND vendido_em BETWEEN ? AND ?";
if ($stmt_vendas = $mysqli->prepare($sql_vendas)) {
    $stmt_vendas->bind_param("iss", $barbeiro_id, $inicio_semana_sql, $fim_semana_sql);
    $stmt_vendas->execute();
    $result_vendas = $stmt_vendas->get_result();
    if ($venda = $result_vendas->fetch_assoc()) {
        $total_vendas_produtos_semana = $venda['total_produtos'] ?? 0.00;
    }
    $stmt_vendas->close();
}

// Total de Vales
$sql_vales = "SELECT SUM(valor) as total_vales FROM Vales_Barbeiro WHERE barbeiro_id = ? AND registrado_em BETWEEN ? AND ?";
if ($stmt_vales = $mysqli->prepare($sql_vales)) {
    $stmt_vales->bind_param("iss", $barbeiro_id, $inicio_semana_sql, $fim_semana_sql);
    $stmt_vales->execute();
    $result_vales = $stmt_vales->get_result();
    if ($vale = $result_vales->fetch_assoc()) {
        $total_vales_semana = $vale['total_vales'] ?? 0.00;
    }
    $stmt_vales->close();
}

// Cálculos Financeiros
$comissao_calculada_semana = $total_servicos_comissionaveis_semana * $taxa_comissao;
$total_a_pagar_semana = ($comissao_calculada_semana + $total_gorjetas_semana) - $total_vales_semana;

// --- Buscar Listas para Formulários ---
$servicos_comissionaveis = [];
$sql_lista_servicos = "SELECT service_id, nome, preco FROM servicos WHERE ativo = 1 ORDER BY nome ASC";
if ($result_lista_servicos = $mysqli->query($sql_lista_servicos)) {
    while ($serv = $result_lista_servicos->fetch_assoc()) {
        $servicos_comissionaveis[] = $serv;
    }
    $result_lista_servicos->free();
}

$produtos_lista = [];
$sql_lista_produtos = "SELECT produto_id, nome, preco FROM produtos WHERE ativo = 1 ORDER BY nome ASC";
if ($result_lista_produtos = $mysqli->query($sql_lista_produtos)) {
    while ($prod = $result_lista_produtos->fetch_assoc()) {
        $produtos_lista[] = $prod;
    }
    $result_lista_produtos->free();
}

// Buscar fotos do mural
$fotos_mural = [];
$sql_fotos = "SELECT foto_id, caminho_imagem, legenda, DATE_FORMAT(data_upload, '%d/%m/%Y %H:%i') as data_formatada
              FROM fotos_barbeiro
              WHERE barbeiro_id = ?
              ORDER BY data_upload DESC";
if ($stmt_fotos = $mysqli->prepare($sql_fotos)) {
    $stmt_fotos->bind_param("i", $barbeiro_id);
    $stmt_fotos->execute();
    $result_fotos = $stmt_fotos->get_result();
    while ($foto = $result_fotos->fetch_assoc()) {
        $fotos_mural[] = $foto;
    }
    $stmt_fotos->close();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Barbeiro - Barbearia JB</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #121212; color: #FFF; padding: 20px; margin:0; }
        .main-container { max-width: 1200px; margin: auto; background-color: #1E1E1E; padding: 20px; border-radius: 8px; }
        .admin-header { background-color: #000; color: #FFF; padding: 15px 20px; margin-bottom:20px; border-bottom:3px solid #f39c12;}
        .admin-header h1 {margin:0; font-size: 1.8em;}
        .admin-header a {color: #FFF; text-decoration:none; float:right;}
        h1, h2, h3 { color: #FFF; }
        a { color: #f39c12; }
        a:hover { color: #FFF; }
        .dashboard-stats { display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 30px;}
        .stat-card { background-color: #282828; padding: 20px; border-radius: 8px; flex-grow: 1; flex-basis: 200px; text-align: center; border-left: 5px solid #f39c12;}
        .stat-card h3 { margin-top: 0; font-size: 1.1em; color: #BBB; text-transform: uppercase;}
        .stat-card p { font-size: 1.8em; font-weight: bold; color: #FFF; margin-bottom:0;}
        
        .forms-section { display: flex; flex-wrap: wrap; gap: 30px; margin-top: 30px;}
        .form-container { background-color: #282828; padding: 20px; border-radius: 8px; flex: 1; min-width: 300px; }
        .form-container h3 { margin-top: 0; color: #f39c12; border-bottom: 1px solid #444; padding-bottom: 10px;}
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; font-size:0.9em; color:#DDD; }
        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group select,
        .form-group textarea {
            width: calc(100% - 20px);
            padding: 10px;
            background-color: #333;
            border: 1px solid #555;
            color: #FFF;
            border-radius: 4px;
            font-size: 1em;
        }
        .form-group input[type="number"] { appearance: textfield; -moz-appearance: textfield; } /* Para esconder setas de number input */
        .form-group button {
            background-color: #f39c12; color: #000; border: none; padding: 10px 15px;
            border-radius: 4px; cursor: pointer; font-weight: bold; text-transform: uppercase;
        }
        .form-group button:hover { background-color: #e67e22; }
        .message { padding: 10px; margin-bottom: 15px; border-radius:4px; text-align:center;}
        .message.success { background-color: #27ae60; color:white; }
        .message.error { background-color: #c0392b; color:white; }

        .photo-mural { margin-top: 30px; border-top:1px solid #444; padding-top:20px;}
        .photo-mural img { width: 150px; height: 150px; object-fit: cover; margin: 5px; border: 2px solid #444; border-radius: 4px;}

        /* ESTILOS PARA A AGENDA */
        .agenda-container {
            background-color: #282828;
            padding: 20px;
            border-radius: 8px;
            margin-top: 30px;
        }
        .agenda-container h2 {
            margin-top: 0;
            color: #f39c12;
            border-bottom: 1px solid #444;
            padding-bottom: 10px;
        }
        .agenda-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .agenda-table th, .agenda-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #444;
        }
        .agenda-table th {
            background-color: #333;
            color: #f39c12;
            text-transform: uppercase;
            font-size: 0.9em;
        }
        .agenda-table td {
            color: #ddd;
        }
        .agenda-table tr:last-child td {
            border-bottom: none;
        }
        .no-appointments {
            text-align: center;
            padding: 20px;
            color: #888;
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

        <?php
            if (isset($_SESSION['form_message'])) {
                echo '<div class="message ' . htmlspecialchars($_SESSION['form_message_type']) . '">' . htmlspecialchars($_SESSION['form_message']) . '</div>';
                unset($_SESSION['form_message']);
                unset($_SESSION['form_message_type']);
            }
        ?>

        <h2>Suas Métricas da Semana (<?php echo $inicio_semana_dt->format('d/m') . ' - ' . $fim_semana_dt->format('d/m'); ?>)</h2>
        <div class="dashboard-stats">
            <div class="stat-card">
                <h3>Serviços (R$)</h3>
                <p>R$ <?php echo number_format($total_servicos_comissionaveis_semana, 2, ',', '.'); ?></p>
            </div>
            <div class="stat-card">
                <h3>Gorjetas (R$)</h3>
                <p>R$ <?php echo number_format($total_gorjetas_semana, 2, ',', '.'); ?></p>
            </div>
            <div class="stat-card">
                <h3>Produtos (R$)</h3>
                <p>R$ <?php echo number_format($total_vendas_produtos_semana, 2, ',', '.'); ?></p>
            </div>
            <div class="stat-card">
                <h3>Vales (R$)</h3>
                <p>R$ <?php echo number_format($total_vales_semana, 2, ',', '.'); ?></p>
            </div>
             <div class="stat-card">
                <h3>Sua Comissão (R$)</h3>
                <p>R$ <?php echo number_format($comissao_calculada_semana, 2, ',', '.'); ?></p>
            </div>
            <div class="stat-card">
                <h3>Clientes Atendidos</h3>
                <p><?php echo $quantidade_clientes_semana; ?></p>
            </div>
        </div>

        <?php if ($agendamento_habilitado): ?>
            <div class="agenda-container">
                <h2>📅 Sua Agenda para Hoje (<?php echo date('d/m/Y'); ?>)</h2>
                
                <?php if (!empty($agendamentos_de_hoje)): ?>
                    <table class="agenda-table">
                        <thead>
                            <tr>
                                <th>Horário</th>
                                <th>Cliente</th>
                                <th>Serviço</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($agendamentos_de_hoje as $agendamento): ?>
                                <tr>
                                    <td><?php echo date('H:i', strtotime($agendamento['data_hora_agendamento'])); ?></td>
                                    <td><?php echo htmlspecialchars($agendamento['cliente_nome']); ?></td>
                                    <td><?php echo htmlspecialchars($agendamento['servico_nome']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="no-appointments">Você não tem nenhum agendamento para hoje.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <div class="forms-section">
            <div class="form-container">
                <h3>✂️ Registrar Atendimento</h3>
                <form action="handle_add_atendimento.php" method="post">
                    <div class="form-group">
                        <label for="servico_id">Serviço:</label>
                        <select name="servico_id" id="servico_id" required>
                            <option value="">Selecione o serviço</option>
                            <?php foreach ($servicos_comissionaveis as $serv): ?>
                                <option value="<?php echo $serv['service_id']; ?>" data-preco="<?php echo $serv['preco']; ?>">
                                    <?php echo htmlspecialchars($serv['nome']) . " (R$ " . number_format($serv['preco'], 2, ',', '.') . ")"; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="cliente_nome">Nome do Cliente (Opcional):</label>
                        <input type="text" name="cliente_nome" id="cliente_nome">
                    </div>
                    <div class="form-group">
                        <label for="gorjeta">Gorjeta (R$):</label>
                        <input type="number" name="gorjeta" id="gorjeta" step="0.01" min="0" placeholder="0.00">
                    </div>
                    <div class="form-group">
                        <label for="metodo_pagamento_servico">Método de Pagamento:</label>
                        <select name="metodo_pagamento" id="metodo_pagamento_servico" required>
                            <option value="dinheiro">Dinheiro</option>
                            <option value="cartao_debito">Cartão de Débito</option>
                            <option value="cartao_credito">Cartão de Crédito</option>
                            <option value="pix">PIX</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <button type="submit">Registrar Atendimento</button>
                    </div>
                </form>
            </div>

            <div class="form-container">
                <h3>🍺 Registrar Venda de Produto</h3>
                <form action="handle_add_venda_produto.php" method="post">
                    <div class="form-group">
                        <label for="produto_id">Produto:</label>
                        <select name="produto_id" id="produto_id" required>
                             <option value="">Selecione o produto</option>
                            <?php foreach ($produtos_lista as $prod): ?>
                                <option value="<?php echo $prod['produto_id']; ?>" data-preco="<?php echo $prod['preco']; ?>">
                                    <?php echo htmlspecialchars($prod['nome']) . " (R$ " . number_format($prod['preco'], 2, ',', '.') . ")"; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="quantidade">Quantidade:</label>
                        <input type="number" name="quantidade" id="quantidade" min="1" value="1" required>
                    </div>
                     <div class="form-group">
                        <label for="metodo_pagamento_produto">Método de Pagamento:</label>
                        <select name="metodo_pagamento" id="metodo_pagamento_produto" required>
                            <option value="dinheiro">Dinheiro</option>
                            <option value="cartao_debito">Cartão de Débito</option>
                            <option value="cartao_credito">Cartão de Crédito</option>
                            <option value="pix">PIX</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <button type="submit">Registrar Venda</button>
                    </div>
                </form>
            </div>

            <div class="form-container">
                <h3>💰 Registrar Vale</h3>
                <form action="handle_add_vale.php" method="post">
                    <div class="form-group">
                        <label for="valor_vale">Valor do Vale (R$):</label>
                        <input type="number" name="valor_vale" id="valor_vale" step="0.01" min="0.01" required placeholder="0.00">
                    </div>
                    <div class="form-group">
                        <label for="descricao_vale">Descrição (Opcional):</label>
                        <textarea name="descricao_vale" id="descricao_vale" rows="2"></textarea>
                    </div>
                    <div class="form-group">
                        <button type="submit">Registrar Vale</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="photo-mural">
            <h3>📸 Seu Mural de Fotos</h3>
            <form action="handle_upload_foto.php" method="post" enctype="multipart/form-data" style="margin-bottom:25px; background-color: #333; padding:15px; border-radius:5px;">
                <div class="form-group">
                    <label for="fotoCliente">Adicionar foto ao mural (JPG, PNG, GIF - Máx 5MB):</label>
                    <input type="file" name="fotoCliente" id="fotoCliente" accept="image/jpeg,image/png,image/gif" required style="padding:10px 0; background-color: transparent; border: none;">
                </div>
                <div class="form-group">
                    <label for="caption">Legenda (Opcional):</label>
                    <input type="text" name="caption" id="caption" placeholder="Descreva o corte ou o cliente">
                </div>
                <button type="submit">Enviar Foto</button>
            </form>

            <div class="mural-gallery" style="display: flex; flex-wrap: wrap; gap: 15px; justify-content: center;">
                <?php if (!empty($fotos_mural)): ?>
                    <?php foreach ($fotos_mural as $foto): ?>
                        <div class="foto-item" style="background-color:#333; padding:10px; border-radius:5px; text-align:center; width: 200px;">
                            <img src="../<?php echo htmlspecialchars($foto['caminho_imagem']); ?>" alt="<?php echo htmlspecialchars($foto['legenda'] ?? 'Foto do Mural'); ?>" style="width: 100%; height: 180px; object-fit: cover; border-radius: 3px; margin-bottom: 8px;">
                            <?php if (!empty($foto['legenda'])): ?>
                                <p style="font-size: 0.85em; color: #ccc; margin-bottom: 5px;"><?php echo htmlspecialchars($foto['legenda']); ?></p>
                            <?php endif; ?>
                            <p style="font-size: 0.75em; color: #888;">Enviada em: <?php echo $foto['data_formatada']; ?></p>
                            </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>Você ainda não adicionou nenhuma foto ao seu mural.</p>
                <?php endif; ?>
            </div>
        </div>

    </div>
</body>
</html>