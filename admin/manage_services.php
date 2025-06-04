<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'admin') {
    header("location: ../login.php");
    exit;
}

$current_page = basename($_SERVER['PHP_SELF']);

// Buscar serviços existentes
$services = [];
$sql_services = "SELECT service_id, nome, preco, descricao, ativo FROM servicos ORDER BY nome ASC";
if ($result = $mysqli->query($sql_services)) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $services[] = $row;
        }
    }
    $result->free();
} else {
    $_SESSION['message'] = "Erro ao buscar serviços: " . $mysqli->error;
    $_SESSION['message_type'] = "error";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Serviços - Painel Admin</title>
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
            <h2>Gerenciar Serviços</h2>

            <?php
            if (isset($_SESSION['message'])) {
                echo '<div class="message ' . htmlspecialchars($_SESSION['message_type']) . '">' . htmlspecialchars($_SESSION['message']) . '</div>';
                unset($_SESSION['message']);
                unset($_SESSION['message_type']);
            }
            ?>

            <section class="form-section">
                <h3>Adicionar Novo Serviço</h3>
                <form action="handle_add_service.php" method="POST">
                    <div class="form-group">
                        <label for="service_name">Nome do Serviço:</label>
                        <input type="text" id="service_name" name="service_name" required>
                    </div>
                    <div class="form-group">
                        <label for="service_price">Preço (Ex: 45.00):</label>
                        <input type="number" id="service_price" name="service_price" step="0.01" min="0" required placeholder="0.00">
                    </div>
                    <div class="form-group">
                        <label for="service_description">Descrição (Opcional):</label>
                        <textarea id="service_description" name="service_description" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <button type="submit">Adicionar Serviço</button>
                    </div>
                </form>
            </section>

            <section>
    <h3>Serviços Cadastrados</h3>
    <?php if (!empty($services)): // Assumindo que a variável com os serviços é $services ?>
        <table class="admin-table">
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
                <?php foreach ($services as $service): ?>
                    <tr>
                        <td data-label="Nome"><?php echo htmlspecialchars($service['nome']); ?></td>
                        <td data-label="Preço (R$)"><?php echo htmlspecialchars(number_format($service['preco'], 2, ',', '.')); ?></td>
                        <td data-label="Descrição">
                            <?php 
                                $descricaoCompleta = $service['descricao'] ?? '';
                                echo nl2br(htmlspecialchars(substr($descricaoCompleta, 0, 100)));
                                if (strlen($descricaoCompleta) > 100) {
                                    echo '...';
                                }
                            ?>
                        </td>
                        <td data-label="Status"><?php echo ($service['ativo'] ?? 1) ? 'Ativo' : 'Inativo'; // Adicionado ?? 1 como fallback se 'ativo' não existir ?></td>
                        <td data-label="Ações">
                            <a href="edit_service.php?id=<?php echo $service['service_id']; // Ou a chave correta para o ID ?>" class="action-link">Editar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>Nenhum serviço cadastrado ainda.</p>
    <?php endif; ?>
</section>
        </main>
    </div>
</body>
</html>