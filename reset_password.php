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
    <style>
        /* Coloque aqui os estilos do form-box e message da sua login.php se não tiver um CSS central */
        body { font-family: 'Arial', sans-serif; margin: 0; padding: 0; background-color: #121212; color: #FFFFFF; line-height: 1.6; display: flex; flex-direction: column; min-height: 100vh; }
        .container { width: 80%; margin: 0 auto; overflow: hidden; padding: 20px; }
        /* ... (copie o header e footer e .form-box e .message da sua login.php ou register.php) ... */
        .main-content-area { flex-grow: 1; display: flex; align-items: center; justify-content: center; padding: 20px 0; }
        .form-box { background-color: #1C1C1C; padding: 30px 40px; border-radius: 8px; box-shadow: 0 0 15px rgba(255,255,255,0.1); width: 100%; max-width: 450px; text-align: center; }
        .form-box h1 { color: #FFFFFF; margin-bottom: 25px; font-size: 28px; border-bottom: 1px solid #333; padding-bottom: 15px; }
        .message { padding: 10px; margin-bottom: 15px; border-radius:4px; text-align:left; font-size: 0.9em; }
        .message.success { background-color: #27ae60; color:white; border: 1px solid #2ecc71;}
        .message.error { background-color: #c0392b; color:white; border: 1px solid #e74c3c; }
        .form-box label { display: block; text-align: left; margin-bottom: 8px; color: #E0E0E0; font-weight: bold; font-size: 14px; }
        .form-box input[type="password"] { width: calc(100% - 22px); padding: 12px; margin-bottom: 20px; border-radius: 4px; border: 1px solid #333333; background-color: #282828; color: #FFFFFF; font-size: 16px; }
        .form-box button[type="submit"] { width: 100%; padding: 12px 20px; background-color: #FFFFFF; color: #000000; border: none; border-radius: 4px; font-size: 18px; font-weight: bold; text-transform: uppercase; cursor: pointer; margin-top: 10px; }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <div id="branding"><h1><span class="highlight">Barbearia</span> JB</h1></div>
             <nav><ul><li><a href="index.php">Início</a></li><li><a href="login.php">Login</a></li></ul></nav>
        </div>
    </header>
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
    <footer><p>Barbearia JB &copy; <?php echo date("Y"); ?> - Todos os direitos reservados.</p></footer>
</body>
</html>