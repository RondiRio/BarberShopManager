<?php
// register.php
session_start(); // Para exibir mensagens de erro/sucesso
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastre-se - Barbearia JB</title>
    <style>
        /* Reutilizando e adaptando estilos do login.php para consistência */
        body {
            font-family: 'Arial', sans-serif; margin: 0; padding: 0;
            background-color: #121212; color: #FFFFFF; line-height: 1.6;
            display: flex; flex-direction: column; min-height: 100vh;
        }
        .container { width: 80%; margin: 0 auto; overflow: hidden; padding: 20px; }
        header {
            background: #000000; color: #FFFFFF; padding-top: 30px; min-height: 70px;
            border-bottom: #FFFFFF 3px solid; width: 100%;
        }
        header a { color: #FFFFFF; text-decoration: none; text-transform: uppercase; font-size: 16px; }
        header ul { padding: 0; margin: 0; list-style: none; float: right; }
        header li { display: inline; padding: 0 20px 0 20px; }
        header #branding { float: left; }
        header #branding h1 { margin: 0; font-size: 28px; }
        header nav ul li a:hover, header nav ul li.current a { color: #CCCCCC; font-weight: bold; }

        .main-content-area { /* Similar ao login-main-content */
            flex-grow: 1; display: flex; align-items: center; justify-content: center; padding: 20px 0;
        }
        .form-box { /* Similar ao login-box */
            background-color: #1C1C1C; padding: 30px 40px; border-radius: 8px;
            box-shadow: 0 0 15px rgba(255,255,255,0.1); width: 100%; max-width: 450px; text-align: center;
        }
        .form-box h1 {
            color: #FFFFFF; margin-bottom: 25px; font-size: 28px;
            border-bottom: 1px solid #333; padding-bottom: 15px;
        }
        .message {
            padding: 10px; margin-bottom: 15px; border-radius:4px; text-align:left; font-size: 0.9em;
        }
        .message.success { background-color: #27ae60; color:white; border: 1px solid #2ecc71;}
        .message.error { background-color: #c0392b; color:white; border: 1px solid #e74c3c; }

        .form-box label {
            display: block; text-align: left; margin-bottom: 8px;
            color: #E0E0E0; font-weight: bold; font-size: 14px;
        }
        .form-box input[type="text"],
        .form-box input[type="email"],
        .form-box input[type="password"] {
            width: calc(100% - 22px); padding: 12px; margin-bottom: 20px;
            border-radius: 4px; border: 1px solid #333333; background-color: #282828;
            color: #FFFFFF; font-size: 16px;
        }
        .form-box input[type="text"]:focus,
        .form-box input[type="email"]:focus,
        .form-box input[type="password"]:focus {
            outline: none; border-color: #FFFFFF; box-shadow: 0 0 5px rgba(255,255,255,0.3);
        }
        .form-box button[type="submit"] {
            width: 100%; padding: 12px 20px; background-color: #FFFFFF; color: #000000;
            border: none; border-radius: 4px; font-size: 18px; font-weight: bold;
            text-transform: uppercase; cursor: pointer; transition: background-color 0.3s ease, color 0.3s ease;
            margin-top: 10px;
        }
        .form-box button[type="submit"]:hover { background-color: #CCCCCC; color: #000000; }
        .form-links { margin-top: 25px; font-size: 14px; }
        .form-links p { margin-bottom: 10px; }
        .form-links a { color: #CCCCCC; text-decoration: none; }
        .form-links a:hover { color: #FFFFFF; text-decoration: underline; }

        footer {
            padding: 20px; color: #FFFFFF; background-color: #000000;
            text-align: center; border-top: #FFFFFF 3px solid; width: 100%;
        }
        nav ul { list-style-type: none; padding: 0; text-align: center;}
        nav ul li {display: inline; margin-right: 20px;}
        nav ul li a {color: #fff; text-decoration: none;}
         @media(max-width: 768px){
            header #branding, header nav, header nav li { float: none; text-align: center; width: 100%; }
            header nav li { padding: 10px 0; }
            .container { width: 90%; }
            .form-box { padding: 20px; margin: 20px; }
            .form-box h1 { font-size: 24px; }
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <div id="branding">
                <h1><span class="highlight">Barbearia</span> JB</h1>
            </div>
            <nav>
                <ul>
                    <li><a href="index.php">Início</a></li>
                    <li><a href="servicos.php">Serviços</a></li>
                    <li><a href="produtos.php">Bebidas</a></li>
                    <li><a href="recomendacoes.php">Recomendações</a></li>
                    <li><a href="login.php">Login</a></li>
                    </ul>
            </nav>
        </div>
    </header>

    <main class="main-content-area">
        <div class="form-box">
            <h1>Criar Nova Conta</h1>

            <?php
            if (isset($_SESSION['register_message'])) {
                echo '<div class="message ' . htmlspecialchars($_SESSION['register_message_type']) . '">' . nl2br(htmlspecialchars($_SESSION['register_message'])) . '</div>';
                unset($_SESSION['register_message']);
                unset($_SESSION['register_message_type']);
            }
            ?>

            <form action="handle_register.php" method="POST">
                <div class="form-group">
                    <label for="name">Nome Completo:</label>
                    <input type="text" id="name" name="name" value="<?php echo isset($_SESSION['form_data']['name']) ? htmlspecialchars($_SESSION['form_data']['name']) : ''; ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" value="<?php echo isset($_SESSION['form_data']['email']) ? htmlspecialchars($_SESSION['form_data']['email']) : ''; ?>" required>
                </div>
                <div class="form-group">
                    <label for="password">Senha (mínimo 6 caracteres):</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirmar Senha:</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                <div class="form-group">
                    <button type="submit">Criar Conta</button>
                </div>
            </form>
            <div class="form-links">
                <p>Já tem uma conta? <a href="login.php">Faça login aqui</a></p>
            </div>
        </div>
    </main>
    <?php unset($_SESSION['form_data']); // Limpa os dados do formulário da sessão ?>

    <footer>
        <p>Barbearia JB &copy; <?php echo date("Y"); ?> - Todos os direitos reservados.</p>
        <p>"Sempre o melhor para você."</p>
    </footer>
</body>
</html>