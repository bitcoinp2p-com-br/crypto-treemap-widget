<?php
/**
 * Build Script for Crypto Treemap Widget
 * 
 * This script automates common build tasks:
 * - Asset minification
 * - Version bumping
 * - Cache clearing
 * - Asset optimization
 * 
 * Usage: php build.php [task]
 * Tasks: minify, version, clean, optimize, all
 */

// Prevent direct access
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from command line.');
}

class CryptoTreemapBuilder {
    private $version = '2.0.0';
    private $plugin_dir;
    
    public function __construct() {
        $this->plugin_dir = __DIR__;
    }
    
    public function run($task = 'help') {
        echo "🚀 Crypto Treemap Widget Build Script v{$this->version}\n";
        echo "==========================================\n\n";
        
        switch ($task) {
            case 'minify':
                $this->minifyAssets();
                break;
            case 'version':
                $this->updateVersion();
                break;
            case 'clean':
                $this->cleanFiles();
                break;
            case 'optimize':
                $this->optimizeAssets();
                break;
            case 'all':
                $this->runAll();
                break;
            case 'help':
            default:
                $this->showHelp();
                break;
        }
    }
    
    private function minifyAssets() {
        echo "📦 Minifying assets...\n";
        
        // Minify CSS
        $this->minifyCSS();
        
        // Minify JavaScript
        $this->minifyJS();
        
        echo "✅ Asset minification completed!\n\n";
    }
    
    private function minifyCSS() {
        $css_file = $this->plugin_dir . '/assets/css/crypto-treemap.css';
        $min_file = $this->plugin_dir . '/assets/css/crypto-treemap.min.css';
        
        if (!file_exists($css_file)) {
            echo "⚠️  CSS file not found: {$css_file}\n";
            return;
        }
        
        $css = file_get_contents($css_file);
        
        // Remove comments
        $css = preg_replace('/\/\*.*?\*\//s', '', $css);
        
        // Remove whitespace
        $css = preg_replace('/\s+/', ' ', $css);
        
        // Remove spaces around specific characters
        $css = str_replace(array('; ', ' {', '{ ', ' }', '} ', ': ', ', '), 
                          array(';', '{', '{', '}', '}', ':', ','), $css);
        
        // Remove leading/trailing spaces
        $css = trim($css);
        
        file_put_contents($min_file, $css);
        
        $original_size = filesize($css_file);
        $minified_size = filesize($min_file);
        $saved = $original_size - $minified_size;
        $percentage = round(($saved / $original_size) * 100, 1);
        
        echo "   CSS: {$this->formatBytes($original_size)} → {$this->formatBytes($minified_size)} ({$percentage}% smaller)\n";
    }
    
    private function minifyJS() {
        $js_file = $this->plugin_dir . '/assets/js/crypto-treemap.js';
        $min_file = $this->plugin_dir . '/assets/js/crypto-treemap.min.js';
        
        if (!file_exists($js_file)) {
            echo "⚠️  JavaScript file not found: {$js_file}\n";
            return;
        }
        
        $js = file_get_contents($js_file);
        
        // Remove single line comments (but preserve URLs)
        $js = preg_replace('/(?<!:)\/\/.*$/m', '', $js);
        
        // Remove multi-line comments
        $js = preg_replace('/\/\*[\s\S]*?\*\//', '', $js);
        
        // Remove extra whitespace
        $js = preg_replace('/\s+/', ' ', $js);
        
        // Remove spaces around operators and punctuation
        $replacements = array(
            ' {' => '{', '{ ' => '{', ' }' => '}', '} ' => '}',
            ' (' => '(', '( ' => '(', ' )' => ')', ') ' => ')',
            ' ;' => ';', '; ' => ';', ' ,' => ',', ', ' => ',',
            ' =' => '=', '= ' => '=', ' +' => '+', '+ ' => '+',
            ' -' => '-', '- ' => '-', ' *' => '*', '* ' => '*',
            ' /' => '/', '/ ' => '/', ' <' => '<', '< ' => '<',
            ' >' => '>', '> ' => '>', ' !' => '!', '! ' => '!',
            ' &' => '&', '& ' => '&', ' |' => '|', '| ' => '|'
        );
        
        $js = str_replace(array_keys($replacements), array_values($replacements), $js);
        
        // Remove leading/trailing spaces
        $js = trim($js);
        
        file_put_contents($min_file, $js);
        
        $original_size = filesize($js_file);
        $minified_size = filesize($min_file);
        $saved = $original_size - $minified_size;
        $percentage = round(($saved / $original_size) * 100, 1);
        
        echo "   JS:  {$this->formatBytes($original_size)} → {$this->formatBytes($minified_size)} ({$percentage}% smaller)\n";
    }
    
