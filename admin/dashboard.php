<?php
$current_page = basename($_SERVER['PHP_SELF']);
session_start();
require_once '../config/database.php';

// --- SEGURANÇA ---
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'admin') {
    header("location: ../login.php");
    exit;
}

// --- BUSCAR DADOS ---

// 1. Lógica para buscar barbeiros existentes (seu código original)
$barbers = [];
$sql_barbers = "SELECT user_id, name, email, commission_rate, is_active FROM Users WHERE role = 'barber' ORDER BY name ASC";
if ($result = $mysqli->query($sql_barbers)) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $barbers[] = $row;
        }
    }
    $result->free();
}

// LÓGICA ATUALIZADA: Buscar a única linha de configuração
$configuracoes = [ /* ... array padrão ... */ ];
// A query agora é direta, buscando pela única linha que existirá na tabela
$sql_configs = "SELECT agendamento_ativo, permitir_agendamento_cliente, taxa_cancelamento_ativa, valor_taxa_cancelamento FROM configuracoes WHERE config_id = 1";
if ($result_configs = $mysqli->query($sql_configs)) {
    if ($configs_db = $result_configs->fetch_assoc()) {
        $configuracoes = array_merge($configuracoes, $configs_db);
    }
    $result_configs->free();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Admin - Barbearia JB</title>
    <link rel="stylesheet" href="css/admin_style.css">
    
    <style>
        .stats-container {
            display: flex;
            gap: 20px;
            margin-bottom: 40px;
            flex-wrap: wrap;
        }
        .stat-card {
            background-color: #2a2a2e;
            padding: 20px;
            border-radius: 8px;
            flex-grow: 1;
            border-left: 5px solid #f39c12;
            min-width: 220px;
        }
        .stat-card h3 {
            margin-top: 0;
            font-size: 1em;
            color: #ccc;
            text-transform: uppercase;
        }
        .stat-card p {
            margin-bottom: 0;
            font-size: 1.5em;
            font-weight: bold;
        }
        .status-active {
            color: #27ae60; /* Verde */
        }
        .status-inactive {
            color: #c0392b; /* Vermelho */
        }
        .status-neutral {
            color: #bdc3c7; /* Cinza */
        }
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
                <li><a href="manage_products.php" class="<?php echo($current_page == 'manage_products.php') ? 'active' : '' ?>">Gerenciar Produtos</a></li>
                <li><a href="view_reports.php" class="<?php echo($current_page == 'view_reports.php') ? 'active' : '' ?>">Relatórios Semanais</a></li>
                <li><a href="manage_recommendations.php" class="<?php echo($current_page == 'manage_recommendations.php') ? 'active' : '' ?>">Moderar Recomendações</a></li>
                <li><a href="configuracoes.php" class="<?php echo($current_page == 'configuracoes.php') ? 'active' : '' ?>">Configurações</a></li>
            </ul>
        </aside>

        <main class="admin-main-content">
            
            <section>
                <h2>Visão Geral do Sistema</h2>
                <div class="stats-container">
                    <div class="stat-card">
                        <h3>Agendamento Online</h3>
                        <?php if ($configuracoes['agendamento_ativo']): ?>
                            <p class="status-active">ATIVO</p>
                        <?php else: ?>
                            <p class="status-inactive">INATIVO</p>
                        <?php endif; ?>
                    </div>

                    <?php if ($configuracoes['agendamento_ativo']): // Só mostra os cards abaixo se o sistema estiver ativo ?>
                        <div class="stat-card">
                            <h3>Agend. pelo Cliente</h3>
                            <?php if ($configuracoes['permitir_agendamento_cliente']): ?>
                                <p class="status-active">PERMITIDO</p>
                            <?php else: ?>
                                <p class="status-neutral">BLOQUEADO</p>
                            <?php endif; ?>
                        </div>
                        <div class="stat-card">
                            <h3>Taxa de Cancelamento</h3>
                            <?php if ($configuracoes['taxa_cancelamento_ativa']): ?>
                                <p class="status-active">ATIVA - R$ <?php echo number_format($configuracoes['valor_taxa_cancelamento'], 2, ',', '.'); ?></p>
                            <?php else: ?>
                                <p class="status-inactive">INATIVA</p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
            
            <section>
                <h2>Gerenciar Barbeiros</h2>

                <?php
                if (isset($_SESSION['message'])) {
                    echo '<div class="message ' . htmlspecialchars($_SESSION['message_type']) . '">' . htmlspecialchars($_SESSION['message']) . '</div>';
                    unset($_SESSION['message']);
                    unset($_SESSION['message_type']);
                }
                ?>

                <div class="form-section">
                    <h3>Adicionar Novo Barbeiro</h3>
                    <form action="handle_add_barber.php" method="POST">
                        <div class="form-group">
                            <label for="name">Nome Completo:</label>
                            <input type="text" id="name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email:</label>
                            <input type="email" id="email" name="email" required placeholder="nomebarbeiro@barbeariajb.com">
                        </div>
                        <div class="form-group">
                            <label for="password">Senha Inicial:</label>
                            <input type="password" id="password" name="password" required>
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Confirmar Senha:</label>
                            <input type="password" id="confirm_password" name="confirm_password" required>
                        </div>
                        <div class="form-group">
                            <label for="commission_rate">Taxa de Comissão (Ex: 0.5 para 50%):</label>
                            <input type="number" id="commission_rate" name="commission_rate" step="0.01" min="0" max="1" required placeholder="0.50">
                        </div>
                        <div class="form-group">
                            <button type="submit">Adicionar Barbeiro</button>
                        </div>
                    </form>
                </div>
            </section>

            <section>
                <h3>Barbeiros Cadastrados</h3>
                <?php if (!empty($barbers)): ?>
                    <div class="responsive-table-wrapper">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Email</th>
                                    <th>Comissão</th>
                                    <th>Status</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($barbers as $barber): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($barber['name']); ?></td>
                                        <td><?php echo htmlspecialchars($barber['email']); ?></td>
                                        <td><?php echo htmlspecialchars(number_format($barber['commission_rate'] * 100, 0)); ?>%</td>
                                        <td><?php echo $barber['is_active'] ? 'Ativo' : 'Inativo'; ?></td>
                                        <td>
                                            <a href="edit_barber.php?id=<?php echo $barber['user_id']; ?>" class="action-link">Editar</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>Nenhum barbeiro cadastrado ainda.</p>
                <?php endif; ?>
            </section>
            
        </main>
    </div>
    
    <script>
    // Seu script de responsividade da tabela permanece o mesmo
    document.addEventListener('DOMContentLoaded', function() {
        var headers = Array.from(document.querySelectorAll('.admin-table thead th')).map(function(th) {
            return th.textContent.trim();
        });
        document.querySelectorAll('.admin-table tbody tr').forEach(function(row) {
            row.querySelectorAll('td').forEach(function(td, i) {
                td.setAttribute('data-label', headers[i]);
            });
        });
    });
    </script>
</body>
</html>