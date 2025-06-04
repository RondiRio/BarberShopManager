<?php
session_start();
require_once 'config/database.php';

$token = isset($_GET['token']) ? trim($_GET['token']) : '';
$valid_token = false;
$user_id_for_reset = null;

if (!empty($token)) {
    $sql = "SELECT user_id FROM Users WHERE reset_token = ? AND reset_token_expiry > NOW() LIMIT 1";
    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows == 1) {
            $stmt->bind_result($user_id_for_reset);
            $stmt->fetch();
            $valid_token = true;
        }
        $stmt->close();
    }
}

if (!$valid_token) {
    $_SESSION['login_error_message'] = "Token de redefinição de senha inválido ou expirado. Por favor, solicite um novo link.";
    header("location: login.php"); // Ou forgot_password.php
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Redefinir Senha - Barbearia JB</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/toogle_menu.css">
    <script src="js/toogle_menu.js"></script>
    <style>
       
        
    </style>
</head>
<body>
    <?php include('routes/header.php')?>
    <!-- <header>
        <div class="container">
            <div id="branding"><h1><span class="highlight">Barbearia</span> JB</h1></div>
             <nav><ul><li><a href="index.php">Início</a></li><li><a href="login.php">Login</a></li></ul></nav>
        </div>
    </header> -->
    <main class="main-content-area">
        <div class="form-box">
            <h1>Definir Nova Senha</h1>
            <?php
            if (isset($_SESSION['reset_message'])) {
                echo '<div class="message ' . htmlspecialchars($_SESSION['reset_message_type']) . '">' . nl2br(htmlspecialchars($_SESSION['reset_message'])) . '</div>';
                unset($_SESSION['reset_message']);
                unset($_SESSION['reset_message_type']);
            }
            ?>
            <form action="handle_reset_password.php" method="POST">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                <input type="hidden" name="user_id_for_reset" value="<?php echo htmlspecialchars($user_id_for_reset); ?>">
                
                <div class="form-group">
                    <label for="new_password">Nova Senha (mínimo 6 caracteres):</label>
                    <input type="password" id="new_password" name="new_password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_new_password">Confirmar Nova Senha:</label>
                    <input type="password" id="confirm_new_password" name="confirm_new_password" required>
                </div>
                <div class="form-group">
                    <button type="submit">Redefinir Senha</button>
                </div>
            </form>
        </div>
    </main>
    <footer>
    <div class="footer-container">
        <div class="footer-section about-barbershop">
            <h4>Barbearia JB</h4>
            <p>"Sempre o melhor para você."</p>
            <div class="social-links">
                <!-- Certifique-se de que o Font Awesome está carregado no <head> para os ícones aparecerem corretamente -->
                <a href="https://www.facebook.com/barbeariajbregadas" target="_blank" title="Facebook Barbearia JB">
                    <i class="fab fa-facebook-f"><img src="imagens/logoface.png" alt="" width="50" height="50"></i>
                </a>
                <a href="SEU_LINK_INSTAGRAM_BARBEARIA_AQUI" target="_blank" title="Instagram Barbearia JB">
                    <i class="fab fa-instagram"><img src="imagens/logoinsta.png" alt="" width="50" height="50"></i>
                </a>
                <a href="https://wa.me/+5521995390705" target="_blank" title="WhatsApp Barbearia JB">
                    <i class="fab fa-whatsapp"><img src="imagens/logowhatsap.png" alt="" width="50" height="50"></i>
                </a>
            </div>
        </div>

        <div class="footer-section dev-info">
            <h4>Desenvolvimento</h4>
            <p>Criado e desenvolvido pela <a href="SEU_LINK_SITE_NETONERD_AQUI" target="_blank" style="color: #f39c12; text-decoration:none;">NetoNerd</a></p>
            <div class="social-links">
                <!-- Certifique-se de que o Font Awesome está carregado no <head> para os ícones aparecerem corretamente -->
                <a href="https://www.facebook.com/profile.php?id=61557364371339" target="_blank" title="Facebook NetoNerd">
                    <i class="fab fa-facebook-f"><img src="imagens/logoface.png" alt="" width="50" height="50"></i>
                </a>
                <a href="https://www.instagram.com/netonerdoficial/" target="_blank" title="Instagram  NetoNerd">
                    <i class="fab fa-instagram"><img src="imagens/logoinsta.png" alt="" width="50" height="50"></i>
                </a>
                <a href="https://wa.me/+5521977395867" target="_blank" title="WhatsApp  NetoNerd">
                    <i class="fab fa-whatsapp"><img src="imagens/logowhatsap.png" alt="" width="50" height="50"></i>
                </a>
            </div>
        </div>

        <div class="footer-section quick-links">
            <h4>Links Úteis</h4>
            <ul>
                <li><a href="index.php">Início</a></li>
                <li><a href="servicos.php">Serviços</a></li>
                <li><a href="produtos.php">Bebidas</a></li>
                <li><a href="recomendacoes.php">Recomendações</a></li>
                <li><a href="login.php">Login / Cadastro</a></li>
            </ul>
        </div>
    </div>
    <div class="footer-bottom">
        <p>&copy; <?php echo date("Y"); ?> Barbearia JB &amp; NetoNerd. Todos os direitos reservados.</p>
    </div>
</footer>
</body>
</html>