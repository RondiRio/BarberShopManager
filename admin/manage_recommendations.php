<?php
session_start();
require_once '../config/database.php'; // Ajuste o caminho se necessário

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'admin') {
    header("location: ../login.php");
    exit;
}

$current_page = basename($_SERVER['PHP_SELF']);

// Lógica para aprovar ou rejeitar/excluir recomendação
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $recomendacao_id = isset($_POST['recomendacao_id']) ? (int)$_POST['recomendacao_id'] : 0;

    if ($recomendacao_id > 0) {
        if ($_POST['action'] == 'aprovar') {
            $sql_action = "UPDATE recomendacoes SET aprovado = 1 WHERE recomendacao_id = ?";
            $message_action = "aprovada";
        } elseif ($_POST['action'] == 'desaprovar') {
            $sql_action = "UPDATE recomendacoes SET aprovado = 0 WHERE recomendacao_id = ?";
            $message_action = "desaprovada (movida para pendente)";
        } elseif ($_POST['action'] == 'excluir') {
            $sql_action = "DELETE FROM recomendacoes WHERE recomendacao_id = ?";
            $message_action = "excluída";
        }

        if (isset($sql_action)) {
            if ($stmt_action = $mysqli->prepare($sql_action)) {
                $stmt_action->bind_param("i", $recomendacao_id);
                if ($stmt_action->execute()) {
                    $_SESSION['message'] = "Recomendação ID $recomendacao_id $message_action com sucesso.";
                    $_SESSION['message_type'] = "success";
                } else {
                    $_SESSION['message'] = "Erro ao executar ação: " . $stmt_action->error;
                    $_SESSION['message_type'] = "error";
                }
                $stmt_action->close();
            } else {
                $_SESSION['message'] = "Erro ao preparar ação: " . $mysqli->error;
                $_SESSION['message_type'] = "error";
            }
        }
    } else {
        $_SESSION['message'] = "ID da recomendação inválido.";
        $_SESSION['message_type'] = "error";
    }
    // Recarregar a página para ver as mudanças e limpar POST
    header("location: manage_recommendations.php");
    exit;
}


// Buscar todas as recomendações
$recommendations = [];
$sql_recs = "SELECT r.recomendacao_id, COALESCE(u_cliente.name, r.nome_cliente) as nome_display_cliente,
                    u_barbeiro.name as nome_barbeiro, r.texto_recomendacao, r.aprovado,
                    DATE_FORMAT(r.data_envio, '%d/%m/%Y %H:%i') as data_formatada
             FROM recomendacoes r
             LEFT JOIN users u_cliente ON r.cliente_id = u_cliente.user_id
             LEFT JOIN users u_barbeiro ON r.barbeiro_id = u_barbeiro.user_id
             ORDER BY r.aprovado ASC, r.data_envio DESC";

if ($result_recs = $mysqli->query($sql_recs)) {
    if ($result_recs->num_rows > 0) {
        while ($row = $result_recs->fetch_assoc()) {
            $recommendations[] = $row;
        }
    }
    $result_recs->free();
} else {
    $_SESSION['message'] = "Erro ao buscar recomendações: " . $mysqli->error;
    $_SESSION['message_type'] = "error";
}

