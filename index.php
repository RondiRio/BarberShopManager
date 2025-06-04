<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barbearia JB - Desde 2005</title>
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

        header .highlight, header .current a {
            color: #CCCCCC; /* Cinza claro para destaque */
            font-weight: bold;
        }

        header a:hover {
            color: #CCCCCC;
            font-weight: bold;
        }

        #showcase {
            min-height: 400px;
            background: url('placeholder-barbershop.jpg') no-repeat 0 -400px; /* Substitua pela sua imagem */
            background-size: cover;
            background-position: center;
            text-align: center;
            color: #FFFFFF;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            border-bottom: #000000 3px solid;
        }

        #showcase h1 {
            font-size: 55px;
            margin-bottom: 10px;
            color: #FFFFFF;
            text-shadow: 2px 2px 4px #000000;
        }

        #showcase p {
            font-size: 20px;
            color: #FFFFFF;
            text-shadow: 1px 1px 2px #000000;
        }

        #main-content {
            padding: 30px 0;
        }

        .section {
            margin-bottom: 30px;
            padding: 20px;
            background-color: #1C1C1C; /* Cinza muito escuro para seções */
            border-radius: 5px;
        }

        .section h2 {
            color: #FFFFFF;
            border-bottom: 2px solid #FFFFFF;
            padding-bottom: 10px;
            margin-top: 0;
            text-align: center; /* Centralizar títulos das seções */
        }

        .section p {
            color: #E0E0E0; /* Cinza bem claro para parágrafos */
        }

        .history-emphasis {
            font-style: italic;
            color: #B0B0B0; /* Cinza médio para ênfase na história */
        }

        /* Estilos do Carrossel */
        .carousel-container {
            position: relative;
            max-width: 700px; /* Ajuste a largura conforme necessário */
            margin: auto;
            overflow: hidden; /* Esconde slides que não estão ativos */
            background-color: #282828; /* Fundo um pouco mais claro para o carrossel */
            padding: 20px;
            border-radius: 5px;
            min-height: 150px; /* Altura mínima para acomodar o texto */
            display: flex; /* Para centralizar verticalmente o conteúdo do slide */
            align-items: center; /* Para centralizar verticalmente o conteúdo do slide */
        }

        .carousel-slide {
            display: none; /* Esconde todos os slides por padrão */
            width: 100%;
            text-align: center;
        }

        .carousel-slide.active {
            display: block; /* Mostra apenas o slide ativo */
        }

        .carousel-slide p.recommendation-text {
            font-size: 1.1em;
            font-style: italic;
            color: #FFFFFF;
            margin-bottom: 10px;
        }

        .carousel-slide p.customer-name {
            font-size: 0.9em;
            font-weight: bold;
            color: #CCCCCC;
        }

        /* Botões Prev/Next */
        .prev, .next {
            cursor: pointer;
            position: absolute;
            top: 50%;
            width: auto;
            padding: 16px;
            margin-top: -22px;
            color: white;
            font-weight: bold;
            font-size: 20px;
            transition: 0.6s ease;
            border-radius: 0 3px 3px 0;
            user-select: none;
            background-color: rgba(0,0,0,0.5); /* Fundo semi-transparente */
        }

        .next {
            right: 0;
            border-radius: 3px 0 0 3px;
        }
        .prev {
            left: 0;
        }

        .prev:hover, .next:hover {
            background-color: rgba(255,255,255,0.3); /* Branco com transparência no hover */
        }

        /* Pontos de Navegação (opcional, mas bom para UX) */
        .dots-container {
            text-align: center;
            padding-top: 10px;
        }

        .dot {
            cursor: pointer;
            height: 13px;
            width: 13px;
            margin: 0 3px;
            background-color: #777777; /* Cinza para pontos inativos */
            border-radius: 50%;
            display: inline-block;
            transition: background-color 0.6s ease;
        }

        .active-dot, .dot:hover {
            background-color: #FFFFFF; /* Branco para ponto ativo/hover */
        }
        /* Fim dos Estilos do Carrossel */


        footer {
            padding: 20px;
            margin-top: 20px;
            color: #FFFFFF;
            background-color: #000000;
            text-align: center;
            border-top: #FFFFFF 3px solid;
        }

        /* Para o menu de navegação simples */
        nav ul {
            list-style-type: none;
            padding: 0;
            text-align: center; /* Centraliza os itens do menu no header */
        }
        nav ul li {
            display: inline;
            margin-right: 20px;
        }
        nav ul li a {
            color: #fff;
            text-decoration: none;
        }
        nav ul li a:hover {
            color: #ccc;
        }

        /* Responsividade simples */
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

            #showcase h1 {
                font-size: 40px;
            }

            #showcase p {
                font-size: 18px;
            }

            .container {
                width: 95%;
            }

            .prev, .next {
                font-size: 16px;
                padding: 12px;
            }
        }

    </style>
