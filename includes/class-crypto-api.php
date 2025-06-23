<?php
/**
 * API Handler for CoinMarketCap integration
 */

if (!defined('ABSPATH')) {
    exit;
}

class Crypto_Treemap_API {
    private $api_key;
    private $cache_prefix = 'crypto_treemap_';
    private $rate_limit_key = 'crypto_treemap_rate_limit';
    private $max_requests_per_minute = 30;
    
    public function __construct($api_key = '') {
        $this->api_key = $api_key;
    }
    
    /**
     * Get cryptocurrency prices with enhanced caching and rate limiting
     */
    public function get_crypto_prices($limit = 50) {
        // Validate inputs
        $limit = $this->validate_limit($limit);
        
        if (empty($this->api_key)) {
            return $this->get_demo_data($limit);
        }
        
        // Check rate limiting
        if (!$this->check_rate_limit()) {
            return new WP_Error('rate_limit', __('Rate limit exceeded', 'crypto-treemap'));
        }
        
        // Check cache first
        $cache_key = $this->cache_prefix . 'prices_' . $limit;
        $cached_data = $this->get_cached_data($cache_key);
        
        if ($cached_data !== false) {
            return $cached_data;
        }
        
        // Fetch fresh data from API
        $data = $this->fetch_from_coinmarketcap($limit);
        
        if (is_wp_error($data)) {
            return $data;
        }
        
        // Cache the results
        $this->cache_data($cache_key, $data);
        
        // Add timestamp to ensure data appears fresh
        if (isset($data['status'])) {
            $data['status']['timestamp'] = current_time('c');
        }
        
        return $data;
    }
    
    /**
     * Validate limit parameter
     */
    private function validate_limit($limit) {
        $limit = absint($limit);
        
        // Ensure limit is within acceptable range
        if ($limit < 1) {
            $limit = 10;
        } elseif ($limit > 100) {
            $limit = 100;
        }
        
        return $limit;
    }
    
    /**
     * Check rate limiting
     */
    private function check_rate_limit() {
        $current_requests = get_transient($this->rate_limit_key);
        
        if ($current_requests === false) {
            set_transient($this->rate_limit_key, 1, 60); // 1 minute
            return true;
        }
        
        if ($current_requests >= $this->max_requests_per_minute) {
            return false;
        }
        
        set_transient($this->rate_limit_key, $current_requests + 1, 60);
        return true;
    }
    
    /**
     * Get cached data with timestamp validation
     */
    private function get_cached_data($cache_key) {
        $cached = get_transient($cache_key);
        
        if ($cached === false) {
            return false;
        }
        
        $data = maybe_unserialize($cached);
        
        // Validate cache structure
        if (!is_array($data) || !isset($data['timestamp']) || !isset($data['data'])) {
            delete_transient($cache_key);
            return false;
        }
        
        // Check if cache is still valid (not older than configured interval)
        $options = get_option('crypto_treemap_settings');
        $cache_duration = isset($options['update_interval']) ? intval($options['update_interval']) : 30;
        
        // Force refresh if cache is older than update interval
        if (time() - $data['timestamp'] >= $cache_duration) {
            delete_transient($cache_key);
            return false;
        }
        
        return $data['data'];
    }
    
    /**
     * Cache data with timestamp
     */
    private function cache_data($cache_key, $data) {
        $options = get_option('crypto_treemap_settings');
        $cache_duration = isset($options['update_interval']) ? intval($options['update_interval']) : 30;
        
        $cache_data = array(
            'timestamp' => time(),
            'data' => $data
        );
        
        set_transient($cache_key, maybe_serialize($cache_data), $cache_duration + 10); // Add 10 seconds buffer
    }
    
    /**
     * Fetch data from CoinMarketCap API
     */
    private function fetch_from_coinmarketcap($limit) {
        $url = 'https://pro-api.coinmarketcap.com/v1/cryptocurrency/listings/latest';
        
        $args = array(
            'start' => '1',
            'limit' => $limit,
            'convert' => 'BRL',
            'sort' => 'market_cap',
            'sort_dir' => 'desc'
        );
        
        $api_url = add_query_arg($args, $url);
        
        $response = wp_remote_get($api_url, array(
            'headers' => array(
                'X-CMC_PRO_API_KEY' => $this->api_key,
                'Accept' => 'application/json'
            ),
            'timeout' => 30,
            'user-agent' => 'CryptoTreemapWidget/2.0 WordPress/' . get_bloginfo('version')
        ));
        
        if (is_wp_error($response)) {
            $this->log_error('API request failed: ' . $response->get_error_message());
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            $error_message = sprintf('API returned status code: %d', $response_code);
            $this->log_error($error_message);
            return new WP_Error('api_error', $error_message);
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $error_message = 'JSON decode error: ' . json_last_error_msg();
            $this->log_error($error_message);
            return new WP_Error('json_error', $error_message);
        }
        
        if (!isset($data['data']) || !is_array($data['data'])) {
            $error_message = 'Invalid API response structure';
            $this->log_error($error_message);
            return new WP_Error('invalid_response', $error_message);
        }
        
        return $this->format_response_data($data);
    }
    
    /**
     * Format API response data
     */
    private function format_response_data($raw_data) {
        $formatted_data = array(
            'status' => array(
                'timestamp' => current_time('c'),
                'error_code' => 0,
                'error_message' => null,
                'credit_count' => 1
            ),
            'data' => array()
        );
        
        foreach ($raw_data['data'] as $coin) {
            if (!isset($coin['quote']['BRL'])) {
                continue;
            }
            
            $quote = $coin['quote']['BRL'];
            
            $formatted_coin = array(
                'id' => intval($coin['id']),
                'name' => sanitize_text_field($coin['name']),
                'symbol' => sanitize_text_field($coin['symbol']),
                'slug' => sanitize_text_field($coin['slug']),
                'price' => floatval($quote['price']),
                'volume_24h' => floatval($quote['volume_24h']),
                'percent_change_24h' => floatval($quote['percent_change_24h']),
                'market_cap' => floatval($quote['market_cap']),
                'last_updated' => sanitize_text_field($quote['last_updated'])
            );
            
            $formatted_data['data'][] = $formatted_coin;
        }
        
        // Sort by market cap descending
        usort($formatted_data['data'], function($a, $b) {
            return $b['market_cap'] - $a['market_cap'];
        });
        
        return $formatted_data;
    }
    
    /**
     * Log errors only in debug mode
     */
    private function log_error($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Crypto Treemap API] ' . $message);
        }
    }
    
    /**
     * Clear all cache
     */
    public function clear_cache() {
        global $wpdb;
        
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_' . $this->cache_prefix . '%'
            )
        );
        
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_timeout_' . $this->cache_prefix . '%'
            )
        );
    }
    
    /**
     * Get cache statistics
     */
    public function get_cache_stats() {
        global $wpdb;
        
        $cache_count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_' . $this->cache_prefix . '%'
            )
        );
        
        return array(
            'cached_entries' => intval($cache_count),
            'cache_prefix' => $this->cache_prefix
        );
    }
}