jQuery(document).ready(function($) {
    // Variável para armazenar o treemap atual durante a atualização
    var currentTreemap = null;

    // Função para formatar números em BRL
    function formatBRL(number, decimals) {
        if (isNaN(number) || number === null) number = 0;
        decimals = decimals || 2;
        return 'R$ ' + number.toLocaleString('pt-BR', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        });
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
        var timestamp = new Date().toLocaleString('pt-BR');
        if (data.status && data.status.timestamp) {
            timestamp = new Date(data.status.timestamp).toLocaleString('pt-BR');
        }
        $('#crypto-treemap-timestamp').text(timestamp);

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

        // Adicionar efeito de piscar
        $('.crypto-treemap-cell').addClass('blink');
        setTimeout(function() {
            $('.crypto-treemap-cell').removeClass('blink');
        }, 500);
        
        // Retornar o container para futura referência
        return container;
    }

    // Função para carregar dados
    function loadCryptoData() {
        console.log('Tentando carregar dados das criptomoedas...');
        
        // Atualizar apenas o timestamp enquanto carrega
        $('#crypto-treemap-timestamp').text('Atualizando...');
        
        // Criar um contêiner temporário invisível para o novo treemap (para evitar tela branca)
        var tempContainer = $('<div>').css({
            position: 'absolute', 
            left: '-9999px', 
            width: $('#crypto-treemap').width(),
            height: $('#crypto-treemap').height()
        });
        
        // Obter o limite configurado
        var limit = 50;
        if (typeof cryptoTreemapSettings !== 'undefined' && cryptoTreemapSettings.limit) {
            limit = parseInt(cryptoTreemapSettings.limit);
        }
        
        // Adicionar parâmetro de limite à URL
        var apiUrl = cryptoTreemapSettings.ajaxUrl;
        if (apiUrl.indexOf('?') === -1) {
            apiUrl += '?limit=' + limit;
        } else {
            apiUrl += '&limit=' + limit;
        }
        
        $.ajax({
            url: apiUrl,
            method: 'GET',
            cache: false, // Evitar cache do navegador
            timeout: 20000, // Aumentar timeout para 20 segundos
            headers: {
                'Cache-Control': 'no-cache',
                'Pragma': 'no-cache'
            },
            success: function(response) {
                console.log('Dados recebidos com sucesso');
                
                // Verificar se os dados são válidos
                if (!response || !response.data || !Array.isArray(response.data) || response.data.length === 0) {
                    console.error('Resposta da API não contém dados válidos', response);
                    $('#crypto-treemap-timestamp').text('Erro: Dados inválidos - Tentando novamente...');
                    
                    // Tentar novamente em 5 segundos
                    setTimeout(loadCryptoData, 5000);
                    return;
                }
                
                try {
                    // Se é a primeira carga (não há treemap atual)
                    if (!currentTreemap) {
                        currentTreemap = renderTreemap(response, $('#crypto-treemap'));
                    } else {
                        // Renderizar no contêiner temporário
                        tempContainer.appendTo('body');
                        var newTreemap = renderTreemap(response, tempContainer);
                        
                        // Piscar os elementos do treemap atual (como indicação visual de atualização)
                        $('.crypto-treemap-cell').addClass('blink');
                        
                        // Substituir o treemap antigo pelo novo após um pequeno atraso
                        setTimeout(function() {
                            $('#crypto-treemap').empty().append(newTreemap.children());
                            tempContainer.remove();
                        }, 500);
                    }
                } catch (e) {
                    console.error('Erro ao renderizar treemap:', e);
                    
                    // Em caso de erro, tentar criar um novo treemap do zero
                    $('#crypto-treemap').empty();
                    currentTreemap = null;
                    currentTreemap = renderTreemap(response, $('#crypto-treemap'));
                }
            },
            error: function(xhr, status, error) {
                console.error('Erro ao carregar dados:', status, error);
                
                // Mostrar erro apenas se não houver treemap atual
                if (!currentTreemap) {
                    $('#crypto-treemap').html('<div class="crypto-treemap-error">Erro ao carregar dados: ' + status + '</div>');
                } else {
                    // Apenas atualizar o timestamp para indicar erro
                    $('#crypto-treemap-timestamp').text('Erro na atualização - Tentando novamente em breve');
                }
                
                // Tentar novamente em 10 segundos
                setTimeout(loadCryptoData, 10000);
            }
        });
    }
    
    // Carregar dados iniciais
    loadCryptoData();
    
    // Recarregar ao redimensionar a janela
    var resizeTimer;
    $(window).on('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            // Resetar o treemap atual para forçar recriação completa no novo tamanho
            currentTreemap = null;
            loadCryptoData();
        }, 500);
    });
    
    // Atualizar a cada intervalo
    if (typeof cryptoTreemapSettings !== 'undefined' && cryptoTreemapSettings.updateInterval > 0) {
        setInterval(loadCryptoData, cryptoTreemapSettings.updateInterval);
    }
}); 