</head>
<body>
    <?php
        // index.php (no topo ou antes da seção do carrossel)
        // session_start(); // Se você precisar de sessão por outros motivos na index.php
        require_once 'config/database.php'; // Garanta que o caminho está correto

        $carousel_recomendacoes = [];
        // Buscar, por exemplo, as 5 recomendações aprovadas mais recentes
        // Ou RAND() para aleatórias, mas pode ser mais lento em tabelas grandes.
        // Vamos pegar as mais recentes com um limite.
        $sql_carousel_recs = "SELECT r.nome_cliente, r.texto_recomendacao, u.name as nome_barbeiro
                            FROM Recomendacoes r
                            LEFT JOIN Users u ON r.barbeiro_id = u.user_id AND u.role = 'barber'
                            WHERE r.aprovado = 1
                            ORDER BY r.data_envio DESC
                            LIMIT 5"; // Limite para o carrossel da página inicial

        if ($result_carousel = $mysqli->query($sql_carousel_recs)) {
            if ($result_carousel->num_rows > 0) {
                while ($row = $result_carousel->fetch_assoc()) {
                    $carousel_recomendacoes[] = $row;
                }
            }
            $result_carousel->free();
        } else {
            // Para depuração, se a query falhar:
            // echo "Erro ao buscar recomendações para o carrossel: " . $mysqli->error;
        }
        // Não feche $mysqli aqui se for usado mais abaixo na página. Se for o último uso, pode fechar.
?>
    <header>
        <div class="container">
            <div id="branding">
                <h1><span class="highlight">Barbearia</span> JB</h1>
            </div>
            <nav>
                <ul>
                    <li class="current"><a href="index.php">Início</a></li>
                    <li><a href="servicos.php">Serviços</a></li>
                    <li><a href="produtos.php">Bebidas</a></li>
                    <li><a href="recomendacoes.php">Recomendações</a></li>
                    <li><a href="login.php">Login</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <section id="showcase">
        <div class="container">
            <h1>Barbearia JB: 20 Anos</h1>
            <p>"Sempre o melhor para você."</p>
        </div>
    </section>

    <div class="container" id="main-content">
        <section class="section" id="nossa-historia">
            <h2>Nossa História: Duas Décadas de Excelência</h2>
            <p>Há duas décadas, em abril de 2005, nascia em Teresópolis um sonho talhado com a precisão de uma navalha afiada: a <strong>Barbearia JB</strong>. Fundada por Bruno Pereira de Souza, um visionário empreendedor com raízes nordestinas e um coração determinado a oferecer excelência, a JB rapidamente se tornou mais do que uma simples barbearia – transformou-se em um refúgio para homens que buscam o melhor em cuidado pessoal e atendimento.</p>
            <p>A paixão de Bruno pela arte da barbearia floresceu cedo, <span class="history-emphasis">aos nove anos</span>, sob a tutela de seu pai, também barbeiro, na acolhedora Capela do Alto Alegre, Bahia. O legado familiar e o talento nato o impulsionaram a buscar novos horizontes. Aos dezesseis anos, Teresópolis, no Rio de Janeiro, tornou-se seu novo lar. Foi na "Barbearia 2 Irmãos" que Bruno aprimorou suas técnicas, absorvendo experiência e sonhando com seu próprio espaço.</p>
            <p>Esse sonho se materializou na <span class="history-emphasis">Avenida J.J. Araújo Regadas, loja 20</span>, onde a Barbearia JB fincou suas raízes. Desde o primeiro dia, a máxima <strong class="history-emphasis">"Sempre o melhor para você"</strong> não é apenas um slogan, mas a filosofia que guia cada corte, cada barba aparada, cada cliente que entra pela porta. São 20 anos de dedicação, tradição e a constante busca por superar expectativas, oferecendo um ambiente onde a qualidade e a satisfação do cliente são a nossa maior prioridade. Convidamos você a fazer parte desta história e descobrir por que, na Barbearia JB, cada detalhe é pensado para o seu bem-estar.</p>
        </section>
