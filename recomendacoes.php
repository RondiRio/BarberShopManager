<?php
session_start(); // Pode ser útil para mensagens de feedback do formulário de submissão no futuro
require('config/database.php'); // Garanta que este caminho está correto

// Buscar recomendações APROVADAS do banco de dados
$db_recomendacoes_aprovadas = [];
// Vamos assumir que você quer exibir o nome do barbeiro se ele foi selecionado na recomendação
// Se sua tabela 'Recomendacoes' não tem 'barbeiro_id' ou você não quer exibir, simplifique a query.
// Adapte 'Recomendacoes', 'Users', 'user_id', 'name' conforme seus nomes de tabela/coluna.
$sql = "SELECT r.nome_cliente, r.texto_recomendacao, 
               DATE_FORMAT(r.data_envio, '%d/%m/%Y') as data_formatada, 
               u.name as nome_barbeiro 
        FROM Recomendacoes r
        LEFT JOIN Users u ON r.barbeiro_id = u.user_id AND u.role = 'barber'
        WHERE r.aprovado = 1 
        ORDER BY r.data_envio DESC"; // Mais recentes primeiro

if ($result = $mysqli->query($sql)) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $db_recomendacoes_aprovadas[] = $row;
        }
    }
    $result->free();
} else {
    // Para depuração, se a query falhar:
    // echo "Erro ao buscar recomendações: " . $mysqli->error;
}
// $mysqli->close(); // Feche a conexão se não for mais usada nesta página
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recomendações - Barbearia JB</title>
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
            <div id="branding">
                <h1><span class="highlight">Barbearia</span> JB</h1>
            </div>
            <nav>
                <ul>
                    <li><a href="index.php">Início</a></li>
                    <li><a href="servicos.php">Serviços</a></li>
                    <li><a href="produtos.php">Bebidas</a></li>
                    <li class="current"><a href="recomendacoes.php">Recomendações</a></li>
                    <li><a href="login.php">Login</a></li>
                </ul>
            </nav>
        </div>
    </header> -->

    <section id="page-title-section">
        <div class="container">
            <h1>Recomendações dos Nossos Clientes</h1>
        </div>
    </section>

    <div class="container main-content-section">
        <section id="recommendations-display">
            <h2 class="section-title">O que dizem sobre nós</h2>
            <?php if (!empty($db_recomendacoes_aprovadas)): ?>
                <?php foreach ($db_recomendacoes_aprovadas as $rec): ?>
                    <article class="recommendation-item">
                        <?php if (!empty($rec['nome_barbeiro'])): ?>
                            <p class="barber-name">Recomendação para: <?php echo htmlspecialchars($rec['nome_barbeiro']); ?></p>
                        <?php endif; ?>
                        <blockquote>"<?php echo nl2br(htmlspecialchars($rec['texto_recomendacao'])); ?>"</blockquote>
                        <p class="author-date">
                            <strong>- <?php echo htmlspecialchars($rec['nome_cliente']); ?></strong>
                            (<?php echo htmlspecialchars($rec['data_formatada']); ?>)
                        </p>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align:center; color: #B0B0B0;">Ainda não há recomendações aprovadas para exibir. Seja o primeiro a deixar a sua!</p>
            <?php endif; ?>
        </section>

        <section id="recommendation-form-section">
            <h2 class="section-title">Deixe Sua Recomendação</h2>
            <?php
            // Exibir mensagens de feedback do formulário de submissão, se houver
            if (isset($_SESSION['public_rec_message'])) {
                echo '<div class="message ' . htmlspecialchars($_SESSION['public_rec_message_type']) . '">' . htmlspecialchars($_SESSION['public_rec_message']) . '</div>';
                unset($_SESSION['public_rec_message']);
                unset($_SESSION['public_rec_message_type']);
            }
            ?>
            <form id="recommendation-form" action="submit_recommendation.php" method="POST">
                <div class="form-group">
                    <label for="customer_name">Seu Nome:</label>
                    <input type="text" id="customer_name" name="nome_cliente" required>
                </div>
                <div class="form-group">
                    <label for="recommendation_text">Sua Recomendação:</label>
                    <textarea id="recommendation_text" name="texto_recomendacao" rows="6" required></textarea>
                </div>
                <p style="font-size: 0.9em; color: #B0B0B0; margin-bottom: 15px;">
                    Sua recomendação será enviada para análise antes de ser publicada. Agradecemos sua contribuição!
                </p>
                <button type="submit">Enviar Recomendação</button>
            </form>
        </section>
    </div>

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
<?php $mysqli->close(); // Fechar a conexão com o banco de dados no final da página ?>
</body>
</html>