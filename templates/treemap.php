<?php
if (!defined('ABSPATH')) {
    exit;
}

// Obter o número de criptomoedas configurado
$options = get_option('crypto_treemap_settings');
$limit = isset($options['crypto_limit']) ? $options['crypto_limit'] : '50';
?>
<div id="crypto-treemap-container" class="crypto-treemap-container">
    <h2 class="crypto-treemap-title">Top <?php echo esc_html($limit); ?> Criptomoedas em BRL</h2>
    <div class="crypto-treemap-updated">Atualizado em: <span id="crypto-treemap-timestamp"><?php echo date('d/m/Y, H:i:s'); ?></span></div>
    <div id="crypto-treemap" class="crypto-treemap">
        <!-- Dados serão inseridos aqui via JavaScript -->
        <div class="crypto-treemap-loading">Carregando dados...</div>
    </div>
</div>

<style>
/* Estilos para o treemap */
.crypto-treemap-container {
    width: 100%;
    max-width: 100%;
    margin: 0 auto;
    padding: 15px;
    box-sizing: border-box;
    background-color: #f5f5f5;
    border-radius: 8px;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
}

.crypto-treemap-title {
    font-size: 1.5em;
    margin-bottom: 10px;
    text-align: center;
    font-weight: bold;
}

.crypto-treemap-updated {
    font-size: 0.8em;
    color: #777;
    margin-bottom: 15px;
    text-align: right;
}

.crypto-treemap {
    width: 100%;
    height: 600px;
    position: relative;
    margin-top: 15px;
    background: #fff;
    border-radius: 8px;
    overflow: hidden;
}

.crypto-treemap-loading {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    padding: 20px;
    text-align: center;
    background: rgba(248, 248, 248, 0.9);
    border-radius: 8px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    z-index: 100;
}

.crypto-treemap-error {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    padding: 20px;
    text-align: center;
    background: rgba(244, 67, 54, 0.1);
    color: #f44336;
    border-radius: 8px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    z-index: 100;
}

.crypto-treemap-cell {
    position: absolute;
    overflow: hidden;
    box-sizing: border-box;
    border: 1px solid #fff;
    transition: all 0.3s ease;
    padding: 5px;
    font-size: 12px;
    cursor: pointer;
}

.crypto-treemap-cell:hover {
    z-index: 10;
    box-shadow: 0 0 15px rgba(0, 0, 0, 0.2);
    transform: scale(1.02);
}

.crypto-treemap-cell.green {
    background-color: #4CAF50;
    color: white;
}

.crypto-treemap-cell.red {
    background-color: #f44336;
    color: white;
}

.crypto-treemap-cell-content {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    height: 100%;
    width: 100%;
}

.crypto-treemap-symbol {
    font-weight: bold;
    text-align: center;
    margin-bottom: 2px;
    font-size: 110%;
}

.crypto-treemap-price {
    text-align: center;
    margin-bottom: 2px;
    font-size: 95%;
}

.crypto-treemap-change {
    text-align: center;
    font-size: 85%;
}

.crypto-treemap-volume {
    text-align: center;
    font-size: 75%;
    opacity: 0.8;
}

/* Classes para animação de piscar */
.crypto-treemap-cell.blink {
    animation: blink-animation 0.5s;
}

@keyframes blink-animation {
    0% { opacity: 0.6; }
    50% { opacity: 1; }
    100% { opacity: 0.6; }
}

/* Ajustes para dispositivos móveis */
@media (max-width: 768px) {
    .crypto-treemap {
        height: 500px;
    }
    
    .crypto-treemap-symbol {
        font-size: 105%;
    }
    
    .crypto-treemap-price {
        font-size: 90%;
    }
}

@media (max-width: 480px) {
    .crypto-treemap {
        height: 400px;
    }
    
    .crypto-treemap-symbol {
        font-size: 95%;
    }
    
    .crypto-treemap-price {
        font-size: 85%;
    }
    
    .crypto-treemap-change, 
    .crypto-treemap-volume {
        font-size: 75%;
    }
}
</style> 