<?php
// index.php (no topo ou antes da seção do carrossel)
// session_start(); // Se você precisar de sessão por outros motivos na index.php
require_once 'config/database.php'; // Garanta que o caminho está correto

$carousel_recomendacoes = [];
// Buscar, por exemplo, as 5 recomendações aprovadas mais recentes
// Ou RAND() para aleatórias, mas pode ser mais lento em tabelas grandes.
// Vamos pegar as mais recentes com um limite.
$sql_carousel_recs = "SELECT r.nome_cliente, r.texto_recomendacao, u.name as nome_barbeiro
                      FROM Recomendacoes r
                      LEFT JOIN Users u ON r.barbeiro_id = u.user_id AND u.role = 'barber'
                      WHERE r.aprovado = 1
                      ORDER BY r.data_envio DESC
                      LIMIT 5"; // Limite para o carrossel da página inicial

if ($result_carousel = $mysqli->query($sql_carousel_recs)) {
    if ($result_carousel->num_rows > 0) {
        while ($row = $result_carousel->fetch_assoc()) {
            $carousel_recomendacoes[] = $row;
        }
    }
    $result_carousel->free();
} else {
    // Para depuração, se a query falhar:
    // echo "Erro ao buscar recomendações para o carrossel: " . $mysqli->error;
}
// Não feche $mysqli aqui se for usado mais abaixo na página. Se for o último uso, pode fechar.
?>
<?php if (!empty($carousel_recomendacoes)): ?>
<section class="section" id="recomendacoes">
    <h2>O Que Dizem Nossos Clientes</h2>
    <div class="carousel-container">
        <?php foreach ($carousel_recomendacoes as $index => $rec): ?>
            <div class="carousel-slide <?php echo ($index == 0) ? 'active' : ''; ?>">
                <?php if (!empty($rec['nome_barbeiro'])): ?>
                    <p style="font-size:0.9em; color:#f39c12; margin-bottom:5px; font-weight:bold;">
                        Recomendação para: <?php echo htmlspecialchars($rec['nome_barbeiro']); ?>
                    </p>
                <?php endif; ?>
                <p class="recommendation-text">"<?php echo nl2br(htmlspecialchars($rec['texto_recomendacao'])); ?>"</p>
                <p class="customer-name">- <?php echo htmlspecialchars($rec['nome_cliente']); ?></p>
            </div>
        <?php endforeach; ?>

        <?php if (count($carousel_recomendacoes) > 1): // Só mostra botões se houver mais de 1 slide ?>
            <a class="prev" onclick="plusSlides(-1)">&#10094;</a>
            <a class="next" onclick="plusSlides(1)">&#10095;</a>
        <?php endif; ?>
    </div>

    <?php if (count($carousel_recomendacoes) > 1): // Só mostra dots se houver mais de 1 slide ?>
    <div class="dots-container">
        <?php foreach ($carousel_recomendacoes as $index => $rec): ?>
            <span class="dot <?php echo ($index == 0) ? 'active-dot' : ''; ?>" onclick="currentSlide(<?php echo $index + 1; ?>)"></span>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</section>
