<?php
/**
 * Plugin Name: Crypto Treemap Widget
 * Plugin URI: https://bitcoinp2p.com.br
 * Description: Widget para exibir um treemap das principais criptomoedas em tempo real
 * Version: 1.0.0
 * Author: BitcoinP2P
 * Author URI: https://bitcoinp2p.com.br
 * Text Domain: crypto-treemap
 */

// Evitar acesso direto ao arquivo
if (!defined('ABSPATH')) {
    exit;
}

// Definir constantes do plugin
define('CRYPTO_TREEMAP_VERSION', '1.0.0');
define('CRYPTO_TREEMAP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CRYPTO_TREEMAP_PLUGIN_URL', plugin_dir_url(__FILE__));

// Classe principal do plugin
class CryptoTreemap {
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Hooks de inicialização
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Registrar shortcode
        add_shortcode('crypto_treemap', array($this, 'render_treemap'));
        
        // Registrar endpoint da API REST com prioridade baixa para garantir que o WordPress já está pronto
        add_action('rest_api_init', array($this, 'register_rest_route'), 10);
        
        // Debug - verificar se a REST API está funcionando
        if (WP_DEBUG) {
            add_action('init', function() {
                error_log('[Crypto Treemap] REST API URL Base: ' . rest_url());
                error_log('[Crypto Treemap] REST API Ativa: ' . (rest_get_url_prefix() ? 'Sim' : 'Não'));
            });
        }
        
