<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'admin') {
    header("location: ../login.php");
    exit;
}

$current_page = basename($_SERVER['PHP_SELF']);

// Buscar produtos existentes
$products = [];
$sql_products = "SELECT produto_id, nome, preco, descricao, ativo FROM produtos ORDER BY nome ASC";
if ($result = $mysqli->query($sql_products)) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
    }
    $result->free();
} else {
    $_SESSION['message'] = "Erro ao buscar produtos: " . $mysqli->error;
    $_SESSION['message_type'] = "error";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Produtos - Painel Admin</title>
    <link rel="stylesheet" href="css/admin_style.css">
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
            <h2>Gerenciar Produtos (Bebidas)</h2>

            <?php
            if (isset($_SESSION['message'])) {
                echo '<div class="message ' . htmlspecialchars($_SESSION['message_type']) . '">' . htmlspecialchars($_SESSION['message']) . '</div>';
                unset($_SESSION['message']);
                unset($_SESSION['message_type']);
            }
            ?>

            <section class="form-section">
                <h3>Adicionar Novo Produto</h3>
                <form action="handle_add_product.php" method="POST">
                    <div class="form-group">
                        <label for="product_nome">Nome do Produto:</label>
                        <input type="text" id="product_nome" name="product_nome" required>
                    </div>
                    <div class="form-group">
                        <label for="product_preco">Preço (Ex: 8.00):</label>
                        <input type="number" id="product_preco" name="product_preco" step="0.01" min="0" required placeholder="0.00">
                    </div>
                    <div class="form-group">
                        <label for="product_descricao">Descrição (Ex: Long Neck 330ml - Opcional):</label>
                        <textarea id="product_descricao" name="product_descricao" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <button type="submit">Adicionar Produto</button>
                    </div>
                </form>
            </section>

            <section>
                <h3>Produtos Cadastrados</h3>
                <div style="overflow-x:auto;">
                <?php if (!empty($products)): ?>
                    <table class="admin-table" style="min-width:600px;">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Preço (R$)</th>
                                <th>Descrição</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($product['nome']); ?></td>
                                    <td><?php echo htmlspecialchars(number_format($product['preco'], 2, ',', '.')); ?></td>
                                    <td><?php echo nl2br(htmlspecialchars(substr($product['descricao'] ?? '', 0, 100))) . (strlen($product['descricao'] ?? '') > 100 ? '...' : ''); ?></td>
                                    <td><?php echo $product['ativo'] ? 'Ativo' : 'Inativo'; ?></td>
                                    <td>
                                        <a href="edit_product.php?id=<?php echo $product['produto_id']; ?>" class="action-link">Editar</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>Nenhum produto cadastrado ainda.</p>
                <?php endif; ?>
                </div>
                <style>
                @media (max-width: 800px) {
                    .admin-table, .admin-table thead, .admin-table tbody, .admin-table th, .admin-table td, .admin-table tr {
                        display: block;
                        width: 100%;
                    }
                    .admin-table thead tr {
                        display: none;
                    }
                    .admin-table tr {
                        margin-bottom: 1rem;
                        border-bottom: 1px solid #ccc;
                    }
                    .admin-table td {
                        position: relative;
                        padding-left: 50%;
                        min-height: 40px;
                        border: none;
                        border-bottom: 1px solid #eee;
                    }
                    .admin-table td:before {
                        position: absolute;
                        top: 0;
                        left: 0;
                        width: 45%;
                        padding-left: 10px;
                        white-space: nowrap;
                        font-weight: bold;
                        content: attr(data-label);
                        color: #333;
                    }
                }
                </style>
                <script>
                // Adiciona os data-labels para responsividade
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
            </section>
        </main>
    </div>
</body>
</html>