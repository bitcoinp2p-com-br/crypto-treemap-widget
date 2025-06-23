# Crypto Treemap Widget v2.0.0

Um plugin WordPress poderoso, seguro e rico em recursos para exibir dados de mercado de criptomoedas em uma visualização interativa de treemap. Perfeito para sites financeiros, blogs de criptomoedas ou qualquer plataforma que queira mostrar informações de mercado de forma visualmente atraente.

![Crypto Treemap Preview](https://github.com/bitcoinp2p-com-br/crypto-treemap-widget/raw/main/screenshot.png)

## 🚀 Novo na Versão 2.0

### ✨ Melhorias Principais
- **Interface em Português**: Títulos e timestamps em português brasileiro
- **Atualizações Automáticas**: Cotações atualizadas automaticamente em tempo real
- **Efeitos Visuais**: Animações de atualização para mostrar que os dados estão sendo atualizados
- **Formato de Data Brasileiro**: Exibição de data/hora no formato DD/MM/AAAA HH:MM:SS
- **Configuração Dinâmica**: Título mostra o número exato configurado (TOP X CRIPTOMOEDAS)

### 📊 Visualização Interativa
- **Treemap Dinâmico**: Visualização responsiva em tempo real com D3.js
- **Cores por Performance**: Verde para ganhos, vermelho para perdas
- **Tipografia Adaptativa**: Tamanhos de fonte ajustam automaticamente
- **Animações Suaves**: Efeitos visuais indicando atualizações
- **Tooltips Informativos**: Informações detalhadas ao passar o mouse

### 📈 Dados em Tempo Real
- **Atualizações Automáticas**: Intervalo configurável (5 segundos a 1 hora)
- **Múltiplas Opções**: Top 10, 20, 50 ou 100 criptomoedas
- **Cache Inteligente**: Sistema de cache reduz chamadas à API
- **Suporte Offline**: Dados em cache quando offline
- **Integração CoinMarketCap**: Dados profissionais de fonte confiável

### 🌐 Interface em Português
- **Títulos Dinâmicos**: "TOP X CRIPTOMOEDAS EM BRL"
- **Data Brasileira**: Formato DD/MM/AAAA HH:MM:SS
- **Mensagens em Português**: Todas as mensagens do usuário
- **Configuração no Admin**: Interface administrativa em português

### 📱 Responsivo e Acessível
- **Design Mobile-First**: Otimizado para todos os tamanhos de tela
- **WCAG 2.1**: Totalmente acessível com ARIA labels
- **Suporte a Leitor de Tela**: Compatibilidade completa
- **Navegação por Teclado**: Controle total via teclado

## 🚀 Para Uso Público

**Perfeito para displays públicos e monitoramento contínuo:**
- **Atualizações Automáticas**: Funciona 24/7 sem intervenção manual
- **Interface Limpa**: Sem menção a fontes de dados, focado nas cotações
- **Resistente a Falhas**: Continua funcionando mesmo com problemas de conexão
- **Otimizado para Displays**: Ideal para telas que ficam ligadas o dia todo
- **Baixo Consumo de Recursos**: Eficiente para uso prolongado

## 📦 Instalação

### Método 1: Admin do WordPress (Recomendado)

1. Baixe a versão mais recente em [GitHub Releases](https://github.com/bitcoinp2p-com-br/crypto-treemap-widget/releases)
2. No admin do WordPress, vá em **Plugins** > **Adicionar Novo** > **Enviar Plugin**
3. Selecione o arquivo ZIP baixado e clique em **Instalar Agora**
4. Ative o plugin após a instalação
5. Vá em **Configurações** > **Crypto Treemap** para configurar

### Método 2: Instalação Manual

1. Baixe e extraia os arquivos do plugin
2. Envie a pasta `crypto-treemap-widget` para `/wp-content/plugins/`
3. Ative o plugin através do menu **Plugins** no WordPress
4. Configure o plugin em **Configurações** > **Crypto Treemap**

### Método 3: Git Clone (Desenvolvimento)

```bash
cd /caminho/para/wp-content/plugins/
git clone https://github.com/bitcoinp2p-com-br/crypto-treemap-widget.git
```

## ⚙️ Configuração

### Configuração Básica

1. Navegue para **Configurações** > **Crypto Treemap** no admin do WordPress
2. Insira sua **Chave API do CoinMarketCap** (obrigatório - [obtenha uma gratuita](https://coinmarketcap.com/api/))
3. Configure suas preferências:

### Opções de Configuração

| Configuração | Descrição | Padrão | Opções |
|--------------|-----------|--------|---------|
| **Chave API** | Chave API Pro do CoinMarketCap | *Obrigatório* | Nível gratuito disponível |
| **Intervalo de Atualização** | Frequência de atualização dos dados | 30 segundos | 5 segundos - 1 hora |
| **Limite de Criptomoedas** | Número de moedas a exibir | 50 | 10, 20, 50, 100 |
| **URL de Redirecionamento** | Para onde vão os usuários ao clicar | App BitcoinP2P | Qualquer URL válida |
| **Origens Permitidas (CORS)** | Domínios permitidos para acessar API | Domínio atual | Uma por linha |
| **Modo Debug** | Ativar métricas de performance | Desabilitado | Ativar para desenvolvimento |
| **Modo Offline** | Forçar dados offline/cache | Desabilitado | Ativar para testes |

## 📊 CoinMarketCap API Usage

The free CoinMarketCap API provides 100,000 monthly calls. Plan your usage carefully to avoid exceeding limits:

### Recommended Settings for Free Tier

| Configuration | Min Interval | Daily Calls | Monthly Calls | Free Tier Duration |
|---------------|--------------|-------------|---------------|--------------------|
| **Top 10**   | 60 seconds   | 1,440       | 43,200        | ~2.3 months        |
| **Top 20**   | 120 seconds  | 720         | 21,600        | ~4.6 months        |
| **Top 50**   | 300 seconds  | 288         | 8,640         | ~11.5 months       |
| **Top 100**  | 600 seconds  | 144         | 4,320         | ~23 months         |

### Calculation Formula
```
Daily calls = 86,400 seconds ÷ Update interval
Monthly calls = Daily calls × 30
Duration = 100,000 ÷ Monthly calls
```

### Pro Tips
- Use **Smart Caching** to reduce API calls automatically
- Enable **Offline Mode** for development/testing
- Monitor usage in the admin tools section
- Consider upgrading to paid plans for high-traffic sites

## 🎯 Como Usar

### Implementação Básica
Adicione o shortcode em qualquer página ou post:
```
[crypto_treemap]
```

### Uso Avançado
O widget automaticamente:
- **Redimensiona responsivamente** baseado no container
- **Recursos de acessibilidade** para todos os usuários  
- **Recuperação de erros** com fallbacks elegantes
- **Otimização de performance** através de cache
- **Compatibilidade cross-browser** (IE11+)

### Personalização
O widget herda os estilos do seu tema mas inclui variáveis CSS abrangentes para personalização.

## 🔧 Admin Tools

Version 2.0 includes powerful admin tools:

### Cache Management
- **Clear Cache**: Instantly clear all cached data
- **Cache Statistics**: View cache performance metrics
- **Cache Health**: Monitor cache hit rates

### API Testing
- **Test API Connection**: Verify CoinMarketCap API connectivity
- **Performance Metrics**: View response times and error rates
- **Debug Information**: Detailed logging for troubleshooting

### Security Monitoring
- **Rate Limit Status**: Monitor API rate limiting
- **CORS Configuration**: Manage allowed origins
- **Security Logs**: Track access attempts (debug mode)

## 📋 Requirements

### Minimum Requirements
- **WordPress**: 5.0 or higher
- **PHP**: 7.4 or higher
- **Memory**: 64MB PHP memory limit
- **API Key**: CoinMarketCap Pro API key (free tier available)

### Recommended Environment
- **WordPress**: 6.0+
- **PHP**: 8.0+
- **Memory**: 128MB+
- **HTTPS**: SSL certificate for security
- **Modern Browser**: Chrome 70+, Firefox 65+, Safari 12+

### Browser Compatibility
| Browser | Version | Notes |
|---------|---------|-------|
| Chrome | 70+ | Full support |
| Firefox | 65+ | Full support |
| Safari | 12+ | Full support |
| Edge | 79+ | Full support |
| IE | 11 | Limited support, no animations |

## 🏗️ Architecture

### Plugin Structure
```
crypto-treemap-widget/
├── assets/
│   ├── css/
│   │   ├── crypto-treemap.css
│   │   └── crypto-treemap.min.css
│   └── js/
│       ├── crypto-treemap.js
│       ├── crypto-treemap.min.js
│       └── d3.v7.min.js
├── includes/
│   ├── class-crypto-api.php
│   ├── class-crypto-security.php
│   └── class-crypto-assets.php
├── templates/
│   └── treemap.php
├── crypto-treemap.php
└── README.md
```

### Class Overview
- **`CryptoTreemap`**: Main plugin class and WordPress integration
- **`Crypto_Treemap_API`**: API handling, caching, and rate limiting
- **`Crypto_Treemap_Security`**: Security features and validation
- **`Crypto_Treemap_Assets`**: Asset management and optimization

## 🚀 Performance

### Optimization Features
- **Lazy Loading**: Components load only when visible
- **Asset Minification**: Compressed CSS and JavaScript
- **Local Dependencies**: D3.js hosted locally for faster loading
- **Smart Caching**: Multi-layer caching reduces server load
- **Intersection Observer**: Efficient visibility detection

### Performance Metrics (Debug Mode)
- API response times
- Render performance
- Cache hit rates
- Error tracking
- Memory usage

## 🔒 Security

### Security Features
- **Rate Limiting**: Prevents API abuse
- **CORS Protection**: Configurable cross-origin policies
- **Input Sanitization**: All inputs properly sanitized
- **Nonce Verification**: CSRF protection on all AJAX calls
- **Capability Checks**: Proper permission validation

### Best Practices Implemented
- ✅ Escape all output
- ✅ Sanitize all input
- ✅ Use WordPress nonces
- ✅ Validate user capabilities
- ✅ Prevent direct file access
- ✅ Secure AJAX endpoints

## 🌐 Accessibility

### WCAG 2.1 Compliance
- **Level AA** compliance achieved
- **Keyboard Navigation**: Full keyboard accessibility
- **Screen Readers**: ARIA labels and descriptions
- **Color Contrast**: High contrast ratios
- **Focus Management**: Visible focus indicators
- **Reduced Motion**: Respects user motion preferences

### Accessibility Features
- Semantic HTML structure
- ARIA landmarks and roles
- Alternative text for visual elements
- Keyboard-accessible controls
- Screen reader announcements
- High contrast mode support

## 🛠️ Development

### Setting Up Development Environment
```bash
# Clone the repository
git clone https://github.com/bitcoinp2p-com-br/crypto-treemap-widget.git

# Navigate to plugin directory
cd crypto-treemap-widget

# Enable debug mode in wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

### Debug Mode Features
- **Console Logging**: Detailed JavaScript debugging
- **Performance Metrics**: Real-time performance data
- **API Monitoring**: Track all API calls and responses
- **Error Tracking**: Comprehensive error logging
- **Cache Statistics**: Cache performance insights

### Code Quality Standards
- **PSR-12**: PHP coding standards
- **JSDoc**: JavaScript documentation
- **WordPress Coding Standards**: Full compliance
- **Accessibility**: WCAG 2.1 guidelines
- **Performance**: Core Web Vitals optimization

## 📞 Support & Contributing

### Getting Help
- **GitHub Issues**: [Report bugs or request features](https://github.com/bitcoinp2p-com-br/crypto-treemap-widget/issues)
- **Documentation**: Check this README and inline documentation
- **Community**: WordPress.org plugin support forums
- **Email**: support@bitcoinp2p.com.br

### Contributing
We welcome contributions! Please:
1. Fork the repository
2. Create a feature branch
3. Follow coding standards
4. Add tests for new features
5. Submit a pull request

### Roadmap
- [ ] Multiple base currencies (USD, EUR, etc.)
- [ ] Custom color themes
- [ ] Advanced filtering options
- [ ] Historical data view
- [ ] Widget for block editor
- [ ] REST API expansion

## 📄 License

This project is licensed under the GPL v2 or later - see the [LICENSE](LICENSE) file for details.

## 🙏 Credits

### Built With
- **D3.js** - Data visualization library
- **CoinMarketCap API** - Cryptocurrency market data
- **WordPress** - Content management system

### Developed By
**[BitcoinP2P](https://bitcoinp2p.com.br)** - Cryptocurrency trading platform for Brazil

---

*Made with ❤️ for the WordPress and cryptocurrency communities* 