<?php endif; // Fim do if (!empty($carousel_recomendacoes)) ?>
        <section class="section" id="nossos-valores">
            <h2>Nossos Valores</h2>
            <p><strong>Tradição:</strong> Honramos a arte clássica da barbearia, passada de geração em geração.</p>
            <p><strong>Qualidade:</strong> Utilizamos os melhores produtos e técnicas para garantir um resultado impecável.</p>
            <p><strong>Atendimento:</strong> Cada cliente é único e merece uma experiência personalizada e acolhedora.</p>
            <p><strong>Comunidade:</strong> Somos mais que uma barbearia, somos um ponto de encontro e amizade.</p>
        </section>

        <section class="section" id="contato-localizacao">
            <h2>Visite-nos</h2>
            <p>Estamos ansiosos para recebê-lo!</p>
            <p><strong>Endereço:</strong> Av. J.J. Araújo Regadas, Lj 20, Teresópolis - RJ</p>
            <p><strong>Horário de Funcionamento:</strong> [Segunda a Sábado: 9h - 20h] </p>
            <p><em>Não trabalhamos com agendamento. Atendimento por ordem de chegada.</em></p>
        </section>
    </div>

    <footer>
        <p>Barbearia JB &copy; <?php echo date("Y"); ?> - Todos os direitos reservados.</p>
        <p>"Sempre o melhor para você."</p>
    </footer>

    <script>
        let slideIndex = 1;
        showSlides(slideIndex);

        // Controles Next/previous
        function plusSlides(n) {
            showSlides(slideIndex += n);
        }

        // Controles de Thumbnail (pontos)
        function currentSlide(n) {
            showSlides(slideIndex = n);
        }

        function showSlides(n) {
            let i;
            let slides = document.getElementsByClassName("carousel-slide");
            let dots = document.getElementsByClassName("dot");
            if (n > slides.length) {slideIndex = 1}
            if (n < 1) {slideIndex = slides.length}
            for (i = 0; i < slides.length; i++) {
                slides[i].style.display = "none";
                slides[i].classList.remove("active");
            }
            for (i = 0; i < dots.length; i++) {
                dots[i].classList.remove("active-dot");
            }
            slides[slideIndex-1].style.display = "block";
            slides[slideIndex-1].classList.add("active");
            dots[slideIndex-1].classList.add("active-dot");
        }

        // Opcional: Troca automática de slides (descomente para usar)
        
        let autoSlideIndex = 0;
        autoShowSlides();

        function autoShowSlides() {
            let i;
            let slides = document.getElementsByClassName("carousel-slide");
            let dots = document.getElementsByClassName("dot");
            for (i = 0; i < slides.length; i++) {
                slides[i].style.display = "none";
                slides[i].classList.remove("active");
            }
            autoSlideIndex++;
            if (autoSlideIndex > slides.length) {autoSlideIndex = 1}
            for (i = 0; i < dots.length; i++) {
                dots[i].classList.remove("active-dot");
            }
            slides[autoSlideIndex-1].style.display = "block";
            slides[autoSlideIndex-1].classList.add("active");
            dots[autoSlideIndex-1].classList.add("active-dot");
            setTimeout(autoShowSlides, 5000); // Muda a imagem a cada 5 segundos
        }
        // Se usar o autoShowSlides, você pode querer remover a chamada inicial showSlides(slideIndex)
        // e ajustar o slideIndex inicial ou a lógica de interação com os botões manuais.
        // Por simplicidade, o código acima mantém o carrossel manual por padrão.
    </script>

</body>
</html>