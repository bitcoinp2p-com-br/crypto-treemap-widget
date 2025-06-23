<?php
/**
 * Security Handler for Crypto Treemap Widget
 */

if (!defined('ABSPATH')) {
    exit;
}

class Crypto_Treemap_Security {
    
    /**
     * Setup security measures
     */
    public static function init() {
        add_action('rest_api_init', array(__CLASS__, 'setup_cors_headers'));
        add_filter('crypto_treemap_validate_request', array(__CLASS__, 'validate_request'), 10, 2);
    }
    
    /**
     * Setup restrictive CORS headers
     */
    public static function setup_cors_headers() {
        add_filter('rest_pre_serve_request', function($value) {
            $allowed_origins = self::get_allowed_origins();
            $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
            
            if (in_array($origin, $allowed_origins) || self::is_same_domain($origin)) {
                header('Access-Control-Allow-Origin: ' . $origin);
            }
            
            header('Access-Control-Allow-Methods: GET, OPTIONS');
            header('Access-Control-Allow-Credentials: false');
            header('Access-Control-Allow-Headers: Accept, Content-Type, X-Requested-With');
            header('Access-Control-Max-Age: 86400'); // 24 hours
            
            return $value;
        });
    }
    
    /**
     * Get allowed origins for CORS
     */
    private static function get_allowed_origins() {
        $site_url = site_url();
        $home_url = home_url();
        
        $allowed = array(
            $site_url,
            $home_url
        );
        
        // Remove duplicates and empty values
        $allowed = array_filter(array_unique($allowed));
        
        // Allow additional origins from settings
        $options = get_option('crypto_treemap_settings');
        if (isset($options['allowed_origins']) && !empty($options['allowed_origins'])) {
            $additional = explode("\n", $options['allowed_origins']);
            $additional = array_map('trim', $additional);
            $additional = array_filter($additional);
            $allowed = array_merge($allowed, $additional);
        }
        
        return apply_filters('crypto_treemap_allowed_origins', $allowed);
    }
    
    /**
     * Check if origin is from same domain
     */
    private static function is_same_domain($origin) {
        if (empty($origin)) {
            return false;
        }
        
        $site_host = parse_url(site_url(), PHP_URL_HOST);
        $origin_host = parse_url($origin, PHP_URL_HOST);
        
        return $site_host === $origin_host;
    }
    
    /**
     * Validate API request
     */
    public static function validate_request(WP_REST_Request $request, $endpoint = '') {
        // Rate limiting check
        if (!self::check_rate_limit($request)) {
            return new WP_Error(
                'rate_limit_exceeded',
                __('Too many requests. Please try again later.', 'crypto-treemap'),
                array('status' => 429)
            );
        }
        
        // Validate referer for additional security
        if (!self::validate_referer($request)) {
            return new WP_Error(
                'invalid_referer',
                __('Invalid request origin.', 'crypto-treemap'),
                array('status' => 403)
            );
        }
        
        // Validate user agent (block known bad bots)
        if (!self::validate_user_agent($request)) {
            return new WP_Error(
                'blocked_user_agent',
                __('Access denied.', 'crypto-treemap'),
                array('status' => 403)
            );
        }
        
        return true;
    }
    
    /**
     * Check rate limiting per IP
     */
    private static function check_rate_limit(WP_REST_Request $request) {
        $ip = self::get_client_ip();
        $rate_limit_key = 'crypto_treemap_rate_limit_' . md5($ip);
        
        $requests = get_transient($rate_limit_key);
        $max_requests_per_minute = apply_filters('crypto_treemap_rate_limit', 60);
        
        if ($requests === false) {
            set_transient($rate_limit_key, 1, 60);
            return true;
        }
        
        if ($requests >= $max_requests_per_minute) {
            return false;
        }
        
        set_transient($rate_limit_key, $requests + 1, 60);
        return true;
    }
    
    /**
     * Validate referer
     */
    private static function validate_referer(WP_REST_Request $request) {
        $referer = $request->get_header('referer');
        
        if (empty($referer)) {
            // Allow empty referer for direct API access
            return true;
        }
        
        $allowed_domains = self::get_allowed_origins();
        
        foreach ($allowed_domains as $domain) {
            if (strpos($referer, $domain) === 0) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Validate user agent
     */
    private static function validate_user_agent(WP_REST_Request $request) {
        $user_agent = $request->get_header('user_agent');
        
        if (empty($user_agent)) {
            return false;
        }
        
        // Block known malicious user agents
        $blocked_agents = array(
            'sqlmap',
            'nikto',
            'dirbuster',
            'nessus',
            'openvas',
            'masscan',
            'nmap'
        );
        
        $user_agent_lower = strtolower($user_agent);
        
        foreach ($blocked_agents as $blocked) {
            if (strpos($user_agent_lower, $blocked) !== false) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Get client IP address
     */
    private static function get_client_ip() {
        $ip_keys = array(
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        );
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                $ip = $_SERVER[$key];
                
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                
                $ip = trim($ip);
                
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
    }
    
    /**
     * Sanitize and validate settings input
     */
    public static function sanitize_settings($input) {
        $sanitized = array();
        
        // API Key
        if (isset($input['api_key'])) {
            $sanitized['api_key'] = sanitize_text_field($input['api_key']);
        }
        
        // Update Interval
        if (isset($input['update_interval'])) {
            $interval = absint($input['update_interval']);
            $sanitized['update_interval'] = max(5, min(3600, $interval)); // Between 5 seconds and 1 hour
        }
        
        // Crypto Limit
        if (isset($input['crypto_limit'])) {
            $limit = absint($input['crypto_limit']);
            $allowed_limits = array(10, 20, 50, 100);
            $sanitized['crypto_limit'] = in_array($limit, $allowed_limits) ? $limit : 50;
        }
        
        // Redirect URL
        if (isset($input['redirect_url'])) {
            $url = esc_url_raw($input['redirect_url']);
            $sanitized['redirect_url'] = filter_var($url, FILTER_VALIDATE_URL) ? $url : '';
        }
        
        // Allowed Origins
        if (isset($input['allowed_origins'])) {
            $origins = sanitize_textarea_field($input['allowed_origins']);
            $origins_array = explode("\n", $origins);
            $valid_origins = array();
            
            foreach ($origins_array as $origin) {
                $origin = trim($origin);
                if (filter_var($origin, FILTER_VALIDATE_URL)) {
                    $valid_origins[] = $origin;
                }
            }
            
            $sanitized['allowed_origins'] = implode("\n", $valid_origins);
        }
        
        // Enable Debug
        if (isset($input['enable_debug'])) {
            $sanitized['enable_debug'] = (bool) $input['enable_debug'];
        }
        
        // Offline Mode
        if (isset($input['offline_mode'])) {
            $sanitized['offline_mode'] = (bool) $input['offline_mode'];
        }
        
        return $sanitized;
    }
    
    /**
     * Log security events
     */
    public static function log_security_event($event, $details = array()) {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }
        
        $log_entry = array(
            'timestamp' => current_time('mysql'),
            'event' => $event,
            'ip' => self::get_client_ip(),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
            'details' => $details
        );
        
        error_log('[Crypto Treemap Security] ' . json_encode($log_entry));
    }
}