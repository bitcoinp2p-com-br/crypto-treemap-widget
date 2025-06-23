<?php
/**
 * Plugin Name: Crypto Treemap Widget
 * Plugin URI: https://bitcoinp2p.com.br
 * Description: Advanced widget for displaying cryptocurrency treemap with real-time data, offline support, and enhanced security
 * Version: 2.0.0
 * Author: BitcoinP2P
 * Author URI: https://bitcoinp2p.com.br
 * Text Domain: crypto-treemap
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Evitar acesso direto ao arquivo
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('CRYPTO_TREEMAP_VERSION', '2.0.0');
define('CRYPTO_TREEMAP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CRYPTO_TREEMAP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CRYPTO_TREEMAP_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Load required classes
require_once CRYPTO_TREEMAP_PLUGIN_DIR . 'includes/class-crypto-api.php';
require_once CRYPTO_TREEMAP_PLUGIN_DIR . 'includes/class-crypto-security.php';
require_once CRYPTO_TREEMAP_PLUGIN_DIR . 'includes/class-crypto-assets.php';

// Classe principal do plugin
class CryptoTreemap {
    private static $instance = null;
    private $api;
    private $assets;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Initialize components
        $this->assets = Crypto_Treemap_Assets::init();
        Crypto_Treemap_Security::init();
        
        // Hooks de inicialização
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        
        // Registrar shortcode
        add_shortcode('crypto_treemap', array($this, 'render_treemap'));
        
        // Registrar endpoint da API REST
        add_action('rest_api_init', array($this, 'register_rest_route'), 10);
        
        // Add admin hooks
        add_action('admin_notices', array($this, 'admin_notices'));
        add_filter('plugin_action_links_' . CRYPTO_TREEMAP_PLUGIN_BASENAME, array($this, 'plugin_action_links'));
        
        // Add AJAX handlers for admin
        add_action('wp_ajax_crypto_treemap_clear_cache', array($this, 'ajax_clear_cache'));
        add_action('wp_ajax_crypto_treemap_test_api', array($this, 'ajax_test_api'));
    }
    
    public function init() {
        load_plugin_textdomain('crypto-treemap', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Initialize API with current settings
        $options = get_option('crypto_treemap_settings');
        $api_key = isset($options['api_key']) ? $options['api_key'] : '';
        $this->api = new Crypto_Treemap_API($api_key);
        
        // Verificar se precisamos atualizar as regras de rewrite
        if (get_option('crypto_treemap_flush_rewrite')) {
            flush_rewrite_rules(false);
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
        register_setting(
            'crypto_treemap_options', 
            'crypto_treemap_settings',
            array(
                'sanitize_callback' => array('Crypto_Treemap_Security', 'sanitize_settings'),
                'show_in_rest' => false
            )
        );
        
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
        
        add_settings_field(
            'allowed_origins',
            __('Origens Permitidas (CORS)', 'crypto-treemap'),
            array($this, 'allowed_origins_callback'),
            'crypto-treemap',
            'crypto_treemap_main'
        );
        
        add_settings_field(
            'enable_debug',
            __('Modo Debug', 'crypto-treemap'),
            array($this, 'enable_debug_callback'),
            'crypto-treemap',
            'crypto_treemap_main'
        );
        
        add_settings_field(
            'offline_mode',
            __('Modo Offline', 'crypto-treemap'),
            array($this, 'offline_mode_callback'),
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
    
    public function allowed_origins_callback() {
        $options = get_option('crypto_treemap_settings');
        $allowed_origins = isset($options['allowed_origins']) ? $options['allowed_origins'] : '';
        echo '<textarea id="allowed_origins" name="crypto_treemap_settings[allowed_origins]" rows="3" class="large-text">' . esc_textarea($allowed_origins) . '</textarea>';
        echo '<p class="description">' . __('Lista de origens permitidas para CORS (uma por linha). Deixe em branco para usar apenas o domínio atual.', 'crypto-treemap') . '</p>';
    }
    
    public function enable_debug_callback() {
        $options = get_option('crypto_treemap_settings');
        $enable_debug = isset($options['enable_debug']) ? (bool) $options['enable_debug'] : false;
        echo '<input type="checkbox" id="enable_debug" name="crypto_treemap_settings[enable_debug]" value="1" ' . checked($enable_debug, true, false) . '>';
        echo '<label for="enable_debug">' . __('Ativar logs de debug e métricas de performance', 'crypto-treemap') . '</label>';
    }
    
    public function offline_mode_callback() {
        $options = get_option('crypto_treemap_settings');
        $offline_mode = isset($options['offline_mode']) ? (bool) $options['offline_mode'] : false;
        echo '<input type="checkbox" id="offline_mode" name="crypto_treemap_settings[offline_mode]" value="1" ' . checked($offline_mode, true, false) . '>';
        echo '<label for="offline_mode">' . __('Forçar modo offline (usar dados em cache)', 'crypto-treemap') . '</label>';
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
            
            <div class="crypto-treemap-admin-tools">
                <h2><?php _e('Ferramentas', 'crypto-treemap'); ?></h2>
                <p>
                    <button type="button" class="button" id="crypto-clear-cache"><?php _e('Limpar Cache', 'crypto-treemap'); ?></button>
                    <button type="button" class="button" id="crypto-test-api"><?php _e('Testar API', 'crypto-treemap'); ?></button>
                </p>
                <div id="crypto-admin-messages"></div>
            </div>
            
            <script>
            jQuery(document).ready(function($) {
                $('#crypto-clear-cache').on('click', function() {
                    $.post(ajaxurl, {
                        action: 'crypto_treemap_clear_cache',
                        nonce: '<?php echo wp_create_nonce('crypto_treemap_admin'); ?>'
                    }, function(response) {
                        $('#crypto-admin-messages').html('<div class="notice notice-' + 
                            (response.success ? 'success' : 'error') + ' is-dismissible"><p>' + 
                            response.data + '</p></div>');
                    });
                });
                
                $('#crypto-test-api').on('click', function() {
                    $(this).prop('disabled', true).text('<?php _e('Testando...', 'crypto-treemap'); ?>');
                    $.post(ajaxurl, {
                        action: 'crypto_treemap_test_api',
                        nonce: '<?php echo wp_create_nonce('crypto_treemap_admin'); ?>'
                    }, function(response) {
                        $('#crypto-admin-messages').html('<div class="notice notice-' + 
                            (response.success ? 'success' : 'error') + ' is-dismissible"><p>' + 
                            response.data + '</p></div>');
                        $('#crypto-test-api').prop('disabled', false).text('<?php _e('Testar API', 'crypto-treemap'); ?>');
                    });
                });
            });
            </script>
            <div class="crypto-treemap-shortcode-info">
                <h2><?php _e('Como usar', 'crypto-treemap'); ?></h2>
                <p><?php _e('Use o shortcode abaixo em qualquer página ou post:', 'crypto-treemap'); ?></p>
                <code>[crypto_treemap]</code>
                
                <h3><?php _e('Cache e Performance', 'crypto-treemap'); ?></h3>
                <?php if ($this->api): ?>
                    <?php $stats = $this->api->get_cache_stats(); ?>
                    <p><?php printf(__('Entradas em cache: %d', 'crypto-treemap'), $stats['cached_entries']); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    // Assets are now handled by Crypto_Treemap_Assets class
    
    public function register_rest_route() {
        register_rest_route(
            'crypto-treemap/v1',
            '/prices',
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_crypto_prices'),
                'permission_callback' => array($this, 'check_permissions'),
                'args' => array(
                    'limit' => array(
                        'default' => 50,
                        'sanitize_callback' => 'absint',
                        'validate_callback' => function($param) {
                            return is_numeric($param) && $param > 0 && $param <= 100;
                        }
                    )
                )
            )
        );
        
        // Test endpoint for admin
        register_rest_route(
            'crypto-treemap/v1',
            '/test',
            array(
                'methods' => 'GET',
                'callback' => array($this, 'test_endpoint'),
                'permission_callback' => '__return_true'
            )
        );
    }
    
    public function check_permissions(WP_REST_Request $request) {
        return true; // Public endpoint, no authentication required
    }
    
    public function get_crypto_prices(WP_REST_Request $request) {
        if (!$this->api) {
            return new WP_Error(
                'api_not_initialized',
                __('API not properly initialized', 'crypto-treemap'),
                array('status' => 500)
            );
        }
        
        $limit = $request->get_param('limit');
        $result = $this->api->get_crypto_prices($limit);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        return rest_ensure_response($result);
    }
    
    public function test_endpoint() {
        return new WP_REST_Response(array(
            'status' => 'success',
            'message' => __('API endpoint is working correctly', 'crypto-treemap'),
            'version' => CRYPTO_TREEMAP_VERSION,
            'timestamp' => current_time('mysql')
        ), 200);
    }
    
    // AJAX handlers for admin tools
    public function ajax_clear_cache() {
        if (!wp_verify_nonce($_POST['nonce'], 'crypto_treemap_admin') || !current_user_can('manage_options')) {
            wp_die(__('Security check failed', 'crypto-treemap'));
        }
        
        if ($this->api) {
            $this->api->clear_cache();
            wp_send_json_success(__('Cache cleared successfully', 'crypto-treemap'));
        } else {
            wp_send_json_error(__('API not initialized', 'crypto-treemap'));
        }
    }
    
    public function ajax_test_api() {
        if (!wp_verify_nonce($_POST['nonce'], 'crypto_treemap_admin') || !current_user_can('manage_options')) {
            wp_die(__('Security check failed', 'crypto-treemap'));
        }
        
        if ($this->api) {
            $result = $this->api->get_crypto_prices(5); // Test with 5 coins
            
            if (is_wp_error($result)) {
                wp_send_json_error(__('API test failed: ', 'crypto-treemap') . $result->get_error_message());
            } else {
                wp_send_json_success(__('API test successful. Retrieved data for ', 'crypto-treemap') . count($result['data']) . __(' cryptocurrencies.', 'crypto-treemap'));
            }
        } else {
            wp_send_json_error(__('API not initialized', 'crypto-treemap'));
        }
    }
    
    public function render_treemap($atts) {
        // Force load assets when shortcode is used
        wp_enqueue_style('crypto-treemap-style');
        wp_enqueue_script('crypto-treemap-script');
        
        // Localize script with settings
        $this->localize_script();
        
        ob_start();
        include CRYPTO_TREEMAP_PLUGIN_DIR . 'templates/treemap.php';
        return ob_get_clean();
    }
    
    private function localize_script() {
        $options = get_option('crypto_treemap_settings');
        
        $settings = array(
            'ajaxUrl' => esc_url_raw(rest_url('crypto-treemap/v1/prices')),
            'updateInterval' => (isset($options['update_interval']) ? intval($options['update_interval']) : 30) * 1000,
            'limit' => isset($options['crypto_limit']) ? intval($options['crypto_limit']) : 50,
            'redirectUrl' => isset($options['redirect_url']) ? esc_url_raw($options['redirect_url']) : '',
            'nonce' => wp_create_nonce('crypto_treemap_nonce'),
            'siteUrl' => site_url(),
            'debug' => (defined('WP_DEBUG') && WP_DEBUG) || (isset($options['enable_debug']) && $options['enable_debug']),
            'offlineMode' => isset($options['offline_mode']) ? (bool) $options['offline_mode'] : false,
            'lazyLoading' => true,
            'performanceMetrics' => (defined('WP_DEBUG') && WP_DEBUG) || (isset($options['enable_debug']) && $options['enable_debug']),
            'i18n' => array(
                'loading' => __('Loading...', 'crypto-treemap'),
                'error' => __('Error loading data', 'crypto-treemap'),
                'retry' => __('Retry', 'crypto-treemap'),
                'offline' => __('Offline mode', 'crypto-treemap'),
                'lastUpdated' => __('Last updated', 'crypto-treemap'),
                'price' => __('Price', 'crypto-treemap'),
                'change' => __('24h Change', 'crypto-treemap'),
                'volume' => __('Volume', 'crypto-treemap'),
                'marketCap' => __('Market Cap', 'crypto-treemap')
            )
        );
        
        wp_localize_script('crypto-treemap-script', 'cryptoTreemapSettings', $settings);
    }
    
    // Admin notices
    public function admin_notices() {
        $options = get_option('crypto_treemap_settings');
        
        if (empty($options['api_key'])) {
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p>' . sprintf(
                __('Crypto Treemap Widget requires a CoinMarketCap API key. <a href="%s">Configure it now</a>.', 'crypto-treemap'),
                admin_url('options-general.php?page=crypto-treemap')
            ) . '</p>';
            echo '</div>';
        }
    }
    
    // Plugin action links
    public function plugin_action_links($links) {
        $settings_link = '<a href="' . admin_url('options-general.php?page=crypto-treemap') . '">' . __('Settings', 'crypto-treemap') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
    

    // Plugin lifecycle methods
    public static function activate() {
        // Set default options
        $default_options = array(
            'update_interval' => 30,
            'crypto_limit' => 50,
            'redirect_url' => 'https://app.bitcoinp2p.com.br/',
            'allowed_origins' => '',
            'enable_debug' => false,
            'offline_mode' => false
        );
        
        $existing_options = get_option('crypto_treemap_settings', array());
        $options = wp_parse_args($existing_options, $default_options);
        update_option('crypto_treemap_settings', $options);
        
        // Create database tables if needed (for future use)
        // self::create_tables();
        
        // Flush rewrite rules
        add_option('crypto_treemap_flush_rewrite', true);
        flush_rewrite_rules(false);
    }

    public static function deactivate() {
        // Clear cache
        $api = new Crypto_Treemap_API();
        $api->clear_cache();
        
        // Clear rewrite rules
        delete_option('crypto_treemap_flush_rewrite');
        flush_rewrite_rules(false);
    }
    
    public static function uninstall() {
        // Remove all plugin data
        delete_option('crypto_treemap_settings');
        delete_option('crypto_treemap_flush_rewrite');
        
        // Clear all cache
        $api = new Crypto_Treemap_API();
        $api->clear_cache();
        
        // Remove any custom database tables if we had them
        // self::drop_tables();
    }
}

// Plugin hooks
register_activation_hook(__FILE__, array('CryptoTreemap', 'activate'));
register_deactivation_hook(__FILE__, array('CryptoTreemap', 'deactivate'));
register_uninstall_hook(__FILE__, array('CryptoTreemap', 'uninstall'));

// Initialize plugin
function crypto_treemap_init() {
    // Check minimum requirements
    if (version_compare(PHP_VERSION, '7.4', '<')) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p>';
            echo sprintf(
                __('Crypto Treemap Widget requires PHP 7.4 or higher. You are running PHP %s.', 'crypto-treemap'),
                PHP_VERSION
            );
            echo '</p></div>';
        });
        return false;
    }
    
    if (version_compare(get_bloginfo('version'), '5.0', '<')) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p>';
            echo sprintf(
                __('Crypto Treemap Widget requires WordPress 5.0 or higher. You are running WordPress %s.', 'crypto-treemap'),
                get_bloginfo('version')
            );
            echo '</p></div>';
        });
        return false;
    }
    
    return CryptoTreemap::get_instance();
}

add_action('plugins_loaded', 'crypto_treemap_init');

// Add compatibility check
function crypto_treemap_check_compatibility() {
    if (!function_exists('rest_url')) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p>';
            echo __('Crypto Treemap Widget requires WordPress REST API support.', 'crypto-treemap');
            echo '</p></div>';
        });
        return false;
    }
    
    return true;
}

add_action('admin_init', 'crypto_treemap_check_compatibility'); 