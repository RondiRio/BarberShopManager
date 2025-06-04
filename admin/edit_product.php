<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'admin') {
    header("location: ../login.php");
    exit;
}

$current_page = 'manage_products.php';

$produto_id = null;
$produto_nome = '';
$produto_preco = '';
$produto_descricao = '';
$produto_ativo = 1;

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $produto_id = (int)$_GET['id'];

    $sql = "SELECT nome, preco, descricao, ativo FROM produtos WHERE produto_id = ?";
    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param("i", $produto_id);
        if ($stmt->execute()) {
            $stmt->store_result();
            if ($stmt->num_rows == 1) {
                $stmt->bind_result($produto_nome, $produto_preco, $produto_descricao, $produto_ativo);
                $stmt->fetch();
            } else {
                $_SESSION['message'] = "Produto não encontrado.";
                $_SESSION['message_type'] = "error";
                header("location: manage_products.php");
                exit;
            }
        } else {
            $_SESSION['message'] = "Erro ao buscar produto: " . $stmt->error;
            $_SESSION['message_type'] = "error";
            header("location: manage_products.php");
            exit;
        }
        $stmt->close();
    } else {
        $_SESSION['message'] = "Erro ao preparar consulta: " . $mysqli->error;
        $_SESSION['message_type'] = "error";
        header("location: manage_products.php");
        exit;
    }
} else {
    $_SESSION['message'] = "ID do produto inválido ou não fornecido.";
    $_SESSION['message_type'] = "error";
    header("location: manage_products.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Produto - Painel Admin</title>
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
            <h2>Editar Produto</h2>

            <?php
            if (isset($_SESSION['message_form'])) { // Usar uma chave de sessão diferente para erros de formulário de edição
                echo '<div class="message ' . htmlspecialchars($_SESSION['message_form_type']) . '">' . htmlspecialchars($_SESSION['message_form']) . '</div>';
                unset($_SESSION['message_form']);
                unset($_SESSION['message_form_type']);
            }
            ?>

            <section class="form-section">
                <form action="handle_update_product.php" method="POST">
                    <input type="hidden" name="produto_id" value="<?php echo htmlspecialchars($produto_id); ?>">

                    <div class="form-group">
                        <label for="product_nome">Nome do Produto:</label>
                        <input type="text" id="product_nome" name="product_nome" value="<?php echo htmlspecialchars($produto_nome); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="product_preco">Preço (R$):</label>
                        <input type="number" id="product_preco" name="product_preco" value="<?php echo htmlspecialchars($produto_preco); ?>" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="product_descricao">Descrição:</label>
                        <textarea id="product_descricao" name="product_descricao" rows="4"><?php echo htmlspecialchars($produto_descricao ?? ''); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="product_ativo">Status:</label>
                        <select id="product_ativo" name="product_ativo">
                            <option value="1" <?php echo ($produto_ativo == 1) ? 'selected' : ''; ?>>Ativo</option>
                            <option value="0" <?php echo ($produto_ativo == 0) ? 'selected' : ''; ?>>Inativo</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <button type="submit">Atualizar Produto</button>
                        <a href="manage_products.php" style="margin-left: 15px; color: #ecf0f1; text-decoration: underline;">Cancelar</a>
                    </div>
                </form>
            </section>
        </main>
    </div>
</body>
</html>