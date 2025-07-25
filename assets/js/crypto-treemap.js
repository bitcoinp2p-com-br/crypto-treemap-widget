jQuery(document).ready(function($) {
    'use strict';
    
    // Performance metrics
    var performanceMetrics = {
        startTime: performance.now(),
        apiCalls: 0,
        renderTimes: [],
        errors: []
    };
    
    // Offline support
    var offlineData = null;
    var isOnline = navigator.onLine;
    
    // Lazy loading and intersection observer
    var observer = null;
    var isVisible = false;
    
    // Debounced resize handler
    var resizeTimeout = null;
    
    // Cache for formatted values
    var formatCache = new Map();
    
    // Current treemap instance
    var currentTreemap = null;
    
    // Initialize
    init();
    
    function init() {
        console.log('[Crypto Treemap] Starting initialization...');
        console.log('[Crypto Treemap] Settings:', typeof cryptoTreemapSettings !== 'undefined' ? cryptoTreemapSettings : 'undefined');
        
        setupOfflineSupport();
        setupIntersectionObserver();
        setupEventListeners();
        
        if (typeof cryptoTreemapSettings !== 'undefined' && cryptoTreemapSettings.performanceMetrics) {
            console.log('[Crypto Treemap] Initialized in', performance.now() - performanceMetrics.startTime, 'ms');
        }
    }
    
    // Setup offline support
    function setupOfflineSupport() {
        window.addEventListener('online', function() {
            isOnline = true;
            updateConnectionStatus();
            if (isVisible) loadCryptoData();
        });
        
        window.addEventListener('offline', function() {
            isOnline = false;
            updateConnectionStatus();
        });
        
        // Load offline data from localStorage
        try {
            var stored = localStorage.getItem('crypto_treemap_offline');
            if (stored) {
                offlineData = JSON.parse(stored);
            }
        } catch (e) {
            console.warn('[Crypto Treemap] Failed to load offline data:', e);
        }
    }
    
    // Setup intersection observer for lazy loading
    function setupIntersectionObserver() {
        console.log('[Crypto Treemap] Setting up intersection observer...');
        
        var containers = document.querySelectorAll('.crypto-treemap');
        console.log('[Crypto Treemap] Found', containers.length, 'treemap containers');
        
        if (!window.IntersectionObserver) {
            // Fallback for browsers without IntersectionObserver
            console.log('[Crypto Treemap] No IntersectionObserver, loading immediately');
            isVisible = true;
            loadCryptoData();
            return;
        }
        
        if (containers.length === 0) {
            // No containers found, try again in a moment
            console.log('[Crypto Treemap] No containers found, retrying in 1 second...');
            setTimeout(setupIntersectionObserver, 1000);
            return;
        }
        
        observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                console.log('[Crypto Treemap] Intersection observer triggered, isIntersecting:', entry.isIntersecting);
                if (entry.isIntersecting && !isVisible) {
                    isVisible = true;
                    loadCryptoData();
                    observer.unobserve(entry.target);
                }
            });
        }, {
            rootMargin: '50px'
        });
        
        containers.forEach(function(container) {
            if (container) {
                console.log('[Crypto Treemap] Observing container:', container);
                observer.observe(container);
            }
        });
    }
    
    // Setup event listeners
    function setupEventListeners() {
        // Resize handler with debouncing
        $(window).on('resize', debounce(function() {
            if (isVisible && currentTreemap) {
                currentTreemap = null;
                loadCryptoData();
            }
        }, 300));
        
        // Visibility change handler
        document.addEventListener('visibilitychange', function() {
            if (!document.hidden && isVisible && isOnline) {
                loadCryptoData();
            }
        });
    }
    
    // Debounce function
    function debounce(func, wait) {
        return function executedFunction() {
            var context = this;
            var args = arguments;
            
            var later = function() {
                resizeTimeout = null;
                func.apply(context, args);
            };
            
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(later, wait);
        };
    }
    
    // Enhanced number formatting with caching
    function formatBRL(number, decimals) {
        if (isNaN(number) || number === null) number = 0;
        decimals = decimals || 2;
        
        var cacheKey = number + '_' + decimals;
        if (formatCache.has(cacheKey)) {
            return formatCache.get(cacheKey);
        }
        
        var formatted = 'R$ ' + number.toLocaleString('pt-BR', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        });
        
        formatCache.set(cacheKey, formatted);
        return formatted;
    }

    // Função para formatar volume com sufixos
    function formatVolume(volume) {
        if (volume >= 1e9) {
            return 'R$ ' + (volume / 1e9).toFixed(2) + ' bi';
        } else if (volume >= 1e6) {
            return 'R$ ' + (volume / 1e6).toFixed(2) + ' mi';
        } else if (volume >= 1e3) {
            return 'R$ ' + (volume / 1e3).toFixed(2) + ' mil';
        } else {
            return 'R$ ' + volume.toFixed(2);
        }
    }

    // Função para ajustar o tamanho da fonte com base no tamanho do container
    function getFontSize(areaSize, baseSize) {
        // Tamanhos mínimos e máximos
        var minSize = baseSize * 0.6;
        var maxSize = baseSize * 1.6;
        
        // Verificar se estamos em um dispositivo móvel
        var isMobile = window.innerWidth <= 768;
        if (isMobile) {
            // Reduzir ainda mais em dispositivos móveis
            maxSize = baseSize * 1.2;
        }
        
        // Ajustar tamanho com base na área
        var size = Math.max(minSize, Math.min(maxSize, baseSize * Math.sqrt(areaSize / 7000)));
        
        return Math.round(size) + 'px';
    }

    // Função para renderizar o treemap usando D3.js
    function renderTreemap(data, container) {
        if (!data || !data.data || !Array.isArray(data.data) || data.data.length === 0) {
            container.html('<div class="crypto-treemap-error">Erro: Dados inválidos recebidos da API</div>');
            console.error('Dados inválidos:', data);
            return;
        }

        // Limpar o contêiner
        container.empty();

        // Atualizar timestamp
        var now = new Date();
        var timestamp = now.toLocaleDateString('pt-BR') + ' ' + now.toLocaleTimeString('pt-BR');
        if (data.status && data.status.timestamp) {
            var apiTime = new Date(data.status.timestamp);
            timestamp = apiTime.toLocaleDateString('pt-BR') + ' ' + apiTime.toLocaleTimeString('pt-BR');
        }
        container.closest('.crypto-treemap-container').find('time').text(timestamp);

        // Garantir que mostramos apenas o número configurado de moedas
        var limit = 50;
        if (typeof cryptoTreemapSettings !== 'undefined' && cryptoTreemapSettings.limit) {
            limit = parseInt(cryptoTreemapSettings.limit);
        }
        var coinsToShow = data.data.slice(0, limit);

        // Obter dimensões do contêiner
        var width = container.width();
        var height = container.height();

        // Preparar dados para o treemap D3
        var treemapData = {
            name: "Criptomoedas",
            children: coinsToShow.map(function(coin) {
                return {
                    name: coin.symbol,
                    value: parseFloat(coin.market_cap) || 0,
                    price: parseFloat(coin.price) || 0,
                    change: parseFloat(coin.percent_change_24h) || 0,
                    volume: parseFloat(coin.volume_24h) || 0,
                    fullName: coin.name || coin.symbol
                };
            })
        };

        // Criar o layout do treemap
        var treemap = d3.treemap()
            .size([width, height])
            .paddingOuter(1)
            .paddingInner(1)
            .round(true);

        // Criar a hierarquia de dados
        var root = d3.hierarchy(treemapData)
            .sum(function(d) { return d.value; })
            .sort(function(a, b) { return b.value - a.value; });

        // Aplicar o layout
        treemap(root);

        // Criar o SVG
        var svg = d3.select(container.get(0))
            .append('div')
            .style('position', 'relative')
            .style('width', width + 'px')
            .style('height', height + 'px');

        // Criar os nós do treemap
        var node = svg.selectAll('.crypto-treemap-cell')
            .data(root.leaves())
            .enter()
            .append('div')
            .attr('class', function(d) {
                return 'crypto-treemap-cell ' + (d.data.change >= 0 ? 'green' : 'red');
            })
            .style('left', function(d) { return d.x0 + 'px'; })
            .style('top', function(d) { return d.y0 + 'px'; })
            .style('width', function(d) { return d.x1 - d.x0 + 'px'; })
            .style('height', function(d) { return d.y1 - d.y0 + 'px'; })
            .attr('data-symbol', function(d) { return d.data.name; })
            .attr('data-value', function(d) { return d.data.value; })
            .attr('title', function(d) { 
                return d.data.fullName + ' - ' + formatBRL(d.data.price) + 
                      ' (' + (d.data.change >= 0 ? '+' : '') + d.data.change.toFixed(2) + '%)';
            })
            .style('cursor', function() {
                // Adicionar cursor de clique apenas se tiver URL configurado
                return typeof cryptoTreemapSettings !== 'undefined' && 
                       cryptoTreemapSettings.redirectUrl ? 'pointer' : 'default';
            })
            .on('click', function() {
                // Redirecionar apenas se tiver URL configurado
                if (typeof cryptoTreemapSettings !== 'undefined' && cryptoTreemapSettings.redirectUrl) {
                    window.open(cryptoTreemapSettings.redirectUrl, '_blank');
                }
            });

        // Adicionar conteúdo aos nós
        node.append('div')
            .attr('class', 'crypto-treemap-cell-content')
            .each(function(d) {
                var container = d3.select(this);
                var areaSize = (d.x1 - d.x0) * (d.y1 - d.y0);
                var isSmall = areaSize < 5000;
                var isMedium = areaSize < 15000 && areaSize >= 5000;
                var isLarge = areaSize >= 15000;

                // Símbolo sempre visível com tamanho proporcional
                var symbolElement = container.append('div')
                    .attr('class', 'crypto-treemap-symbol')
                    .text(d.data.name);
                symbolElement.style('font-size', getFontSize(areaSize, 20));

                // Preço (visível em todos menos os muito pequenos)
                if (!isSmall || isLarge) {
                    var priceElement = container.append('div')
                        .attr('class', 'crypto-treemap-price')
                        .text(function() {
                            var price = d.data.price;
                            return formatBRL(price, price < 1 ? 6 : 2);
                        });
                    
                    if (isLarge) {
                        priceElement.style('font-size', getFontSize(areaSize, 18));
                    } else if (isMedium) {
                        priceElement.style('font-size', getFontSize(areaSize, 14));
                    }
                }

                // Variação (visível em médios e grandes)
                if (isMedium || isLarge) {
                    var changeElement = container.append('div')
                        .attr('class', 'crypto-treemap-change')
                        .text(function() {
                            return (d.data.change >= 0 ? '▲ ' : '▼ ') + 
                                  Math.abs(d.data.change).toFixed(2) + '%';
                        });
                    
                    if (isLarge) {
                        changeElement.style('font-size', getFontSize(areaSize, 16));
                    }
                }

                // Volume (visível apenas em grandes)
                if (isLarge) {
                    container.append('div')
                        .attr('class', 'crypto-treemap-volume')
                        .style('font-size', getFontSize(areaSize, 14))
                        .text('Vol: ' + formatVolume(d.data.volume));
                }
            });

        // Adicionar efeito de atualização
        $('.crypto-treemap-cell').addClass('updating');
        setTimeout(function() {
            $('.crypto-treemap-cell').removeClass('updating').addClass('updated');
            setTimeout(function() {
                $('.crypto-treemap-cell').removeClass('updated');
            }, 1000);
        }, 300);
        
        // Retornar o container para futura referência
        return container;
    }

    // Enhanced data loading with offline support and error handling
    function loadCryptoData() {
        console.log('[Crypto Treemap] loadCryptoData called');
        
        var startTime = performance.now();
        performanceMetrics.apiCalls++;
        
        console.log('[Crypto Treemap] Loading data... (attempt #' + performanceMetrics.apiCalls + ')');
        console.log('[Crypto Treemap] cryptoTreemapSettings available:', typeof cryptoTreemapSettings !== 'undefined');
        
        // Check if offline mode is enabled or if we're offline
        if ((typeof cryptoTreemapSettings !== 'undefined' && cryptoTreemapSettings.offlineMode) || !isOnline) {
            if (offlineData) {
                $('.crypto-treemap').each(function() {
                    renderTreemapWithErrorHandling(offlineData, $(this));
                });
                updateConnectionStatus();
                return;
            }
        }
        
        // Update status
        updateLoadingStatus('Carregando...');
        
        // Get configuration
        var limit = (typeof cryptoTreemapSettings !== 'undefined' && cryptoTreemapSettings.limit) ? cryptoTreemapSettings.limit : 50;
        var apiUrl = buildApiUrl(limit);
        
        // Enhanced AJAX with retry logic
        performApiRequest(apiUrl, 0, startTime);
    }
    
    function performApiRequest(apiUrl, retryCount, startTime) {
        var maxRetries = 3;
        var retryDelay = Math.min(1000 * Math.pow(2, retryCount), 10000); // Exponential backoff
        
        $.ajax({
            url: apiUrl,
            method: 'GET',
            timeout: 30000,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Cache-Control': 'no-cache'
            }
        })
        .done(function(response) {
            var loadTime = performance.now() - startTime;
            performanceMetrics.renderTimes.push(loadTime);
            
            if (cryptoTreemapSettings.performanceMetrics) {
                console.log('[Crypto Treemap] API call completed in', loadTime.toFixed(2), 'ms');
            }
            
            if (validateResponse(response)) {
                // Store offline data
                storeOfflineData(response);
                
                // Render with error handling
                $('.crypto-treemap').each(function() {
                    renderTreemapWithErrorHandling(response, $(this));
                });
                
                // Update connection status
                updateConnectionStatus();
                
                // Schedule next update
                console.log('[Crypto Treemap] Scheduling next update...');
                scheduleNextUpdate();
            } else {
                handleApiError('Invalid response data', retryCount, maxRetries, apiUrl, startTime);
            }
        })
        .fail(function(xhr, status, error) {
            var errorMsg = 'API Error: ' + status + ' - ' + error;
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMsg = xhr.responseJSON.message;
            }
            
            handleApiError(errorMsg, retryCount, maxRetries, apiUrl, startTime);
        });
    }
    
    function handleApiError(errorMsg, retryCount, maxRetries, apiUrl, startTime) {
        performanceMetrics.errors.push({
            time: new Date().toISOString(),
            error: errorMsg,
            retryCount: retryCount
        });
        
        if (cryptoTreemapSettings.debug) {
            console.error('[Crypto Treemap]', errorMsg);
        }
        
        if (retryCount < maxRetries) {
            var retryDelay = Math.min(1000 * Math.pow(2, retryCount), 10000);
            updateLoadingStatus('Erro ao carregar dados - Tentativa (' + (retryCount + 1) + '/' + maxRetries + ')');
            
            setTimeout(function() {
                performApiRequest(apiUrl, retryCount + 1, startTime);
            }, retryDelay);
        } else {
            // All retries failed, show offline data if available
            if (offlineData) {
                $('.crypto-treemap').each(function() {
                    renderTreemapWithErrorHandling(offlineData, $(this));
                });
                updateLoadingStatus('Modo offline - Última atualização: ' + getOfflineDataTimestamp());
            } else {
                showError(errorMsg);
            }
        }
    }
    
    function validateResponse(response) {
        return response && 
               response.data && 
               Array.isArray(response.data) && 
               response.data.length > 0 &&
               response.data[0].hasOwnProperty('symbol') &&
               response.data[0].hasOwnProperty('price');
    }
    
    function buildApiUrl(limit) {
        var baseUrl = '/wp-json/crypto-treemap/v1/prices';
        if (typeof cryptoTreemapSettings !== 'undefined' && cryptoTreemapSettings.ajaxUrl) {
            baseUrl = cryptoTreemapSettings.ajaxUrl;
        }
        var separator = baseUrl.indexOf('?') === -1 ? '?' : '&';
        return baseUrl + separator + 'limit=' + limit + '&t=' + Date.now();
    }
    
    function storeOfflineData(data) {
        try {
            var offlineStorage = {
                data: data,
                timestamp: Date.now()
            };
            localStorage.setItem('crypto_treemap_offline', JSON.stringify(offlineStorage));
            offlineData = data;
        } catch (e) {
            if (cryptoTreemapSettings.debug) {
                console.warn('[Crypto Treemap] Failed to store offline data:', e);
            }
        }
    }
    
    function getOfflineDataTimestamp() {
        try {
            var stored = localStorage.getItem('crypto_treemap_offline');
            if (stored) {
                var parsed = JSON.parse(stored);
                return new Date(parsed.timestamp).toLocaleString();
            }
        } catch (e) {
            return 'Unknown';
        }
        return 'Unknown';
    }
    
    function renderTreemapWithErrorHandling(data, container) {
        try {
            var renderStart = performance.now();
            var result = renderTreemap(data, container);
            var renderTime = performance.now() - renderStart;
            
            if (cryptoTreemapSettings.performanceMetrics) {
                console.log('[Crypto Treemap] Render completed in', renderTime.toFixed(2), 'ms');
            }
            
            return result;
        } catch (error) {
            console.error('[Crypto Treemap] Render error:', error);
            showError('Render error: ' + error.message);
            return null;
        }
    }
    
    function updateConnectionStatus() {
        $('.crypto-treemap-container').each(function() {
            var container = $(this);
            var statusElement = container.find('.crypto-connection-status');
            if (statusElement.length === 0) {
                statusElement = $('<div class="crypto-connection-status"></div>');
                container.prepend(statusElement);
            }
            
            if (!isOnline) {
                statusElement.text('Offline').addClass('offline').removeClass('online');
            } else {
                statusElement.text('').removeClass('offline online');
            }
        });
    }
    
    function updateLoadingStatus(message) {
        $('.crypto-treemap-container time').text(message);
    }
    
    function showError(message) {
        $('.crypto-treemap').each(function() {
            $(this).html('<div class="crypto-treemap-error">' + message + '</div>');
        });
        updateLoadingStatus('Erro ao carregar dados');
    }
    
    function scheduleNextUpdate() {
        var updateInterval = 30000; // 30 seconds default
        if (typeof cryptoTreemapSettings !== 'undefined' && cryptoTreemapSettings.updateInterval > 0) {
            updateInterval = cryptoTreemapSettings.updateInterval;
        }
        
        console.log('[Crypto Treemap] Next update scheduled in', updateInterval / 1000, 'seconds');
        
        setTimeout(function() {
            console.log('[Crypto Treemap] Auto-update triggered, isVisible:', isVisible, 'isOnline:', isOnline);
            if (isVisible && isOnline) {
                loadCryptoData();
            } else {
                // Try again in a few seconds if not visible or offline
                setTimeout(scheduleNextUpdate, 5000);
            }
        }, updateInterval);
    }
    
    // Performance monitoring functions
    function getPerformanceMetrics() {
        return {
            totalApiCalls: performanceMetrics.apiCalls,
            averageRenderTime: performanceMetrics.renderTimes.length > 0 
                ? performanceMetrics.renderTimes.reduce(function(a, b) { return a + b; }, 0) / performanceMetrics.renderTimes.length 
                : 0,
            errorCount: performanceMetrics.errors.length,
            uptime: performance.now() - performanceMetrics.startTime,
            cacheSize: formatCache.size,
            offlineDataAvailable: !!offlineData
        };
    }
    
    // Expose performance metrics for debugging
    if (cryptoTreemapSettings.performanceMetrics) {
        window.cryptoTreemapMetrics = getPerformanceMetrics;
        
        // Log metrics every 5 minutes
        setInterval(function() {
            console.log('[Crypto Treemap] Performance Metrics:', getPerformanceMetrics());
        }, 300000);
    }
    
    // Cleanup function for when the widget is removed
    function cleanup() {
        if (observer) {
            observer.disconnect();
        }
        
        if (resizeTimeout) {
            clearTimeout(resizeTimeout);
        }
        
        formatCache.clear();
        
        // Remove event listeners
        window.removeEventListener('online', setupOfflineSupport);
        window.removeEventListener('offline', setupOfflineSupport);
        document.removeEventListener('visibilitychange', setupEventListeners);
    }
    
    // Expose cleanup function
    window.cryptoTreemapCleanup = cleanup;
    
    // Auto-cleanup on page unload
    $(window).on('beforeunload', cleanup);
}); 