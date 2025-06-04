<?php
session_start(); // Pode ser útil para mensagens de feedback do formulário de submissão no futuro
require_once 'config/database.php'; // Garanta que este caminho está correto

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
    <style>
        /* SEU CSS EXISTENTE - NENHUMA MUDANÇA NECESSÁRIA AQUI PELO PHP */
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #121212;
            color: #FFFFFF;
            line-height: 1.6;
        }

        .container {
            width: 80%;
            margin: auto;
            overflow: hidden;
            padding: 20px;
        }

        header {
            background: #000000;
            color: #FFFFFF;
            padding-top: 30px;
            min-height: 70px;
            border-bottom: #FFFFFF 3px solid;
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

        #page-title-section {
            padding: 40px 0;
            background-color: #1C1C1C;
            text-align: center;
            border-bottom: #000000 2px solid;
        }

        #page-title-section h1 {
            font-size: 36px;
            margin: 0;
            color: #FFFFFF;
            text-shadow: 1px 1px 2px #000000;
        }

        .main-content-section {
            padding: 30px 0;
        }

        .section-title {
            color: #FFFFFF;
            border-bottom: 2px solid #FFFFFF;
            padding-bottom: 10px;
            margin-bottom: 30px;
            font-size: 28px;
            text-align: center;
        }

        #recommendations-display {
            margin-bottom: 40px;
        }

        .recommendation-item {
            background-color: #1C1C1C;
            margin-bottom: 20px;
            padding: 20px;
            border-radius: 5px;
            border-left: 5px solid #FFFFFF;
        }

        .recommendation-item blockquote {
            margin: 0 0 10px 0;
            font-style: italic;
            font-size: 1.1em;
            color: #E0E0E0;
        }
        .recommendation-item .barber-name { /* Novo estilo para nome do barbeiro */
            font-size: 0.9em;
            color: #f39c12; /* Cor de destaque para o barbeiro */
            margin-bottom: 5px;
            font-weight: bold;
        }

        .recommendation-item .author-date {
            text-align: right;
            font-size: 0.9em;
            color: #B0B0B0;
        }
        .recommendation-item .author-date strong {
            color: #CCCCCC;
        }

        #recommendation-form-section {
            background-color: #1C1C1C;
            padding: 30px;
            border-radius: 5px;
        }

        #recommendation-form label {
            display: block;
            margin-bottom: 8px;
            color: #E0E0E0;
            font-weight: bold;
        }
        /* Adicionar select para barbeiro no formulário público, se desejar */
        #recommendation-form select,
        #recommendation-form input[type="text"],
        #recommendation-form textarea {
            width: calc(100% - 22px); 
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
            border: 1px solid #333333;
            background-color: #282828;
            color: #FFFFFF;
            font-size: 16px;
        }

        #recommendation-form textarea {
            resize: vertical;
            min-height: 100px;
        }

        #recommendation-form input[type="text"]:focus,
        #recommendation-form select:focus,
        #recommendation-form textarea:focus {
            outline: none;
            border-color: #FFFFFF;
            box-shadow: 0 0 5px rgba(255,255,255,0.3);
        }

        #recommendation-form button[type="submit"] {
            display: block; width: auto; padding: 12px 25px;
            background-color: #FFFFFF; color: #000000; border: none;
            border-radius: 4px; font-size: 16px; font-weight: bold;
            text-transform: uppercase; cursor: pointer;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        #recommendation-form button[type="submit"]:hover {
            background-color: #CCCCCC;
            color: #000000;
        }

        footer {
            padding: 20px; margin-top: 20px; color: #FFFFFF;
            background-color: #000000; text-align: center; border-top: #FFFFFF 3px solid;
        }
        nav ul { list-style-type: none; padding: 0; text-align: center; }
        nav ul li { display: inline; margin-right: 20px; }
        nav ul li a { color: #fff; text-decoration: none; }

        @media(max-width: 768px){
            header #branding, header nav, header nav li { float: none; text-align: center; width: 100%; }
            header nav li { padding: 10px 0; }
            .container { width: 95%; }
            #page-title-section h1 { font-size: 28px; }
            .section-title { font-size: 24px; }
            #recommendation-form input[type="text"],
            #recommendation-form select,
            #recommendation-form textarea,
            #recommendation-form button[type="submit"] { width: 100%; box-sizing: border-box; }
            #recommendation-form button[type="submit"] { text-align: center; }
        }
        .message { padding: 10px; margin-bottom: 15px; border-radius:4px; text-align:center;}
        .message.success { background-color: #27ae60; color:white; }
        .message.error { background-color: #c0392b; color:white; }
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
                    <li class="current"><a href="recomendacoes.php">Recomendações</a></li>
                    <li><a href="login.php">Login</a></li>
                </ul>
            </nav>
        </div>
    </header>

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
        <p>Barbearia JB &copy; <?php echo date("Y"); ?> - Todos os direitos reservados.</p>
        <p>"Sempre o melhor para você."</p>
    </footer>
<?php $mysqli->close(); // Fechar a conexão com o banco de dados no final da página ?>
</body>
</html>