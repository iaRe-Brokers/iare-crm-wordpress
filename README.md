# iaRe CRM - Plugin WordPress

[![WordPress](https://img.shields.io/badge/WordPress-6.0+-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4+-purple.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPL%20v2+-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Elementor](https://img.shields.io/badge/Elementor-Compatible-orange.svg)](https://elementor.com/)

Plugin WordPress para integração completa com o sistema iaRe CRM, permitindo captura e gerenciamento automático de leads diretamente do seu site WordPress com suporte nativo ao Elementor.

## 🚀 Recursos Principais

- **Integração Perfeita com CRM**: Conecte seu site WordPress diretamente ao iaRe CRM
- **Integração com Elementor**: Suporte nativo para formulários Elementor com ações personalizadas
- **Captura de Leads**: Capture e sincronize leads automaticamente dos formulários do site
- **Gerenciamento de API**: Cliente API robusto para transmissão segura de dados
- **Painel Administrativo**: Interface administrativa completa para configuração e monitoramento

## 🔧 Recursos Técnicos

- Integração com API RESTful
- Autenticação segura por chave de API
- Custom post types e meta fields
- Manipulação avançada de formulários
- Sincronização de dados em tempo real
- Log de erros e monitoramento
- Arquitetura extensível com hooks e filtros

## 📋 Requisitos

- WordPress 6.0 ou superior
- PHP 7.4 ou superior
- Conta válida no iaRe CRM com acesso à API
- Elementor (para integração de formulários)

## 📦 Instalação

### Via WordPress Admin

1. Faça login no painel administrativo do WordPress
2. Navegue para **Plugins** > **Adicionar Novo**
3. Procure por "iaRe CRM"
4. Clique em **Instalar Agora** e depois **Ativar**

### Instalação Manual

1. Baixe o arquivo ZIP do plugin
2. Faça upload para o diretório `/wp-content/plugins/`
3. Extraia os arquivos
4. Ative o plugin através do menu **Plugins** no WordPress

## ⚙️ Configuração

1. Após a ativação, acesse **iaRe CRM** no menu administrativo
2. Insira sua chave de API do iaRe CRM nas configurações
3. Configure suas preferências de integração
4. Teste a conexão para garantir o funcionamento

## 🎯 Como Usar

### Integração com Elementor

1. Crie um formulário no Elementor
2. Nas configurações do formulário, adicione a ação **iaRe CRM**
3. Configure o mapeamento dos campos
4. Publique o formulário

### Hooks Disponíveis

```php
// Executado quando o plugin é carregado
add_action('iare_crm_loaded', 'minha_funcao');

// Filtrar dados do lead antes do envio
add_filter('iare_crm_lead_data', 'filtrar_dados_lead');

// Filtrar requisições da API
add_filter('iare_crm_api_request', 'filtrar_api_request');
```

## ❓ Perguntas Frequentes

**Preciso de uma conta no iaRe CRM?**
Sim, você precisa de uma conta válida e chave de API. Entre em contato com o iaRe CRM para configuração.

**O plugin é gratuito?**
Sim, o plugin é gratuito. Você precisa apenas de uma conta no iaRe CRM.

**Funciona com outros construtores de página?**
Atualmente suportamos Elementor nativamente. Outras integrações podem ser adicionadas no futuro.

**É compatível com WooCommerce?**
A integração com WooCommerce está em desenvolvimento e será lançada em breve.

## 📄 Licença

Este projeto está licenciado sob a GPL v2 - veja o arquivo [LICENSE](LICENSE) para detalhes.

## 🏆 Créditos

Desenvolvido pela equipe iaRe CRM.

---

<p align="center">
  <strong>🚀 Transforme visitantes em leads com iaRe CRM!</strong>
</p>

<p align="center">
  <a href="https://iare.me/seja-parceiro">Seja Parceiro</a> • 
  <a href="https://iarebrokers.com.br">iaRe Brokers</a> • 
</p> 