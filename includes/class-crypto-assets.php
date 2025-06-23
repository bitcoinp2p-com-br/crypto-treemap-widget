<?php
/**
 * Assets Management for Crypto Treemap Widget
 */

if (!defined('ABSPATH')) {
    exit;
}

class Crypto_Treemap_Assets {
    
    /**
     * Initialize assets management
     */
    public static function init() {
        add_action('wp_enqueue_scripts', array(__CLASS__, 'register_assets'));
        add_action('wp_footer', array(__CLASS__, 'maybe_enqueue_assets'));
        add_action('wp_head', array(__CLASS__, 'add_preload_hints'));
    }
    
    /**
     * Register all assets
     */
    public static function register_assets() {
        // Register D3.js locally for better performance and security
        wp_register_script(
            'crypto-d3-local',
            CRYPTO_TREEMAP_PLUGIN_URL . 'assets/js/d3.v7.min.js',
            array(),
            '7.8.5',
            true
        );
        
        // Register main CSS with proper versioning
        wp_register_style(
            'crypto-treemap-style',
            CRYPTO_TREEMAP_PLUGIN_URL . 'assets/css/crypto-treemap.min.css',
            array(),
            CRYPTO_TREEMAP_VERSION . '-' . self::get_css_hash()
        );
        
        // Register main JavaScript
        wp_register_script(
            'crypto-treemap-script',
            CRYPTO_TREEMAP_PLUGIN_URL . 'assets/js/crypto-treemap.min.js',
            array('jquery', 'crypto-d3-local'),
            CRYPTO_TREEMAP_VERSION . '-' . self::get_js_hash(),
            true
        );
        
        // Add critical CSS inline for better performance
        self::add_critical_css();
    }
    
    /**
     * Conditionally enqueue assets only when needed
     */
    public static function maybe_enqueue_assets() {
        global $post;
        
        $should_load = false;
        
        // Check if shortcode is present in current post
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'crypto_treemap')) {
            $should_load = true;
        }
        
        // Check if shortcode is present in any content (for page builders, widgets, etc)
        if (!$should_load) {
            global $wp_query;
            if (isset($wp_query->posts) && is_array($wp_query->posts)) {
                foreach ($wp_query->posts as $post_obj) {
                    if (has_shortcode($post_obj->post_content, 'crypto_treemap')) {
                        $should_load = true;
                        break;
                    }
                }
            }
        }
        
        // Check if widget is active (for future widget implementation)
        if (is_active_widget(false, false, 'crypto_treemap_widget')) {
            $should_load = true;
        }
        
        // Force load on admin pages for testing
        if (is_admin() && isset($_GET['page']) && $_GET['page'] === 'crypto-treemap') {
            $should_load = true;
        }
        
        // Allow other plugins/themes to force loading
        $should_load = apply_filters('crypto_treemap_should_load_assets', $should_load);
        
        if (!$should_load) {
            return;
        }
        
        // Enqueue assets with lazy loading for non-critical resources
        wp_enqueue_style('crypto-treemap-style');
        wp_enqueue_script('crypto-treemap-script');
        
        // Localize script with settings
        self::localize_script();
    }
    
    /**
     * Add preload hints for better performance
     */
    public static function add_preload_hints() {
        global $post;
        
        if (!is_a($post, 'WP_Post') || !has_shortcode($post->post_content, 'crypto_treemap')) {
            return;
        }
        
        // Preload critical assets
        echo '<link rel="preload" href="' . esc_url(CRYPTO_TREEMAP_PLUGIN_URL . 'assets/css/crypto-treemap.min.css') . '" as="style">' . "\n";
        echo '<link rel="preload" href="' . esc_url(CRYPTO_TREEMAP_PLUGIN_URL . 'assets/js/crypto-treemap.min.js') . '" as="script">' . "\n";
        echo '<link rel="preload" href="' . esc_url(CRYPTO_TREEMAP_PLUGIN_URL . 'assets/js/d3.v7.min.js') . '" as="script">' . "\n";
        
        // DNS prefetch for API
        echo '<link rel="dns-prefetch" href="//pro-api.coinmarketcap.com">' . "\n";
    }
    
    /**
     * Add critical CSS inline
     */
    private static function add_critical_css() {
        $critical_css = '
        .crypto-treemap-container{width:100%;max-width:100%;margin:0 auto;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif}
        .crypto-treemap{width:100%;height:600px;position:relative;background:#fff;border-radius:8px;overflow:hidden}
        .crypto-treemap-loading{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);padding:20px;text-align:center;background:rgba(248,248,248,0.9);border-radius:8px;z-index:100}
        ';
        
        wp_add_inline_style('crypto-treemap-style', $critical_css);
    }
    
    /**
     * Localize script with settings and configuration
     */
    private static function localize_script() {
        $options = get_option('crypto_treemap_settings');
        
        $settings = array(
            'ajaxUrl' => esc_url_raw(rest_url('crypto-treemap/v1/prices')),
            'updateInterval' => (isset($options['update_interval']) ? intval($options['update_interval']) : 30) * 1000,
            'limit' => isset($options['crypto_limit']) ? intval($options['crypto_limit']) : 50,
            'redirectUrl' => isset($options['redirect_url']) ? esc_url_raw($options['redirect_url']) : '',
            'nonce' => wp_create_nonce('crypto_treemap_nonce'),
            'siteUrl' => site_url(),
            'debug' => self::is_debug_mode(),
            'offlineMode' => isset($options['offline_mode']) ? (bool) $options['offline_mode'] : false,
            'lazyLoading' => true,
            'performanceMetrics' => self::is_debug_mode(),
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
    
    /**
     * Get CSS file hash for cache busting
     */
    private static function get_css_hash() {
        $css_file = CRYPTO_TREEMAP_PLUGIN_DIR . 'assets/css/crypto-treemap.min.css';
        return file_exists($css_file) ? substr(md5_file($css_file), 0, 8) : '0';
    }
    
    /**
     * Get JS file hash for cache busting
     */
    private static function get_js_hash() {
        $js_file = CRYPTO_TREEMAP_PLUGIN_DIR . 'assets/js/crypto-treemap.min.js';
        return file_exists($js_file) ? substr(md5_file($js_file), 0, 8) : '0';
    }
    
    /**
     * Check if debug mode is enabled
     */
    private static function is_debug_mode() {
        $options = get_option('crypto_treemap_settings');
        return (defined('WP_DEBUG') && WP_DEBUG) || (isset($options['enable_debug']) && $options['enable_debug']);
    }
    
    /**
     * Generate Service Worker for offline functionality
     */
    public static function generate_service_worker() {
        $sw_content = "
const CACHE_NAME = 'crypto-treemap-v" . CRYPTO_TREEMAP_VERSION . "';
const urlsToCache = [
    '" . CRYPTO_TREEMAP_PLUGIN_URL . "assets/css/crypto-treemap.min.css',
    '" . CRYPTO_TREEMAP_PLUGIN_URL . "assets/js/crypto-treemap.min.js',
    '" . CRYPTO_TREEMAP_PLUGIN_URL . "assets/js/d3.v7.min.js'
];

self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => cache.addAll(urlsToCache))
    );
});

