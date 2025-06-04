<?php
require_once 'config/database.php'; // Garanta que este caminho está correto

// Buscar serviços ATIVOS do banco de dados
$db_servicos = [];
// Adapte os nomes da tabela e colunas se forem diferentes no seu banco de dados
// Ex: se sua tabela for 'Services' e colunas 'name', 'price', 'description', 'is_active'
$sql = "SELECT nome, preco, descricao 
        FROM servicos  -- Ou o nome da sua tabela de serviços
        WHERE ativo = 1   -- Ou a condição para serviço ativo (ex: is_active = TRUE)
        ORDER BY nome ASC"; // Ou outra ordenação de sua preferência

if ($result = $mysqli->query($sql)) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Formatando o preço para o padrão brasileiro aqui, antes de passar para o HTML
            // O banco deve armazenar o preço como DECIMAL (ex: 45.00)
            $row['preco_formatado'] = number_format($row['preco'], 2, ',', '.');
            $db_servicos[] = $row;
        }
    }
    $result->free();
} else {
    // Para depuração, se a query falhar:
    // echo "Erro ao buscar serviços: " . $mysqli->error;
    // Você pode querer definir uma mensagem de erro amigável aqui ou logar o erro.
}
$mysqli->close(); // Feche a conexão se não for mais usada nesta página (geralmente no final do script)
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nossos Serviços - Barbearia JB</title>
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
                    <li class="current"><a href="servicos.php">Serviços</a></li>
                    <li><a href="produtos.php">Bebidas</a></li>
                    <li><a href="recomendacoes.php">Recomendações</a></li>
                    <li><a href="login.php">Login</a></li>
                </ul>
            </nav>
        </div>
    </header> -->

    <section id="page-title-section">
        <div class="container">
            <h1>Nossos Serviços</h1>
        </div>
    </section>

    <div class="container" id="services-list">
        <section class="service-category">
            <h2>Cortes & Cuidados Essenciais</h2>
            <?php if (!empty($db_servicos)): ?>
                <?php foreach ($db_servicos as $servico_db): ?>
                    <article class="service-item">
                        <h3><?php echo htmlspecialchars($servico_db['nome']); ?></h3>
                        <p class="price">R$ <?php echo htmlspecialchars($servico_db['preco_formatado']); // Usando o preço já formatado ?></p>
                        <p class="description"><?php echo htmlspecialchars($servico_db['descricao']); ?></p>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align:center; color: #B0B0B0;">Nenhum serviço disponível no momento. Volte em breve!</p>
            <?php endif; ?>
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
</body>
</html>