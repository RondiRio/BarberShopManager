<?php
session_start();
require_once '../config/database.php'; // Ajuste o caminho se necessário

// Verifica se o usuário está logado e se é admin
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'admin') {
    header("location: ../login.php");
    exit;
}

$current_page = 'dashboard.php'; // Para manter "Gerenciar Barbeiros" ativo no menu

$barber_id = null;
$barber_name = '';
$barber_email = '';
$barber_commission_rate = '';
$barber_is_active = 1; // Default

// Verifica se o ID foi passado e é numérico
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $barber_id = (int)$_GET['id'];

    // Busca os dados do barbeiro
    $sql = "SELECT name, email, commission_rate, is_active FROM Users WHERE user_id = ? AND role = 'barber'";
    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param("i", $barber_id);
        if ($stmt->execute()) {
            $stmt->store_result();
            if ($stmt->num_rows == 1) {
                $stmt->bind_result($barber_name, $barber_email, $barber_commission_rate, $barber_is_active);
                $stmt->fetch();
            } else {
                $_SESSION['message'] = "Barbeiro não encontrado.";
                $_SESSION['message_type'] = "error";
                header("location: dashboard.php");
                exit;
            }
        } else {
            $_SESSION['message'] = "Erro ao buscar dados do barbeiro: " . $stmt->error;
            $_SESSION['message_type'] = "error";
            header("location: dashboard.php");
            exit;
        }
        $stmt->close();
    } else {
        $_SESSION['message'] = "Erro ao preparar consulta: " . $mysqli->error;
        $_SESSION['message_type'] = "error";
        header("location: dashboard.php");
        exit;
    }
} else {
    $_SESSION['message'] = "ID do barbeiro inválido ou não fornecido.";
    $_SESSION['message_type'] = "error";
    header("location: dashboard.php");
    exit;
}
// $mysqli->close(); // Conexão será usada no handler ou fechada lá
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Barbeiro - Painel Admin</title>
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
            <h2>Editar Barbeiro: <?php echo htmlspecialchars($barber_name); ?></h2>

            <?php
            // Mensagens de erro do handle_update_barber.php podem ser exibidas aqui
            if (isset($_SESSION['message_form_edit'])) { // Usar uma chave de sessão diferente para não conflitar
                echo '<div class="message ' . htmlspecialchars($_SESSION['message_form_edit_type']) . '">' . htmlspecialchars($_SESSION['message_form_edit']) . '</div>';
                unset($_SESSION['message_form_edit']);
                unset($_SESSION['message_form_edit_type']);
            }
            ?>

            <section class="form-section">
                <form action="handle_update_barber.php" method="POST">
                    <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($barber_id); ?>">

                    <div class="form-group">
                        <label for="name">Nome Completo:</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($barber_name); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($barber_email); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="commission_rate">Taxa de Comissão (Ex: 0.5 para 50%):</label>
                        <input type="number" id="commission_rate" name="commission_rate" value="<?php echo htmlspecialchars($barber_commission_rate); ?>" step="0.01" min="0" max="1" required>
                    </div>
                    <div class="form-group">
                        <label for="is_active">Status:</label>
                        <select id="is_active" name="is_active">
                            <option value="1" <?php echo ($barber_is_active == 1) ? 'selected' : ''; ?>>Ativo</option>
                            <option value="0" <?php echo ($barber_is_active == 0) ? 'selected' : ''; ?>>Inativo</option>
                        </select>
                    </div>
                    <hr style="border-color: #4a627a; margin: 20px 0;">
                    <p style="color: #ecf0f1; font-size:0.9em;">Deixe os campos de senha em branco para não alterar a senha atual.</p>
                    <div class="form-group">
                        <label for="password">Nova Senha (mínimo 6 caracteres):</label>
                        <input type="password" id="password" name="password">
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirmar Nova Senha:</label>
                        <input type="password" id="confirm_password" name="confirm_password">
                    </div>
                    <div class="form-group">
                        <button type="submit">Atualizar Barbeiro</button>
                        <a href="dashboard.php" style="margin-left: 15px; color: #ecf0f1; text-decoration: underline;">Cancelar</a>
                    </div>
                </form>
            </section>
        </main>
    </div>
</body>
</html>