self.addEventListener('fetch', event => {
    event.respondWith(
        caches.match(event.request)
            .then(response => {
                if (response) {
                    return response;
                }
                return fetch(event.request);
            })
    );
});
        ";
        
        return $sw_content;
    }
    
    /**
     * Optimize assets (minify, compress)
     */
    public static function optimize_assets() {
        // This would typically be done during build process
        // For now, we'll create minified versions
        
        $css_file = CRYPTO_TREEMAP_PLUGIN_DIR . 'assets/css/crypto-treemap.css';
        $js_file = CRYPTO_TREEMAP_PLUGIN_DIR . 'assets/js/crypto-treemap.js';
        
        if (file_exists($css_file)) {
            $css_content = file_get_contents($css_file);
            $minified_css = self::minify_css($css_content);
            file_put_contents(CRYPTO_TREEMAP_PLUGIN_DIR . 'assets/css/crypto-treemap.min.css', $minified_css);
        }
        
        if (file_exists($js_file)) {
            $js_content = file_get_contents($js_file);
            $minified_js = self::minify_js($js_content);
            file_put_contents(CRYPTO_TREEMAP_PLUGIN_DIR . 'assets/js/crypto-treemap.min.js', $minified_js);
        }
    }
    
    /**
     * Simple CSS minification
     */
    private static function minify_css($css) {
        // Remove comments
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        
        // Remove whitespace
        $css = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), '', $css);
        
        return $css;
    }
    
    /**
     * Simple JavaScript minification
     */
    private static function minify_js($js) {
        // Remove single line comments
        $js = preg_replace('/\/\/.*$/m', '', $js);
        
        // Remove multi-line comments
        $js = preg_replace('/\/\*[\s\S]*?\*\//', '', $js);
        
        // Remove extra whitespace
        $js = preg_replace('/\s+/', ' ', $js);
        
        return trim($js);
    }
}