    private function updateVersion() {
        echo "🔢 Updating version numbers...\n";
        
        // Update main plugin file
        $this->updateVersionInFile(
            $this->plugin_dir . '/crypto-treemap.php',
            array(
                '/Version: ([\d\.]+)/' => "Version: {$this->version}",
                "/define\('CRYPTO_TREEMAP_VERSION', '([\d\.]+)'\)/" => "define('CRYPTO_TREEMAP_VERSION', '{$this->version}')"
            )
        );
        
        // Update README
        $this->updateVersionInFile(
            $this->plugin_dir . '/README.md',
            array(
                '/# Crypto Treemap Widget v([\d\.]+)/' => "# Crypto Treemap Widget v{$this->version}"
            )
        );
        
        echo "✅ Version updated to {$this->version}!\n\n";
    }
    
    private function updateVersionInFile($file, $patterns) {
        if (!file_exists($file)) {
            echo "⚠️  File not found: {$file}\n";
            return;
        }
        
        $content = file_get_contents($file);
        
        foreach ($patterns as $pattern => $replacement) {
            $content = preg_replace($pattern, $replacement, $content);
        }
        
        file_put_contents($file, $content);
        echo "   Updated: " . basename($file) . "\n";
    }
    
    private function cleanFiles() {
        echo "🧹 Cleaning temporary files...\n";
        
        $patterns = array(
            '*.tmp',
            '*.log',
            '*~',
            '.DS_Store',
            'Thumbs.db'
        );
        
        $cleaned = 0;
        
        foreach ($patterns as $pattern) {
            $files = glob($this->plugin_dir . '/**/' . $pattern, GLOB_BRACE);
            foreach ($files as $file) {
                if (unlink($file)) {
                    $cleaned++;
                    echo "   Removed: " . basename($file) . "\n";
                }
            }
        }
        
        echo "✅ Cleaned {$cleaned} files!\n\n";
    }
    
    private function optimizeAssets() {
        echo "⚡ Optimizing assets...\n";
        
        // Check if D3.js is downloaded
        $d3_file = $this->plugin_dir . '/assets/js/d3.v7.min.js';
        if (!file_exists($d3_file)) {
            echo "   Downloading D3.js...\n";
            $d3_content = file_get_contents('https://d3js.org/d3.v7.min.js');
            if ($d3_content) {
                file_put_contents($d3_file, $d3_content);
                echo "   ✅ D3.js downloaded\n";
            } else {
                echo "   ⚠️  Failed to download D3.js\n";
            }
        } else {
            echo "   ✅ D3.js already present\n";
        }
        
        // Validate asset integrity
        $this->validateAssets();
        
        echo "✅ Asset optimization completed!\n\n";
    }
    
    private function validateAssets() {
        $required_files = array(
            'assets/css/crypto-treemap.css',
            'assets/css/crypto-treemap.min.css',
            'assets/js/crypto-treemap.js',
            'assets/js/crypto-treemap.min.js',
            'assets/js/d3.v7.min.js'
        );
        
        echo "   Validating assets...\n";
        
        foreach ($required_files as $file) {
            $full_path = $this->plugin_dir . '/' . $file;
            if (file_exists($full_path)) {
                $size = $this->formatBytes(filesize($full_path));
                echo "   ✅ {$file} ({$size})\n";
            } else {
                echo "   ❌ {$file} - Missing!\n";
            }
        }
    }
    
    private function runAll() {
        echo "🚀 Running all build tasks...\n\n";
        
        $this->cleanFiles();
        $this->optimizeAssets();
        $this->minifyAssets();
        $this->updateVersion();
        
        echo "🎉 Build completed successfully!\n";
        echo "📦 Plugin is ready for distribution.\n\n";
        
        // Show summary
        $this->showSummary();
    }
    
    private function showSummary() {
        echo "📊 Build Summary:\n";
        echo "================\n";
        echo "Version: {$this->version}\n";
        echo "Plugin Directory: {$this->plugin_dir}\n";
        
        // Calculate total size
        $total_size = 0;
        $files = glob($this->plugin_dir . '/**/*', GLOB_BRACE);
        foreach ($files as $file) {
            if (is_file($file)) {
                $total_size += filesize($file);
            }
        }
        
        echo "Total Size: {$this->formatBytes($total_size)}\n";
        echo "Build Date: " . date('Y-m-d H:i:s') . "\n\n";
    }
    
    private function showHelp() {
        echo "Available tasks:\n";
        echo "================\n";
        echo "  minify    - Minify CSS and JavaScript files\n";
        echo "  version   - Update version numbers in files\n";
        echo "  clean     - Remove temporary files\n";
        echo "  optimize  - Optimize assets and download dependencies\n";
        echo "  all       - Run all tasks\n";
        echo "  help      - Show this help message\n\n";
        
        echo "Usage:\n";
        echo "  php build.php [task]\n\n";
        
        echo "Examples:\n";
        echo "  php build.php minify    # Minify assets only\n";
        echo "  php build.php all       # Run complete build\n\n";
    }
    
    private function formatBytes($bytes, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}

// Run the builder
$task = isset($argv[1]) ? $argv[1] : 'help';
$builder = new CryptoTreemapBuilder();
$builder->run($task);
?>