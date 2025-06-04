<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barbearia JB - Desde 2005</title>
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/toogle_menu.css">
    <script src="js/toogle_menu.js"></script>
    <style>
       
    </style>
    <script>
        
    </script>
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
    <?php include('routes/header.php')?>

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
            <p><strong>Horário de Funcionamento:</strong> Segunda a Sábado: 8h - 20h </p>
            <p><em>Não trabalhamos com agendamento. Atendimento por ordem de chegada.</em></p>
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