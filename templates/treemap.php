<?php
if (!defined('ABSPATH')) {
    exit;
}

// Get plugin settings
$options = get_option('crypto_treemap_settings');
$limit = isset($options['crypto_limit']) ? intval($options['crypto_limit']) : 50;
$debug_mode = isset($options['enable_debug']) ? (bool) $options['enable_debug'] : false;
$offline_mode = isset($options['offline_mode']) ? (bool) $options['offline_mode'] : false;

// Generate unique ID for this instance
$instance_id = 'crypto-treemap-' . wp_generate_uuid4();
?>
<div class="crypto-treemap-wrapper">
    <div id="<?php echo esc_attr($instance_id); ?>-container" class="crypto-treemap-container" role="main" aria-live="polite">
        <header class="crypto-treemap-header">
            <h2 class="crypto-treemap-title">
                <?php printf('TOP %d CRIPTOMOEDAS EM BRL', $limit); ?>
            </h2>
            <div class="crypto-treemap-controls" aria-label="<?php _e('Widget controls', 'crypto-treemap'); ?>">
                <?php if ($debug_mode): ?>
                    <button type="button" class="crypto-refresh-btn" aria-label="<?php _e('Refresh data', 'crypto-treemap'); ?>">
                        <?php _e('Refresh', 'crypto-treemap'); ?>
                    </button>
                <?php endif; ?>
            </div>
        </header>
        
        <div class="crypto-treemap-meta">
            <div class="crypto-treemap-updated">
                Última atualização: 
                <time id="<?php echo esc_attr($instance_id); ?>-timestamp" datetime="<?php echo esc_attr(current_time('c')); ?>">
                    <?php echo esc_html(current_time('d/m/Y H:i:s')); ?>
                </time>
            </div>
            
            <?php if ($offline_mode): ?>
                <div class="crypto-offline-indicator" aria-label="<?php _e('Offline mode active', 'crypto-treemap'); ?>">
                    <span class="crypto-offline-icon" aria-hidden="true">⚡</span>
                    <?php _e('Offline Mode', 'crypto-treemap'); ?>
                </div>
            <?php endif; ?>
        </div>
        
        <main id="<?php echo esc_attr($instance_id); ?>" class="crypto-treemap" 
              role="img" 
              aria-label="<?php printf(__('Interactive treemap showing %d cryptocurrencies', 'crypto-treemap'), $limit); ?>"
              tabindex="0">
            
            <!-- Loading state -->
            <div class="crypto-treemap-loading" role="status" aria-live="polite">
                <div class="crypto-spinner" aria-hidden="true"></div>
                <span class="sr-only"><?php _e('Loading cryptocurrency data...', 'crypto-treemap'); ?></span>
                <?php _e('Loading data...', 'crypto-treemap'); ?>
            </div>
            
            <!-- Error state template (hidden by default) -->
            <div class="crypto-treemap-error" role="alert" aria-live="assertive" style="display: none;">
                <h3><?php _e('Error Loading Data', 'crypto-treemap'); ?></h3>
                <p class="crypto-error-message"></p>
                <button type="button" class="crypto-retry-btn">
                    <?php _e('Try Again', 'crypto-treemap'); ?>
                </button>
            </div>
            
            <!-- Accessibility description -->
            <div class="sr-only" id="<?php echo esc_attr($instance_id); ?>-description">
                <?php _e('This treemap displays cryptocurrency market data. Each rectangle represents a cryptocurrency, with size proportional to market capitalization and color indicating price change. Use tab and arrow keys to navigate.', 'crypto-treemap'); ?>
            </div>
        </main>
        
        <?php if ($debug_mode): ?>
            <footer class="crypto-treemap-footer">
                <div class="crypto-debug-info">
                    <small>Debug Mode | Instance: <?php echo esc_html(substr($instance_id, -8)); ?></small>
                </div>
            </footer>
        <?php endif; ?>
    </div>
</div> 