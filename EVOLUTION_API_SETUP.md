# Configuração da Evolution API para WhatsApp

Este documento descreve como configurar a integração com a Evolution API para envio automático de vagas via WhatsApp.

## Variáveis de Ambiente

Adicione as seguintes variáveis ao seu arquivo `.env`:

```env
# Evolution API Configuration
EVOLUTION_API_URL=https://seu-vps-domain.com
EVOLUTION_API_KEY=sua_api_key_aqui
EVOLUTION_INSTANCE_NAME=nome_da_sua_instancia
EVOLUTION_PHONE_NUMBER=5551997073430
```

**Importante:** Substitua `https://seu-vps-domain.com` pela URL real do seu VPS onde a Evolution API está rodando.

## Configuração da Evolution API

### 1. Evolution API já configurada no VPS

Como você já tem a Evolution API rodando no seu VPS, você só precisa:

1. **Obter a URL do seu VPS** onde a Evolution API está rodando
2. **Obter sua API Key** da Evolution API
3. **Obter o nome da instância** que você criou
4. **Configurar as variáveis de ambiente** no seu projeto Laravel

### 2. Estrutura da API

A integração foi configurada para usar a estrutura correta da Evolution API:

**Endpoint:** `POST {EVOLUTION_API_URL}/message/sendText/{EVOLUTION_INSTANCE_NAME}`

**Headers obrigatórios:**
- `Content-Type: application/json`
- `apikey: {EVOLUTION_API_KEY}`

**Body obrigatório:**
```json
{
  "number": "5551997073430",
  "text": "Sua mensagem aqui"
}
```

**Resposta esperada:**
```json
{
  "key": {
    "remoteJid": "553198296801@s.whatsapp.net",
    "fromMe": true,
    "id": "BAE594145F4C59B4"
  },
  "message": {
    "extendedTextMessage": {
      "text": "Sua mensagem"
    }
  },
  "messageTimestamp": "1717689097",
  "status": "PENDING"
}
```

### 3. Teste da Integração

Para testar se a integração está funcionando, você pode usar o método `sendTestMessage()` do serviço:

```php
use App\Services\EvolutionApiService;

$evolutionService = new EvolutionApiService();
$result = $evolutionService->sendTestMessage();

if ($result) {
    echo "Mensagem de teste enviada com sucesso!";
} else {
    echo "Erro ao enviar mensagem de teste.";
}
```

## Funcionalidades Implementadas

### Envio Automático de Vagas

Quando uma nova vaga é criada através da API, ela é automaticamente enviada para o número configurado no WhatsApp com as seguintes informações:

- Título da vaga
- Empresa
- Localização
- Carga horária
- Tipo de contrato
- Salário (se informado)
- Descrição
- Responsabilidades
- Requisitos
- Benefícios
- Informações de contato

### Formato da Mensagem

A mensagem é formatada de forma legível com emojis e estrutura organizada para facilitar a leitura no WhatsApp.

### Tratamento de Erros

- Se o envio para WhatsApp falhar, a vaga ainda será criada normalmente
- Todos os erros são logados para monitoramento
- O sistema não interrompe o fluxo principal em caso de falha na integração

## Monitoramento

Os logs de envio são registrados em:
- Sucessos: `storage/logs/laravel.log`
- Erros: `storage/logs/laravel.log`

Procure por mensagens com as tags:
- "Vaga enviada para WhatsApp com sucesso"
- "Erro ao enviar vaga para WhatsApp"
- "Exceção ao enviar vaga para WhatsApp"

## Troubleshooting

### Problemas Comuns

1. **Erro de conexão**: Verifique se a Evolution API está rodando e acessível
2. **Erro de autenticação**: Verifique se a API key está correta
3. **Instância não conectada**: Verifique se o WhatsApp foi escaneado e está conectado
4. **Número inválido**: Verifique se o número está no formato correto (com código do país)

### Verificação de Status

Use o método `checkConnection()` para verificar se a instância está conectada:

```php
$evolutionService = new EvolutionApiService();
$isConnected = $evolutionService->checkConnection();

if ($isConnected) {
    echo "Instância conectada!";
} else {
    echo "Instância desconectada!";
}
```
