# Crypto Treemap Widget

Um widget WordPress elegante e responsivo para exibir as principais criptomoedas em tempo real usando visualização de treemap. Ideal para sites de finanças, blogs de criptomoedas ou qualquer portal que queira mostrar informações do mercado de forma visualmente atraente.

![Crypto Treemap Preview](https://example.com/screenshot.png)

## Características

- Visualização de treemap dinâmico e responsivo
- Exibição em tempo real com atualizações automáticas
- Escolha exibir Top 10, Top 20 ou Top 50 criptomoedas
- Cores que mudam de acordo com a variação de preço (verde para positivo, vermelho para negativo)
- Fontes dinâmicas que se ajustam ao tamanho dos blocos
- URL de redirecionamento configurável ao clicar em qualquer criptomoeda
- Integração com API CoinMarketCap
- Interface totalmente responsiva (desktop, tablet e mobile)

## Instalação

### Método 1: Via GitHub (Recomendado)

1. Clone ou baixe este repositório como arquivo ZIP
2. Extraia o arquivo baixado
3. Compacte a pasta `crypto-treemap-widget` em um novo arquivo ZIP
4. No painel WordPress, vá para "Plugins" > "Adicionar Novo" > "Enviar Plugin"
5. Selecione o arquivo ZIP que você criou e clique em "Instalar Agora"
6. Ative o plugin após a instalação

## Configuração

1. Após a instalação, vá para "Configurações" > "Crypto Treemap" no painel WordPress
2. Insira sua chave de API do CoinMarketCap (obrigatório)
3. Configure as opções desejadas:
   - Intervalo de Atualização (segundos)
   - Número de Criptomoedas a exibir (Top 10, 20 ou 50)
   - URL de Redirecionamento (para onde o usuário será direcionado ao clicar em uma criptomoeda)

## Uso da API CoinMarketCap

A versão gratuita da API CoinMarketCap oferece 100.000 chamadas, o que pode acabar rapidamente se você configurar intervalos muito curtos. Recomendamos:

| Configuração | Intervalo Mínimo | Chamadas Diárias | Chamadas Mensais | Duração do Plano Gratuito |
|--------------|------------------|------------------|------------------|-----------------------------|
| Top 10       | 60 segundos      | 1.440           | 43.200           | ~2,3 meses                  |
| Top 20       | 120 segundos     | 720             | 21.600           | ~4,6 meses                  |
| Top 50       | 300 segundos     | 288             | 8.640            | ~11,5 meses                 |

**Fórmula de cálculo:**
- Chamadas diárias = 86.400 (segundos em um dia) ÷ Intervalo de atualização
- Chamadas mensais = Chamadas diárias × 30
- Duração = 100.000 ÷ Chamadas mensais

## Como Usar

1. Adicione o shortcode `[crypto_treemap]` em qualquer página ou post onde deseja exibir o widget
2. O treemap será exibido automaticamente e atualizado de acordo com o intervalo configurado

## Requisitos

- WordPress 5.0 ou superior
- PHP 7.2 ou superior
- Chave de API do CoinMarketCap (gratuita ou paga)
- Tema WordPress responsivo para melhor experiência

## Suporte

Caso encontre algum problema ou tenha sugestões, por favor:
- Abra uma issue no GitHub
- Entre em contato através de support@bitcoinp2p.com.br

---

Desenvolvido por [BitcoinP2P](https://bitcoinp2p.com.br) 