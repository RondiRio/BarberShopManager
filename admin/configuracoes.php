<?php
session_start();
require_once '../config/database.php'; // Conexão com o banco

// --- SEGURANÇA ---
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'admin') {
    header("location: ../login.php");
    exit;
}

// --- LÓGICA SIMPLIFICADA PARA BUSCAR AS CONFIGURAÇÕES ---

// REMOVIDO: A variável $barbearia_id não é mais necessária.

// Prepara um array de configurações padrão.
$configuracoes = [
    'agendamento_ativo' => false,
    'permitir_agendamento_cliente' => false,
    'taxa_cancelamento_ativa' => false,
    'valor_taxa_cancelamento' => '0.00',
    'prazo_cancelamento_sem_taxa_horas' => 24
];

// ALTERADO: Busca a única linha de configuração da tabela.
$sql = "SELECT * FROM configuracoes WHERE config_id = 1";
if ($result = $mysqli->query($sql)) {
    if ($result->num_rows === 1) {
        $configuracoes_db = $result->fetch_assoc();
        $configuracoes = array_merge($configuracoes, $configuracoes_db);
    }
    $result->free();
}
// O restante do arquivo (HTML, CSS, JS) permanece exatamente o mesmo.
?>
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Configurações - Painel do Dono</title>
    <style>
        /* Estilos copiados do seu projeto para consistência */
        body { font-family: Arial, sans-serif; background-color: #121212; color: #FFF; padding: 20px; margin: 0; }
        .main-container { max-width: 800px; margin: auto; background-color: #1E1E1E; padding: 30px; border-radius: 8px; }
        .admin-header { background-color: #000; color: #FFF; padding: 15px 20px; margin-bottom: 20px; border-bottom: 3px solid #f39c12; }
        .admin-header h1 { margin: 0; font-size: 1.8em; display: inline-block; }
        .admin-header a { color: #FFF; text-decoration: none; float: right; margin-left: 20px; line-height: 2.2em; }
        h1, h2 { color: #FFF; border-bottom: 1px solid #444; padding-bottom: 10px; }
        .form-group { margin-bottom: 25px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: bold; color: #DDD; }
        .form-group input[type="number"] {
            width: 150px;
            padding: 10px;
            background-color: #333;
            border: 1px solid #555;
            color: #FFF;
            border-radius: 4px;
            font-size: 1em;
        }
        button[type="submit"] {
            background-color: #f39c12; color: #000; border: none; padding: 12px 20px;
            border-radius: 4px; cursor: pointer; font-weight: bold; text-transform: uppercase;
            font-size: 1em;
        }
        button[type="submit"]:hover { background-color: #e67e22; }

        /* Estilo para o interruptor (Toggle Switch) */
        .switch { position: relative; display: inline-block; width: 60px; height: 34px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #555; transition: .4s; border-radius: 34px; }
        .slider:before { position: absolute; content: ""; height: 26px; width: 26px; left: 4px; bottom: 4px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: #f39c12; }
        input:checked + .slider:before { transform: translateX(26px); }
        .switch-label { vertical-align: middle; margin-left: 10px; font-weight: bold; }
        
        /* Container para opções avançadas */
        .advanced-options {
            border-left: 3px solid #f39c12;
            margin-top: 20px;
            padding-left: 20px;
            display: <?php echo $configuracoes['agendamento_ativo'] ? 'block' : 'none'; ?>; /* Controla visibilidade inicial */
        }
        
        /* Mensagens de feedback */
        .message { padding: 15px; margin-bottom: 20px; border-radius: 5px; text-align: center; font-weight: bold; }
        .message.success { background-color: #27ae60; color: white; }
        .message.error { background-color: #c0392b; color: white; }
    </style>
</head>
<body>
    <header class="admin-header">
        <h1>Barbearia JB</h1>
        <a href="../logout.php">Sair</a>
        <a href="dashboard.php">Painel Principal</a>
    </header>
    
    <div class="main-container">
        <h2>Configurações da Barbearia</h2>

        <?php
            // Exibe mensagem de sucesso/erro que pode ter sido setada pelo script de handle
            if (isset($_SESSION['form_message'])) {
                echo '<div class="message ' . htmlspecialchars($_SESSION['form_message_type']) . '">' . htmlspecialchars($_SESSION['form_message']) . '</div>';
                unset($_SESSION['form_message']);
                unset($_SESSION['form_message_type']);
            }
        ?>

        <form action="handle_update_configuracoes.php" method="post">
            <fieldset style="border: 1px solid #444; padding: 20px; border-radius: 5px;">
                <legend style="padding: 0 10px; font-size: 1.2em; color: #f39c12;">Agendamentos</legend>

                <div class="form-group">
                    <label class="switch">
                        <input type="checkbox" id="agendamento_ativo" name="agendamento_ativo" value="1" <?php echo $configuracoes['agendamento_ativo'] ? 'checked' : ''; ?>>
                        <span class="slider"></span>
                    </label>
                    <span class="switch-label">Ativar Sistema de Agendamento</span>
                </div>

                <div id="opcoes_agendamento_avancado" class="advanced-options">
                    <div class="form-group">
                        <label class="switch">
                            <input type="checkbox" name="permitir_agendamento_cliente" value="1" <?php echo $configuracoes['permitir_agendamento_cliente'] ? 'checked' : ''; ?>>
                            <span class="slider"></span>
                        </label>
                        <span class="switch-label">Permitir que clientes agendem online</span>
                    </div>

                    <div class="form-group">
                        <label class="switch">
                            <input type="checkbox" name="taxa_cancelamento_ativa" value="1" <?php echo $configuracoes['taxa_cancelamento_ativa'] ? 'checked' : ''; ?>>
                            <span class="slider"></span>
                        </label>
                        <span class="switch-label">Ativar taxa por cancelamento fora do prazo</span>
                    </div>
                    
                    <div class="form-group">
                        <label for="valor_taxa_cancelamento">Valor da Taxa de Cancelamento (R$)</label>
                        <input type="number" id="valor_taxa_cancelamento" name="valor_taxa_cancelamento" step="0.01" min="0" value="<?php echo htmlspecialchars($configuracoes['valor_taxa_cancelamento']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="prazo_cancelamento_sem_taxa_horas">Prazo para cancelamento grátis (em horas)</label>
                        <input type="number" id="prazo_cancelamento_sem_taxa_horas" name="prazo_cancelamento_sem_taxa_horas" step="1" min="0" value="<?php echo htmlspecialchars($configuracoes['prazo_cancelamento_sem_taxa_horas']); ?>">
                    </div>
                </div>

            </fieldset>

            <br>
            <button type="submit">Salvar Alterações</button>
        </form>
    </div>

<script>
    // Script para mostrar/esconder as opções avançadas em tempo real
    document.addEventListener('DOMContentLoaded', function() {
        const agendamentoToggle = document.getElementById('agendamento_ativo');
        const advancedOptionsDiv = document.getElementById('opcoes_agendamento_avancado');

        agendamentoToggle.addEventListener('change', function() {
            if (this.checked) {
                advancedOptionsDiv.style.display = 'block';
            } else {
                advancedOptionsDiv.style.display = 'none';
            }
        });
    });
</script>

</body>
</html>