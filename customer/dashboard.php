<?php
session_start();
require_once '../config/database.php'; // Ajuste o caminho

// Verifica se o cliente está logado
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'customer') {
    header("location: ../login.php");
    exit;
}

$cliente_id = $_SESSION["user_id"];
$cliente_nome = $_SESSION["name"];

// Buscar lista de barbeiros para os dropdowns
$barbeiros_lista = [];
$sql_barbeiros = "SELECT user_id, name FROM Users WHERE role = 'barber' AND is_active = 1 ORDER BY name ASC";
if ($result_barbs = $mysqli->query($sql_barbeiros)) {
    while ($barb = $result_barbs->fetch_assoc()) {
        $barbeiros_lista[] = $barb;
    }
    $result_barbs->free();
}

// Lógica para buscar fotos do mural do barbeiro selecionado
$fotos_do_barbeiro_selecionado = [];
$barbeiro_mural_selecionado_id = null;
$barbeiro_mural_selecionado_nome = "";

if (isset($_GET['view_mural_barber_id']) && is_numeric($_GET['view_mural_barber_id'])) {
    $barbeiro_mural_selecionado_id = (int)$_GET['view_mural_barber_id'];

    // Pegar nome do barbeiro selecionado
    foreach($barbeiros_lista as $b_info){
        if($b_info['user_id'] == $barbeiro_mural_selecionado_id){
            $barbeiro_mural_selecionado_nome = $b_info['name'];
            break;
        }
    }

    $sql_fotos = "SELECT caminho_imagem, legenda, DATE_FORMAT(data_upload, '%d/%m/%Y') as data_f
                  FROM Fotos_Barbeiro
                  WHERE barbeiro_id = ?
                  ORDER BY data_upload DESC";
    if ($stmt_fotos = $mysqli->prepare($sql_fotos)) {
        $stmt_fotos->bind_param("i", $barbeiro_mural_selecionado_id);
        $stmt_fotos->execute();
        $result_fotos_mural = $stmt_fotos->get_result();
        while ($foto_mural = $result_fotos_mural->fetch_assoc()) {
            $fotos_do_barbeiro_selecionado[] = $foto_mural;
        }
        $stmt_fotos->close();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minha Conta - Barbearia JB</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #121212; color: #FFF; padding: 0; margin:0; line-height: 1.6; }
        .customer-header { background-color: #000; color: #FFF; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; border-bottom:3px solid #f39c12;}
        .customer-header h1 { margin: 0; font-size: 1.5em; }
        .customer-header a { color: #FFF; text-decoration: none; }
        .customer-container { max-width: 900px; margin: 30px auto; padding: 20px; background-color: #1E1E1E; border-radius: 8px; }
        h2 { color: #f39c12; border-bottom: 1px solid #444; padding-bottom: 10px; margin-top: 0;}
        .section { margin-bottom: 30px; background-color: #282828; padding: 20px; border-radius: 5px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; font-size:0.9em; color:#DDD; }
        .form-group select, .form-group textarea, .form-group button {
            width: calc(100% - 22px); padding: 10px; background-color: #333; border: 1px solid #555;
            color: #FFF; border-radius: 4px; font-size: 1em;
        }
        .form-group textarea { width: calc(100% - 22px); min-height: 80px; } /* Ajuste de largura para textarea */
        .form-group button { background-color: #f39c12; color: #000; border: none; cursor: pointer; font-weight: bold; width: auto; }
        .message { padding: 10px; margin-bottom: 15px; border-radius:4px; text-align:center;}
        .message.success { background-color: #27ae60; color:white; }
        .message.error { background-color: #c0392b; color:white; }

        .mural-gallery { display: flex; flex-wrap: wrap; gap: 15px; margin-top: 15px; justify-content: flex-start;}
        .foto-item { background-color:#333; padding:10px; border-radius:5px; text-align:center; width: calc(33.333% - 10px); box-sizing: border-box; }
        .foto-item img { width: 100%; height: 180px; object-fit: cover; border-radius: 3px; margin-bottom: 8px; }
        .foto-item p { font-size: 0.85em; color: #ccc; margin-bottom: 5px; word-wrap: break-word; }
        .foto-item .data { font-size: 0.75em; color: #888; }
        @media (max-width: 768px) { .foto-item { width: calc(50% - 8px); } }
        @media (max-width: 480px) { .foto-item { width: 100%; } }
    </style>
</head>
<body>
    <header class="customer-header">
        <h1>Barbearia JB - Minha Conta</h1>
        <div>
            <span>Olá, <?php echo htmlspecialchars($cliente_nome); ?>!</span>
            <a href="../logout.php" style="margin-left: 20px;">Sair</a>
        </div>
    </header>

    <div class="customer-container">
        <?php
            if (isset($_SESSION['customer_message'])) {
                echo '<div class="message ' . htmlspecialchars($_SESSION['customer_message_type']) . '">' . htmlspecialchars($_SESSION['customer_message']) . '</div>';
                unset($_SESSION['customer_message']);
                unset($_SESSION['customer_message_type']);
            }
        ?>

        <section class="section">
            <h2>Deixar uma Recomendação</h2>
            <form action="handle_add_recomendacao.php" method="POST">
                <div class="form-group">
                    <label for="barbeiro_id_rec">Barbeiro que te atendeu:</label>
                    <select name="barbeiro_id" id="barbeiro_id_rec" required>
                        <option value="">Selecione um barbeiro</option>
                        <?php foreach($barbeiros_lista as $barbeiro): ?>
                            <option value="<?php echo $barbeiro['user_id']; ?>"><?php echo htmlspecialchars($barbeiro['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="texto_recomendacao">Sua Recomendação:</label>
                    <textarea name="texto_recomendacao" id="texto_recomendacao" rows="5" required></textarea>
                </div>
                <div class="form-group">
                    <button type="submit">Enviar Recomendação</button>
                </div>
            </form>
        </section>

        <section class="section">
            <h2>Ver Mural de Fotos dos Barbeiros</h2>
            <form action="dashboard.php" method="GET">
                 <div class="form-group">
                    <label for="view_mural_barber_id">Selecione o Barbeiro:</label>
                    <select name="view_mural_barber_id" id="view_mural_barber_id" onchange="this.form.submit()">
                        <option value="">Escolha um barbeiro para ver o mural</option>
                        <?php foreach($barbeiros_lista as $barbeiro): ?>
                            <option value="<?php echo $barbeiro['user_id']; ?>" <?php echo ($barbeiro_mural_selecionado_id == $barbeiro['user_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($barbeiro['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                </form>

            <?php if ($barbeiro_mural_selecionado_id && !empty($barbeiro_mural_selecionado_nome)): ?>
                <h3>Mural de <?php echo htmlspecialchars($barbeiro_mural_selecionado_nome); ?></h3>
                <?php if (!empty($fotos_do_barbeiro_selecionado)): ?>
                    <div class="mural-gallery">
                        <?php foreach($fotos_do_barbeiro_selecionado as $foto): ?>
                            <div class="foto-item">
                                <img src="../<?php echo htmlspecialchars($foto['caminho_imagem']); ?>" alt="<?php echo htmlspecialchars($foto['legenda'] ?? 'Foto do Mural'); ?>">
                                <?php if(!empty($foto['legenda'])): ?>
                                    <p><?php echo htmlspecialchars($foto['legenda']); ?></p>
                                <?php endif; ?>
                                <p class="data">Postada em: <?php echo $foto['data_f']; ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p><?php echo htmlspecialchars($barbeiro_mural_selecionado_nome); ?> ainda não postou fotos no mural.</p>
                <?php endif; ?>
            <?php endif; ?>
        </section>

        <!-- <section class="section">
            <h2>Meu Histórico de Atendimentos (Sugestão)</h2>
            <p>Aqui você poderá ver os serviços que realizou, com qual barbeiro e quando. (Requer alteração na forma como o barbeiro registra atendimentos para vincular ao seu ID de cliente).</p>
            </section>

        <section class="section">
            <h2>Programa de Fidelidade (Sugestão)</h2>
            <p>Acompanhe seus pontos! A cada 10 cortes de cabelo, ganhe 1 grátis. (Requer uma coluna 'pontos_fidelidade' na tabela de usuários/clientes e lógica de incremento).</p>
            </section> -->

    </div>
</body>
</html>