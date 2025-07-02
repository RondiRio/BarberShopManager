<?php
        session_start();
        $_SESSION['login_error_message'] = isset($_SESSION['login_error_message']) ? $_SESSION['login_error_message'] : '';
        // print_r($_SESSION); // Necessário para acessar $_SESSION
        if (isset($_SESSION['login_error_message'])) {
            echo '<div style="color: red; text-align: center; padding: 10px; background-color: #333; border-radius: 5px; margin-bottom: 15px;">' . htmlspecialchars($_SESSION['login_error_message']) . '</div>';
            unset($_SESSION['login_error_message']); // Limpa a mensagem após exibir
        }
    ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Barbearia JB</title>
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" integrity="sha512-papm6Qw1v6wQw6Qw1v6wQw6Qw1v6wQw6Qw1v6wQw6Qw1v6wQw6Qw1v6wQw6Qw1v6wQw6Qw1v6wQw6Qw1v6wQw==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/toogle_menu.css">
    <script src="js/toogle_menu.js"></script>
    <style>
        body {
            font-family: 'times new roman', Times, serif; /* Fonte padrão */
            font-size: 24px; /* Tamanho de fonte padrão */
            margin: 0;
            padding: 0;
            background-color: #121212;
            color: #FFFFFF;
            line-height: 1.6;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .container {
            width: 80%;
            margin: 0 auto; /* Alterado para margin: 0 auto para centralizar se não estiver no flex container principal */
            overflow: hidden;
            padding: 20px;
        }

        header {
            background: #000000;
            color: #FFFFFF;
            padding-top: 30px;
            min-height: 70px;
            border-bottom: #FFFFFF 3px solid;
            width: 100%;
        }

        header a {
            color: #FFFFFF;
            text-decoration: none;
            text-transform: uppercase;
            font-size: 16px;
        }

        header ul {
            padding: 0;
            margin: 0;
            list-style: none;
            float: right;
        }

        header li {
            display: inline;
            padding: 0 20px 0 20px;
        }

        header #branding {
            float: left;
        }

        header #branding h1 {
            margin: 0;
            font-size: 28px;
        }

        header nav ul li.current a {
            color: #CCCCCC;
            font-weight: bold;
        }
         header nav ul li a:hover {
            color: #CCCCCC;
            font-weight: bold;
        }

        /* Centraliza o conteúdo da página de login */
        .login-main-content {
            flex-grow: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px 0; /* Adiciona padding vertical */
        }

        .login-box {
            background-color: #1C1C1C;
            padding: 30px 40px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(255,255,255,0.1);
            width: 100%;
            max-width: 400px; /* Define uma largura máxima para o box de login */
            text-align: center;
        }

        .login-box h1 {
            color: #FFFFFF;
            margin-bottom: 25px;
            font-size: 28px;
            border-bottom: 1px solid #333;
            padding-bottom: 15px;
        }

        .login-box label {
            display: block;
            text-align: left;
            margin-bottom: 8px;
            color: #E0E0E0;
            font-weight: bold;
            font-size: 14px;
        }

        .login-box input[type="email"],
        .login-box input[type="password"] {
            width: calc(100% - 22px);
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 4px;
            border: 1px solid #333333;
            background-color: #282828;
            color: #FFFFFF;
            font-size: 16px;
        }

        .login-box input[type="email"]:focus,
        .login-box input[type="password"]:focus {
            outline: none;
            border-color: #FFFFFF;
            box-shadow: 0 0 5px rgba(255,255,255,0.3);
        }

        .login-box button[type="submit"] {
            width: 100%;
            padding: 12px 20px;
            background-color: #FFFFFF;
            color: #000000;
            border: none;
            border-radius: 4px;
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
            cursor: pointer;
            transition: background-color 0.3s ease, color 0.3s ease;
            margin-top: 10px;
        }

        .login-box button[type="submit"]:hover {
            background-color: #CCCCCC;
            color: #000000;
        }

        .login-links {
            margin-top: 25px;
            font-size: 14px;
        }

        .login-links p {
            margin-bottom: 10px;
        }

        .login-links a {
            color: #CCCCCC;
            text-decoration: none;
        }

        .login-links a:hover {
            color: #FFFFFF;
            text-decoration: underline;
        }

        footer {
            padding: 20px;
            /* margin-top: auto; */ /* Empurra o rodapé para baixo quando o conteúdo é pouco */
            color: #FFFFFF;
            background-color: #000000;
            text-align: center;
            border-top: #FFFFFF 3px solid;
            width: 100%;
        }

        nav ul {
            list-style-type: none;
            padding: 0;
            text-align: center;
        }
        nav ul li {
            display: inline;
            margin-right: 20px;
        }
        nav ul li a {
            color: #fff;
            text-decoration: none;
        }

        /* Responsividade */
        @media(max-width: 768px){
            header #branding,
            header nav,
            header nav li {
                float: none;
                text-align: center;
                width: 100%;
            }

            header nav li {
                padding: 10px 0;
            }

            .container { /* Ajuste para container geral se necessário */
                width: 90%;
            }

            .login-box {
                padding: 20px;
                margin: 20px; /* Adiciona margem em telas menores */
            }
            .login-box h1 {
                font-size: 24px;
            }

        }
    </style>
</head>
<body>
    <?php include('routes/header.php')?>
    <!-- <header>
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
                    <li class="current"><a href="login.php">Login</a></li>
                </ul>
            </nav>
        </div>
    </header> -->

    <main class="login-main-content">
        <div class="login-box">
            <h1>Acessar Conta</h1>
            <form id="loginForm" action="config/valida_login.php" method="POST">
                <div>
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" placeholder="seuemail@exemplo.com" required>
                </div>
                <div>
                    <label for="password">Senha:</label>
                    <input type="password" id="password" name="password" placeholder="Sua senha" required>
                </div>
                <button type="submit">Entrar</button>
            </form>
            <div class="login-links">
                <p><a href="forgot_password.php">Esqueci minha senha</a></p>
                <p>Não tem uma conta? <a href="register.php">Cadastre-se</a></p>
            </div>
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