// $mysqli->close(); // Fechar no final se não for mais usado
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moderar Recomendações - Painel Admin</title>
    <link rel="stylesheet" href="css/admin_style.css">
    <style>
        .action-buttons form {
            display: inline-block;
            margin-right: 5px;
        }
        .action-buttons button {
            padding: 5px 10px;
            font-size: 0.85em;
        }
        .status-pendente { color: #f39c12; font-weight:bold; }
        .status-aprovada { color: #2ecc71; font-weight:bold; }
        .recommendation-text {
            max-height: 100px;
            overflow-y: auto;
            white-space: pre-wrap; /* Mantém quebras de linha e espaços */
            word-wrap: break-word; /* Quebra palavras longas */
            background-color: #2c3e50;
            padding: 8px;
            border-radius: 3px;
            border: 1px solid #4a627a;
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
                <li><a href="manage_products.php" class="<?php echo ($current_page == 'manage_products.php') ? 'active' : ''; ?>">Gerenciar Produtos</a></li>
                <li><a href="view_reports.php" class="<?php echo ($current_page == 'view_reports.php') ? 'active' : ''; ?>">Relatórios Semanais</a></li>
                <li><a href="manage_recommendations.php" class="<?php echo ($current_page == 'manage_recommendations.php') ? 'active' : ''; ?>">Moderar Recomendações</a></li>
            </ul>
        </aside>

        <main class="admin-main-content">
            <h2>Moderar Recomendações de Clientes</h2>

            <?php
            if (isset($_SESSION['message'])) {
                echo '<div class="message ' . htmlspecialchars($_SESSION['message_type']) . '">' . htmlspecialchars($_SESSION['message']) . '</div>';
                unset($_SESSION['message']);
                unset($_SESSION['message_type']);
            }
            ?>

            <section>
                <?php if (!empty($recommendations)): ?>
                    <div class="responsive-table-wrapper">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Cliente</th>
                                    <th style="width: 40%;">Recomendação</th>
                                    <th>Data Envio</th>
                                    <th>Status</th>
                                    <th style="width: 20%;">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recommendations as $rec): ?>
                                    <tr>
                                        <td data-label="Cliente"><?php echo htmlspecialchars($rec['nome_display_cliente']); ?></td>
                                        <td data-label="Recomendação"><div class="recommendation-text"><?php echo nl2br(htmlspecialchars($rec['texto_recomendacao'])); ?></div></td>
                                        <td data-label="Data Envio"><?php echo htmlspecialchars($rec['data_formatada']); ?></td>
                                        <td data-label="Status">
                                            <?php if ($rec['aprovado'] == 1): ?>
                                                <span class="status-aprovada">Aprovada</span>
                                            <?php else: ?>
                                                <span class="status-pendente">Pendente</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="action-buttons" data-label="Ações">
                                            <form action="manage_recommendations.php" method="POST" onsubmit="return confirm('Tem certeza?');">
                                                <input type="hidden" name="recomendacao_id" value="<?php echo $rec['recomendacao_id']; ?>">
                                                <?php if ($rec['aprovado'] == 0): // Pendente ?>
                                                    <button type="submit" name="action" value="aprovar" style="background-color: #27ae60;">Aprovar</button>
                                                <?php else: // Aprovada ?>
                                                    <button type="submit" name="action" value="desaprovar" style="background-color: #e67e22;">Desaprovar</button>
                                                <?php endif; ?>
                                                <button type="submit" name="action" value="excluir" style="background-color: #c0392b;">Excluir</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>Nenhuma recomendação para moderar no momento.</p>
                <?php endif; ?>
            </section>
        </main>
        <style>
        /* Responsividade para a tabela de recomendações */
        .responsive-table-wrapper {
            width: 100%;
            overflow-x: auto;
        }
        @media (max-width: 900px) {
            .admin-container {
                flex-direction: column;
            }
            .admin-sidebar {
                width: 100%;
                margin-bottom: 20px;
            }
            .admin-main-content {
                width: 100%;
            }
        }
        @media (max-width: 700px) {
            .admin-table thead {
                display: none;
            }
            .admin-table, .admin-table tbody, .admin-table tr, .admin-table td {
                display: block;
                width: 100%;
            }
            .admin-table tr {
                margin-bottom: 18px;
                border-bottom: 2px solid #34495e;
                background: #22303c;
                border-radius: 6px;
                box-shadow: 0 2px 6px rgba(0,0,0,0.04);
                padding: 8px 0;
            }
            .admin-table td {
                padding: 10px 12px;
                text-align: left;
                position: relative;
                border: none;
                background: none;
            }
            .admin-table td[data-label]:before {
                content: attr(data-label) ": ";
                font-weight: bold;
                color: #7ed6df;
                display: block;
                margin-bottom: 2px;
            }
            .action-buttons {
                text-align: left;
                margin-top: 8px;
            }
            .recommendation-text {
                max-height: none;
                font-size: 1em;
            }
        }
        @media (max-width: 480px) {
            .admin-header h1 {
                font-size: 1.1em;
            }
            .admin-sidebar h2 {
                font-size: 1em;
            }
            .admin-sidebar ul li a {
                font-size: 0.95em;
            }
            .admin-main-content h2 {
                font-size: 1.1em;
            }
            .recommendation-text {
                font-size: 0.98em;
            }
        }
        </style>
    </div>
</body>
</html>