        // Adicionar filtro para CORS
        add_action('rest_api_init', function() {
            remove_filter('rest_pre_serve_request', 'rest_send_cors_headers');
            add_filter('rest_pre_serve_request', function($value) {
                header('Access-Control-Allow-Origin: *');
                header('Access-Control-Allow-Methods: GET, OPTIONS');
                header('Access-Control-Allow-Credentials: true');
                header('Access-Control-Allow-Headers: X-Requested-With, Content-Type, X-WP-Nonce');
                header('Access-Control-Expose-Headers: Link');
                return $value;
            });
        });
    }
    
    public function init() {
        load_plugin_textdomain('crypto-treemap', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Verificar se precisamos atualizar as regras de rewrite
        if (get_option('crypto_treemap_flush_rewrite')) {
            global $wp_rewrite;
            $wp_rewrite->flush_rules(true);
            delete_option('crypto_treemap_flush_rewrite');
        }
    }
    
    public function add_admin_menu() {
        add_options_page(
            __('Configurações do Crypto Treemap', 'crypto-treemap'),
            __('Crypto Treemap', 'crypto-treemap'),
            'manage_options',
            'crypto-treemap',
            array($this, 'render_settings_page')
        );
    }
    
    public function register_settings() {
        register_setting('crypto_treemap_options', 'crypto_treemap_settings');
        
        add_settings_section(
            'crypto_treemap_main',
            __('Configurações Principais', 'crypto-treemap'),
            array($this, 'settings_section_callback'),
            'crypto-treemap'
        );
        
        add_settings_field(
            'coinmarketcap_api_key',
            __('Chave API CoinMarketCap', 'crypto-treemap'),
            array($this, 'api_key_callback'),
            'crypto-treemap',
            'crypto_treemap_main'
        );
        
        add_settings_field(
            'update_interval',
            __('Intervalo de Atualização (segundos)', 'crypto-treemap'),
            array($this, 'update_interval_callback'),
            'crypto-treemap',
            'crypto_treemap_main'
        );
        
        add_settings_field(
            'crypto_limit',
            __('Número de Criptomoedas', 'crypto-treemap'),
            array($this, 'crypto_limit_callback'),
            'crypto-treemap',
            'crypto_treemap_main'
        );
        
        // Novo campo para URL de redirecionamento
        add_settings_field(
            'redirect_url',
            __('URL de Redirecionamento', 'crypto-treemap'),
            array($this, 'redirect_url_callback'),
            'crypto-treemap',
            'crypto_treemap_main'
        );
    }
    
    public function settings_section_callback() {
        echo '<p>' . __('Configure as opções do widget Crypto Treemap.', 'crypto-treemap') . '</p>';
    }
    
    public function api_key_callback() {
        $options = get_option('crypto_treemap_settings');
        $api_key = isset($options['api_key']) ? $options['api_key'] : '';
        echo '<input type="text" id="api_key" name="crypto_treemap_settings[api_key]" value="' . esc_attr($api_key) . '" class="regular-text">';
    }
    
    public function update_interval_callback() {
        $options = get_option('crypto_treemap_settings');
        $interval = isset($options['update_interval']) ? $options['update_interval'] : '30';
        echo '<input type="number" id="update_interval" name="crypto_treemap_settings[update_interval]" value="' . esc_attr($interval) . '" min="5" max="3600">';
        echo '<p class="description">' . __('Mínimo: 5 segundos, Máximo: 3600 segundos (1 hora)', 'crypto-treemap') . '</p>';
    }
    
    public function crypto_limit_callback() {
        $options = get_option('crypto_treemap_settings');
        $limit = isset($options['crypto_limit']) ? $options['crypto_limit'] : '50';
        echo '<select id="crypto_limit" name="crypto_treemap_settings[crypto_limit]">';
        echo '<option value="10" ' . selected($limit, '10', false) . '>Top 10</option>';
        echo '<option value="20" ' . selected($limit, '20', false) . '>Top 20</option>';
        echo '<option value="50" ' . selected($limit, '50', false) . '>Top 50</option>';
        echo '</select>';
    }
    
    public function redirect_url_callback() {
        $options = get_option('crypto_treemap_settings');
        $redirect_url = isset($options['redirect_url']) ? $options['redirect_url'] : 'https://app.bitcoinp2p.com.br/';
        echo '<input type="url" id="redirect_url" name="crypto_treemap_settings[redirect_url]" value="' . esc_attr($redirect_url) . '" class="regular-text">';
        echo '<p class="description">' . __('URL para onde os usuários serão redirecionados ao clicar em uma criptomoeda. Deixe em branco para desativar o redirecionamento.', 'crypto-treemap') . '</p>';
    }
    
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('crypto_treemap_options');
                do_settings_sections('crypto-treemap');
                submit_button();
                ?>
            </form>
            <div class="crypto-treemap-shortcode-info">
                <h2><?php _e('Como usar', 'crypto-treemap'); ?></h2>
                <p><?php _e('Use o shortcode abaixo em qualquer página ou post:', 'crypto-treemap'); ?></p>
                <code>[crypto_treemap]</code>
            </div>
        </div>
        <?php
    }
    
    public function enqueue_scripts() {
        // Registrar e enfileirar o CSS
        wp_register_style(
            'crypto-treemap-style',
            CRYPTO_TREEMAP_PLUGIN_URL . 'assets/css/crypto-treemap.css',
            array(),
            CRYPTO_TREEMAP_VERSION
        );
        
        // Registrar e enfileirar o JavaScript
        // Adicionar dependência para D3.js
        wp_register_script(
            'd3-script',
            'https://d3js.org/d3.v7.min.js',
            array(),
            '7.0.0',
            true
        );
        
        wp_register_script(
            'crypto-treemap-script',
            CRYPTO_TREEMAP_PLUGIN_URL . 'assets/js/crypto-treemap.js',
            array('jquery', 'd3-script'),
            CRYPTO_TREEMAP_VERSION,
            true
        );
        
        // Configurações para o JavaScript
        $options = get_option('crypto_treemap_settings');
        $update_interval = isset($options['update_interval']) ? intval($options['update_interval']) : 30;
        $crypto_limit = isset($options['crypto_limit']) ? intval($options['crypto_limit']) : 50;
        $redirect_url = isset($options['redirect_url']) ? esc_url_raw($options['redirect_url']) : '';
        
        wp_localize_script('crypto-treemap-script', 'cryptoTreemapSettings', array(
            'ajaxUrl' => esc_url_raw(rest_url('crypto-treemap/v1/prices')),
            'updateInterval' => $update_interval * 1000,
            'limit' => $crypto_limit,
            'redirectUrl' => $redirect_url,
            'nonce' => wp_create_nonce('wp_rest'),
            'siteUrl' => site_url(),
            'debug' => false
        ));

        // Adicionar CSS inline para garantir a exibição correta
        wp_add_inline_style('crypto-treemap-style', '
            .crypto-treemap-container {
                width: 100%;
                max-width: 100%;
                margin: 0 auto;
                padding: 15px;
                box-sizing: border-box;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            }
            .crypto-treemap-grid {
                margin-top: 15px;
            }
            .crypto-treemap-loading,
            .crypto-treemap-error {
                padding: 20px;
                text-align: center;
                background: #f8f8f8;
                border: 1px solid #ddd;
                border-radius: 4px;
            }
            .crypto-treemap-error {
                color: #f44336;
            }
        ');

        // Enfileirar os scripts apenas quando o shortcode estiver presente
        global $post;
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'crypto_treemap')) {
            wp_enqueue_style('crypto-treemap-style');
            wp_enqueue_script('crypto-treemap-script');
            error_log('[Crypto Treemap] Scripts enfileirados para a página ' . $post->ID);
        }
    }
    
    public function register_rest_route() {
        // More detailed debugging
        error_log('[Crypto Treemap] Iniciando registro da rota REST API');
        error_log('[Crypto Treemap] REST API URL Base: ' . rest_url());
        error_log('[Crypto Treemap] REST API Namespace: crypto-treemap/v1');
        
        // Check if REST API is available
        if (!function_exists('register_rest_route')) {
            error_log('[Crypto Treemap] Erro crítico: REST API não está disponível');
            return false;
        }

        // Add a simple test route first
        register_rest_route(
            'crypto-treemap/v1',
            '/test',
            array(
                'methods' => 'GET',
                'callback' => function() {
                    error_log('[Crypto Treemap] Rota de teste acessada com sucesso');
                    return new WP_REST_Response(array('status' => 'ok', 'message' => 'API funcionando'), 200);
                },
                'permission_callback' => '__return_true'
            )
        );

        // Register the main route with simplified parameters
        register_rest_route(
            'crypto-treemap/v1',
            '/prices',
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_crypto_prices'),
                'permission_callback' => '__return_true',
                'args' => array(
                    'limit' => array(
                        'default' => 50,
                        'sanitize_callback' => 'absint'
                    )
                )
            )
        );

        // Verify routes were registered correctly
        $routes = rest_get_server()->get_routes();
        if (isset($routes['crypto-treemap/v1/prices'])) {
            error_log('[Crypto Treemap] Rota principal registrada com sucesso');
            error_log('[Crypto Treemap] URL completa: ' . rest_url('crypto-treemap/v1/prices'));
        } else {
            error_log('[Crypto Treemap] ERRO CRÍTICO: Rota principal não encontrada após tentativa de registro');
        }
        
        if (isset($routes['crypto-treemap/v1/test'])) {
            error_log('[Crypto Treemap] Rota de teste registrada com sucesso');
        } else {
            error_log('[Crypto Treemap] ERRO: Rota de teste não encontrada após registro');
        }
    }
    
    public function get_crypto_prices($request) {
        // Debug
        error_log('[Crypto Treemap] Recebida requisição para get_crypto_prices');
        
        $options = get_option('crypto_treemap_settings');
        $api_key = isset($options['api_key']) ? $options['api_key'] : '';
        $limit = $request->get_param('limit') ?: (isset($options['crypto_limit']) ? $options['crypto_limit'] : '50');
        
        if (empty($api_key)) {
            error_log('[Crypto Treemap] Erro: API key não configurada');
            return new WP_Error(
                'no_api_key',
                'API key não configurada',
                array('status' => 403)
            );
        }
        
        // Verificar cache
        $cache_key = 'crypto_treemap_prices_' . $limit;
        $cached_data = get_transient($cache_key);
        
        if ($cached_data !== false) {
            $decoded_data = json_decode($cached_data, true);
            if ($decoded_data) {
                error_log('[Crypto Treemap] Retornando dados do cache');
                return rest_ensure_response($decoded_data);
            }
        }
        
        // Debug - Log da URL que será usada
        error_log('[Crypto Treemap] Fazendo requisição para CoinMarketCap');
        
        // MODIFICAÇÃO: Usar o endpoint quotes/latest em vez de listings/latest
        // similar ao bot Telegram que está funcionando
        $url = 'https://pro-api.coinmarketcap.com/v2/cryptocurrency/quotes/latest';
        
        // Obter as top moedas primeiro para depois consultar quotes
        $listings_url = add_query_arg(array(
            'start' => '1',
            'limit' => $limit,
            'convert' => 'BRL',
            'sort' => 'market_cap',
            'sort_dir' => 'desc'
        ), 'https://pro-api.coinmarketcap.com/v1/cryptocurrency/listings/latest');
        
        $listings_response = wp_remote_get($listings_url, array(
            'headers' => array(
                'X-CMC_PRO_API_KEY' => $api_key,
                'Accept' => 'application/json'
            ),
            'timeout' => 30
        ));
        
        if (is_wp_error($listings_response)) {
            error_log('[Crypto Treemap] Erro na API CoinMarketCap (listings): ' . $listings_response->get_error_message());
            return new WP_Error(
                'api_error',
                'Erro ao acessar a API: ' . $listings_response->get_error_message(),
                array('status' => 500)
            );
        }
        
        $listings_body = wp_remote_retrieve_body($listings_response);
        $listings_data = json_decode($listings_body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE || !isset($listings_data['data']) || empty($listings_data['data'])) {
            error_log('[Crypto Treemap] Erro ao decodificar JSON de listings: ' . json_last_error_msg());
            return new WP_Error(
                'json_error',
                'Erro ao processar resposta da API (listings)',
                array('status' => 500)
            );
        }
        
        // Extrair símbolos das moedas
        $symbols = array();
        foreach ($listings_data['data'] as $coin) {
            $symbols[] = $coin['symbol'];
        }
        $symbols_string = implode(',', $symbols);
        
        // Consultar quotes com os símbolos
        $quotes_url = add_query_arg(array(
            'symbol' => $symbols_string,
            'convert' => 'BRL'
        ), $url);
        
        error_log('[Crypto Treemap] URL da API: ' . $quotes_url);
        
        $response = wp_remote_get($quotes_url, array(
            'headers' => array(
                'X-CMC_PRO_API_KEY' => $api_key,
                'Accept' => 'application/json'
            ),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            error_log('[Crypto Treemap] Erro na API CoinMarketCap (quotes): ' . $response->get_error_message());
            return new WP_Error(
                'api_error',
                'Erro ao acessar a API: ' . $response->get_error_message(),
                array('status' => 500)
            );
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            error_log('[Crypto Treemap] Erro na API CoinMarketCap. Código: ' . $response_code);
            error_log('[Crypto Treemap] Corpo da resposta: ' . wp_remote_retrieve_body($response));
            return new WP_Error(
                'api_error',
                'API retornou código de erro: ' . $response_code,
                array('status' => $response_code)
            );
        }
        
        $body = wp_remote_retrieve_body($response);
        error_log('[Crypto Treemap] Resposta da API recebida: ' . substr($body, 0, 200) . '...');
        
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('[Crypto Treemap] Erro ao decodificar JSON: ' . json_last_error_msg());
            return new WP_Error(
                'json_error',
                'Erro ao processar resposta da API',
                array('status' => 500)
            );
        }
        
        // Após receber os dados da API, adicione este log:
        error_log('[Crypto Treemap] Estrutura da resposta da API: ' . print_r($data, true));
        
        // Formatar dados para compatibilidade com o frontend
        $formatted_data = array(
            'status' => array(
                'timestamp' => date('c'),
                'error_code' => 0,
                'error_message' => null,
                'credit_count' => 1
            ),
            'data' => array()
        );
        
        // Mesclar os dados de listings com os quotes de forma mais direta
        if (isset($data['data']) && is_array($data['data'])) {
            $coins = array();
            
            foreach ($data['data'] as $symbol => $coin_data) {
                if (isset($coin_data[0]) && isset($coin_data[0]['quote']['BRL'])) {
                    $quote = $coin_data[0]['quote']['BRL'];
                    
                    $coin = array(
                        'id' => $coin_data[0]['id'],
                        'name' => $coin_data[0]['name'],
                        'symbol' => $symbol,
                        'slug' => $coin_data[0]['slug'],
                        'price' => floatval($quote['price']),
                        'volume_24h' => floatval($quote['volume_24h']),
                        'percent_change_24h' => floatval($quote['percent_change_24h']),
                        'market_cap' => floatval($quote['market_cap']),
                        'last_updated' => $quote['last_updated']
                    );
                    
                    $coins[] = $coin;
                    
                    // Log para depuração
                    error_log('[Crypto Treemap] Processado: ' . $symbol . ' - Preço: ' . $quote['price']);
                }
            }
            
            // Ordenar por market cap
            usort($coins, function($a, $b) {
                return $b['market_cap'] - $a['market_cap'];
            });
            
            $formatted_data['data'] = $coins;
        }
        
        error_log('[Crypto Treemap] Total de moedas formatadas: ' . count($formatted_data['data']));
        
        // Salvar no cache
        $cache_time = isset($options['update_interval']) ? intval($options['update_interval']) : 30;
        set_transient($cache_key, json_encode($formatted_data), $cache_time);
        
        error_log('[Crypto Treemap] Dados obtidos com sucesso');
        return rest_ensure_response($formatted_data);
    }
    
    public function render_treemap($atts) {
        // Enfileirar os assets necessários
        wp_enqueue_style('crypto-treemap-style');
        wp_enqueue_script('crypto-treemap-script');
        
        ob_start();
        include CRYPTO_TREEMAP_PLUGIN_DIR . 'templates/treemap.php';
        return ob_get_clean();
    }

    // Adicionar função de ativação do plugin
    public static function activate() {
        // Marcar para atualizar regras de rewrite na próxima execução
        add_option('crypto_treemap_flush_rewrite', true);
        
        // Forçar atualização das regras de rewrite
        global $wp_rewrite;
        $wp_rewrite->flush_rules(true);
    }

    // Adicionar função de desativação do plugin
    public static function deactivate() {
        // Limpar opção de rewrite
        delete_option('crypto_treemap_flush_rewrite');
        
        // Limpar regras de rewrite
        flush_rewrite_rules(false);
    }
}

// Registrar hooks de ativação/desativação
register_activation_hook(__FILE__, array('CryptoTreemap', 'activate'));
register_deactivation_hook(__FILE__, array('CryptoTreemap', 'deactivate'));

// Inicializar o plugin
function crypto_treemap_init() {
    return CryptoTreemap::get_instance();
}
add_action('plugins_loaded', 'crypto_treemap_init'); 