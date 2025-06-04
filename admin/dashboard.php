<?php
$current_page = basename($_SERVER['PHP_SELF']);
session_start();
require_once '../config/database.php'; // Ajuste o caminho se necessário

// Verifica se o usuário está logado e se é admin
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'admin') {
    header("location: ../login.php");
    exit;
}

// Lógica para buscar barbeiros existentes
$barbers = [];
$sql_barbers = "SELECT user_id, name, email, commission_rate, is_active FROM Users WHERE role = 'barber' ORDER BY name ASC";
if ($result = $mysqli->query($sql_barbers)) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $barbers[] = $row;
        }
    }
    $result->free();
} else {
    // Tratar erro na consulta, se necessário
    // echo "Erro ao buscar barbeiros: " . $mysqli->error;
}

// $mysqli->close(); // Não feche a conexão aqui se for usar em handle_add_barber.php (ou reabra lá)
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Admin - Barbearia JB</title>
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
                <li><a href="manage_products.php" class="<?php echo($current_page == 'manage_products.php') ? 'active' : '' ?>">Gerenciar Produtos</a></li>
                <li><a href="view_reports.php" class="<?php echo($current_page == 'view_reports.php') ? 'active' : '' ?>">Relatórios Semanais</a></li>
                <li><a href="manage_recommendations.php" class="<?php echo($current_page == 'manage_recommendations.php') ? 'active' : '' ?>">Moderar Recomendações</a></li>
            </ul>
        </aside>

        <main class="admin-main-content">
            <h2>Gerenciar Barbeiros</h2>

            <?php
            if (isset($_SESSION['message'])) {
                echo '<div class="message ' . htmlspecialchars($_SESSION['message_type']) . '">' . htmlspecialchars($_SESSION['message']) . '</div>';
                unset($_SESSION['message']);
                unset($_SESSION['message_type']);
            }
            ?>

            <section class="form-section">
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
            <style>
            
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
        </main>
    </div>
    </body>
</html>