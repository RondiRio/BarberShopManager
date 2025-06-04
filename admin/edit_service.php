<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'admin') {
    header("location: ../login.php");
    exit;
}

$current_page = 'manage_services.php'; // Para o menu lateral continuar ativo em "Gerenciar Serviços"

$service_id = null;
$service_nome = '';
$service_preco = '';
$service_descricao = '';
$service_ativo = 1; // Default para ativo

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $service_id = (int)$_GET['id'];

    $sql = "SELECT nome, preco, descricao, ativo FROM servicos WHERE service_id = ?";
    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param("i", $service_id);
        if ($stmt->execute()) {
            $stmt->store_result();
            if ($stmt->num_rows == 1) {
                $stmt->bind_result($service_nome, $service_preco, $service_descricao, $service_ativo);
                $stmt->fetch();
            } else {
                $_SESSION['message'] = "Serviço não encontrado.";
                $_SESSION['message_type'] = "error";
                header("location: manage_services.php");
                exit;
            }
        } else {
            $_SESSION['message'] = "Erro ao buscar serviço: " . $stmt->error;
            $_SESSION['message_type'] = "error";
            header("location: manage_services.php");
            exit;
        }
        $stmt->close();
    } else {
        $_SESSION['message'] = "Erro ao preparar consulta: " . $mysqli->error;
        $_SESSION['message_type'] = "error";
        header("location: manage_services.php");
        exit;
    }
} else {
    $_SESSION['message'] = "ID do serviço inválido ou não fornecido.";
    $_SESSION['message_type'] = "error";
    header("location: manage_services.php");
    exit;
}

// $mysqli->close(); // Não fechar aqui, será usado no handle_update_service.php ou reabrir lá
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Serviço - Painel Admin</title>
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
            <h2>Editar Serviço</h2>

            <?php
            if (isset($_SESSION['message'])) {
                echo '<div class="message ' . htmlspecialchars($_SESSION['message_type']) . '">' . htmlspecialchars($_SESSION['message']) . '</div>';
                unset($_SESSION['message']);
                unset($_SESSION['message_type']);
            }
            ?>

            <section class="form-section">
                <form action="handle_update_service.php" method="POST">
                    <input type="hidden" name="service_id" value="<?php echo htmlspecialchars($service_id); ?>">

                    <div class="form-group">
                        <label for="service_nome">Nome do Serviço:</label>
                        <input type="text" id="service_nome" name="service_nome" value="<?php echo htmlspecialchars($service_nome); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="service_preco">Preço (R$):</label>
                        <input type="number" id="service_preco" name="service_preco" value="<?php echo htmlspecialchars($service_preco); ?>" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="service_descricao">Descrição:</label>
                        <textarea id="service_descricao" name="service_descricao" rows="4"><?php echo htmlspecialchars($service_descricao ?? ''); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="service_ativo">Status:</label>
                        <select id="service_ativo" name="service_ativo">
                            <option value="1" <?php echo ($service_ativo == 1) ? 'selected' : ''; ?>>Ativo</option>
                            <option value="0" <?php echo ($service_ativo == 0) ? 'selected' : ''; ?>>Inativo</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <button type="submit">Atualizar Serviço</button>
                        <a href="manage_services.php" style="margin-left: 15px; color: #ecf0f1; text-decoration: underline;">Cancelar</a>
                    </div>
                </form>
            </section>
        </main>
    </div>
</body>
</html>