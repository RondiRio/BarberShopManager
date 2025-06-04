<?php
require_once 'config/database.php'; // Garanta que este caminho está correto

// Buscar serviços ATIVOS do banco de dados
$db_produtos = [];
// Adapte os nomes da tabela e colunas se forem diferentes no seu banco de dados
// Ex: se sua tabela for 'Services' e colunas 'name', 'price', 'description', 'is_active'
$sql = "SELECT nome, preco, descricao 
        FROM produtos  -- Ou o nome da sua tabela de serviços
        WHERE ativo = 1   -- Ou a condição para serviço ativo (ex: is_active = TRUE)
        ORDER BY nome ASC"; // Ou outra ordenação de sua preferência

if ($result = $mysqli->query($sql)) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Formatando o preço para o padrão brasileiro aqui, antes de passar para o HTML
            // O banco deve armazenar o preço como DECIMAL (ex: 45.00)
            $row['preco_formatado'] = number_format($row['preco'], 2, ',', '.');
            $db_produtos[] = $row;
        }
    }
    $result->free();
} else {
    // Para depuração, se a query falhar:
    // echo "Erro ao buscar serviços: " . $mysqli->error;
    // Você pode querer definir uma mensagem de erro amigável aqui ou logar o erro.
}
// $mysqli->close(); // Feche a conexão se não for mais usada nesta página (geralmente no final do script)
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu de Bebidas - Barbearia JB</title>
    <style>
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

        /* Destaca o link da página atual no menu */
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

        #products-list {
            padding: 30px 0;
        }

        .product-category { /* Renomeado de service-category para product-category para clareza */
            margin-bottom: 40px;
        }

        .product-category h2 {
            color: #FFFFFF;
            border-bottom: 2px solid #FFFFFF;
            padding-bottom: 10px;
            margin-bottom: 20px;
            font-size: 28px;
            text-align: center;
        }

        .product-item { /* Renomeado de service-item para product-item */
            background-color: #1C1C1C;
            margin-bottom: 20px;
            padding: 20px;
            border-radius: 5px;
            border-left: 5px solid #FFFFFF;
        }

        .product-item:hover {
            border-left: 5px solid #CCCCCC;
        }

        .product-item h3 {
            margin-top: 0;
            margin-bottom: 5px;
            color: #FFFFFF;
            font-size: 22px;
        }

        .product-item .price {
            font-size: 20px;
            font-weight: bold;
            color: #CCCCCC;
            margin-bottom: 10px;
        }

        .product-item .description {
            font-size: 16px;
            color: #E0E0E0;
        }

        footer {
            padding: 20px;
            margin-top: 20px;
            color: #FFFFFF;
            background-color: #000000;
            text-align: center;
            border-top: #FFFFFF 3px solid;
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

            .container {
                width: 95%;
            }

            #page-title-section h1 {
                font-size: 28px;
            }
            .product-category h2 {
                font-size: 24px;
            }
            .product-item h3 {
                font-size: 20px;
            }
            .product-item .price {
                font-size: 18px;
            }
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
                    <li class="current"><a href="produtos.php">Bebidas</a></li>
                    <li><a href="recomendacoes.php">Recomendações</a></li>
                    <li><a href="login.php">Login</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <section id="page-title-section">
        <div class="container">
            <h1>Nosso Menu de Bebidas</h1>
        </div>
    </section>

    <div class="container" id="products-list">
        <section class="product-category">
            <h2>Para Refrescar e Celebrar</h2>
            <?php foreach ($db_produtos as $db_produto): ?>
                <article class="product-item">
                    <h3><?php echo htmlspecialchars($db_produto['nome']); ?></h3>
                    <p class="price">R$ <?php echo htmlspecialchars($db_produto['preco_formatado']); ?></p>
                    <p class="description"><?php echo htmlspecialchars($db_produto['descricao']); ?></p>
                </article>
            <?php endforeach; ?>
        </section>

        </div>

    <footer>
        <p>Barbearia JB &copy; <?php echo date("Y"); ?> - Todos os direitos reservados.</p>
        <p>"Sempre o melhor para você."</p>
    </footer>

</body>
</html>