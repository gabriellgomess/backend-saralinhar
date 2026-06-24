# API de Vagas de Emprego - Laravel Sanctum

API REST desenvolvida com Laravel 12 e Laravel Sanctum para autenticação de usuários e gerenciamento de vagas de emprego.

## Tecnologias Utilizadas

- Laravel 12
- Laravel Sanctum 4.2
- PHP 8.2
- SQLite

## Instalação

### Requisitos

- PHP 8.2 ou superior
- Composer
- SQLite (ou outro banco de dados de sua preferência)

### Passos de Instalação

1. Clone o repositório ou navegue até a pasta `api`

2. Instale as dependências:
```bash
composer install
```

3. Configure o arquivo `.env`:
```bash
cp .env.example .env
```

4. Gere a chave da aplicação:
```bash
php artisan key:generate
```

5. Execute as migrações:
```bash
php artisan migrate
```

6. (Opcional) Execute os seeders para popular o banco com dados de exemplo:
```bash
php artisan db:seed
```

7. Inicie o servidor:
```bash
php artisan serve
```

A API estará disponível em `http://localhost:8000`

## Endpoints da API

Todos os endpoints da API estão prefixados com `/api`

### Rotas Públicas

#### Registro de Usuário
```
POST /api/register
```

**Body (JSON):**
```json
{
    "name": "Nome do Usuário",
    "email": "usuario@example.com",
    "password": "senha123",
    "password_confirmation": "senha123"
}
```

**Resposta de Sucesso (201):**
```json
{
    "message": "Usuário registrado com sucesso",
    "user": {
        "id": 1,
        "name": "Nome do Usuário",
        "email": "usuario@example.com",
        "created_at": "2025-10-15T00:00:00.000000Z",
        "updated_at": "2025-10-15T00:00:00.000000Z"
    },
    "token": "1|token_gerado_pelo_sanctum",
    "token_type": "Bearer"
}
```

#### Login
```
POST /api/login
```

**Body (JSON):**
```json
{
    "email": "usuario@example.com",
    "password": "senha123"
}
```

**Resposta de Sucesso (200):**
```json
{
    "message": "Login realizado com sucesso",
    "user": {
        "id": 1,
        "name": "Nome do Usuário",
        "email": "usuario@example.com",
        "created_at": "2025-10-15T00:00:00.000000Z",
        "updated_at": "2025-10-15T00:00:00.000000Z"
    },
    "token": "2|token_gerado_pelo_sanctum",
    "token_type": "Bearer"
}
```

#### Listar Vagas (Feed)
```
GET /api/jobs
```

**Resposta de Sucesso (200):**
```json
{
    "current_page": 1,
    "data": [
        {
            "id": 1,
            "user_id": 1,
            "title": "Desenvolvedor Full Stack",
            "company": "Tech Solutions",
            "address": "São Paulo, SP - Remoto",
            "description": "Estamos buscando um desenvolvedor full stack...",
            "responsibilities": "Desenvolver e manter aplicações web...",
            "requirements": "Experiência com Laravel, React...",
            "workload": "40 horas semanais",
            "salary": "8000.00",
            "benefits": "Vale alimentação, Vale transporte, Plano de saúde, Home office",
            "type": "clt",
            "email": "rh@techsolutions.com",
            "phone": "(11) 98765-4321",
            "is_active": true,
            "created_at": "2025-10-15T00:00:00.000000Z",
            "updated_at": "2025-10-15T00:00:00.000000Z",
            "user": {
                "id": 1,
                "name": "Gabriel Gomes",
                "email": "gabriel.gomes@outlook.com"
            }
        }
    ],
    "per_page": 15,
    "total": 5
}
```

#### Ver Detalhes de uma Vaga
```
GET /api/jobs/{id}
```

**Resposta de Sucesso (200):**
```json
{
    "id": 1,
    "user_id": 1,
    "title": "Desenvolvedor Full Stack",
    "company": "Tech Solutions",
    "address": "São Paulo, SP - Remoto",
    "description": "Estamos buscando um desenvolvedor full stack...",
    "responsibilities": "Desenvolver e manter aplicações web...",
    "requirements": "Experiência com Laravel, React...",
    "workload": "40 horas semanais",
    "salary": "8000.00",
    "benefits": "Vale alimentação, Vale transporte, Plano de saúde, Home office",
    "type": "clt",
    "email": "rh@techsolutions.com",
    "phone": "(11) 98765-4321",
    "is_active": true,
    "created_at": "2025-10-15T00:00:00.000000Z",
    "updated_at": "2025-10-15T00:00:00.000000Z",
    "user": {
        "id": 1,
        "name": "Gabriel Gomes",
        "email": "gabriel.gomes@outlook.com"
    }
}
```

### Rotas Protegidas (Autenticação)

Para acessar as rotas protegidas, você deve incluir o token de autenticação no header:
```
Authorization: Bearer {seu_token_aqui}
```

#### Obter Perfil do Usuário
```
GET /api/profile
```

**Headers:**
```
Authorization: Bearer {seu_token}
```

**Resposta de Sucesso (200):**
```json
{
    "user": {
        "id": 1,
        "name": "Nome do Usuário",
        "email": "usuario@example.com",
        "created_at": "2025-10-15T00:00:00.000000Z",
        "updated_at": "2025-10-15T00:00:00.000000Z"
    }
}
```

#### Logout
```
POST /api/logout
```

**Headers:**
```
Authorization: Bearer {seu_token}
```

**Resposta de Sucesso (200):**
```json
{
    "message": "Logout realizado com sucesso"
}
```

### Rotas Protegidas (Vagas)

#### Criar Nova Vaga
```
POST /api/jobs
```

**Headers:**
```
Authorization: Bearer {seu_token}
```

**Body (JSON):**
```json
{
    "title": "Desenvolvedor Full Stack",
    "company": "Tech Solutions",
    "address": "São Paulo, SP - Remoto",
    "description": "Estamos buscando um desenvolvedor full stack para integrar nossa equipe de tecnologia.",
    "responsibilities": "Desenvolver e manter aplicações web, participar de reuniões de planejamento, realizar code reviews, trabalhar com metodologias ágeis.",
    "requirements": "Experiência com Laravel, React, conhecimento em bancos de dados relacionais, inglês intermediário.",
    "workload": "40 horas semanais",
    "salary": 8000.00,
    "benefits": "Vale alimentação, Vale transporte, Plano de saúde, Home office",
    "type": "clt",
    "email": "rh@techsolutions.com",
    "phone": "(11) 98765-4321"
}
```

**Resposta de Sucesso (201):**
```json
{
    "message": "Vaga criada com sucesso",
    "job": {
        "id": 1,
        "user_id": 1,
        "title": "Desenvolvedor Full Stack",
        "company": "Tech Solutions",
        "address": "São Paulo, SP - Remoto",
        "description": "Estamos buscando um desenvolvedor full stack...",
        "responsibilities": "Desenvolver e manter aplicações web...",
        "requirements": "Experiência com Laravel, React...",
        "workload": "40 horas semanais",
        "salary": "8000.00",
        "benefits": "Vale alimentação, Vale transporte, Plano de saúde, Home office",
        "type": "clt",
        "email": "rh@techsolutions.com",
        "phone": "(11) 98765-4321",
        "is_active": true,
        "created_at": "2025-10-15T00:00:00.000000Z",
        "updated_at": "2025-10-15T00:00:00.000000Z"
    }
}
```

#### Atualizar Vaga
```
PUT /api/jobs/{id}
```

**Headers:**
```
Authorization: Bearer {seu_token}
```

**Body (JSON):** (todos os campos são opcionais)
```json
{
    "title": "Desenvolvedor Full Stack Sênior",
    "salary": 10000.00
}
```

**Resposta de Sucesso (200):**
```json
{
    "message": "Vaga atualizada com sucesso",
    "job": { ... }
}
```

#### Deletar Vaga
```
DELETE /api/jobs/{id}
```

**Headers:**
```
Authorization: Bearer {seu_token}
```

**Resposta de Sucesso (200):**
```json
{
    "message": "Vaga deletada com sucesso"
}
```

#### Listar Minhas Vagas
```
GET /api/my-jobs
```

**Headers:**
```
Authorization: Bearer {seu_token}
```

**Resposta de Sucesso (200):**
```json
{
    "current_page": 1,
    "data": [ ... ],
    "per_page": 15,
    "total": 3
}
```

## Validações

### Registro
- `name`: obrigatório, string, máximo 255 caracteres
- `email`: obrigatório, email válido, máximo 255 caracteres, único
- `password`: obrigatório, string, mínimo 8 caracteres, deve ser confirmado
- `password_confirmation`: deve ser igual ao campo `password`

### Login
- `email`: obrigatório, email válido
- `password`: obrigatório

### Criar/Atualizar Vaga
- `title`: obrigatório, string, máximo 255 caracteres
- `company`: obrigatório, string, máximo 255 caracteres
- `address`: obrigatório, string, máximo 255 caracteres
- `description`: obrigatório, texto
- `responsibilities`: obrigatório, texto
- `requirements`: obrigatório, texto
- `workload`: obrigatório, string, máximo 255 caracteres
- `salary`: opcional, numérico, mínimo 0
- `benefits`: opcional, texto
- `type`: obrigatório, enum (clt, pj, estagio, aprendiz)
- `email`: obrigatório, email válido
- `phone`: opcional, string, máximo 20 caracteres
- `is_active`: opcional, booleano

## Configuração CORS

A API está configurada para aceitar requisições de qualquer origem. Para ambientes de produção, é recomendado configurar o arquivo `config/cors.php` para permitir apenas origens específicas.

## Estrutura de Arquivos Principais

```
api/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       └── Api/
│   │           ├── AuthController.php
│   │           └── JobController.php
│   └── Models/
│       ├── User.php
│       └── Job.php
├── config/
│   └── cors.php
├── database/
│   ├── migrations/
│   │   ├── create_users_table.php
│   │   └── create_jobs_table.php
│   ├── seeders/
│   │   ├── UserSeeder.php
│   │   └── JobSeeder.php
│   └── database.sqlite
├── routes/
│   ├── api.php
│   └── web.php
└── bootstrap/
    └── app.php
```

## Testando a API

### Usando cURL

**Registro:**
```bash
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Teste User",
    "email": "teste@example.com",
    "password": "senha123",
    "password_confirmation": "senha123"
  }'
```

**Login:**
```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "teste@example.com",
    "password": "senha123"
  }'
```

**Perfil:**
```bash
curl -X GET http://localhost:8000/api/profile \
  -H "Authorization: Bearer SEU_TOKEN_AQUI"
```

**Logout:**
```bash
curl -X POST http://localhost:8000/api/logout \
  -H "Authorization: Bearer SEU_TOKEN_AQUI"
```

## Funcionalidades

### Autenticação
- Registro de novos usuários
- Login com email e senha
- Logout (revogação de token)
- Visualização de perfil do usuário autenticado
- Proteção de rotas com Laravel Sanctum

### Gerenciamento de Vagas
- Listagem pública de vagas ativas (feed com paginação)
- Visualização detalhada de uma vaga
- Criação de novas vagas (apenas usuários autenticados)
- Atualização de vagas (apenas o criador da vaga)
- Exclusão de vagas (apenas o criador da vaga)
- Listagem das vagas criadas pelo usuário autenticado

### Tipos de Contratação
- CLT (Consolidação das Leis do Trabalho)
- PJ (Pessoa Jurídica)
- Estágio
- Aprendiz

## Dados de Exemplo

A API vem com seeders que criam:
- 1 usuário de exemplo (gabriel.gomes@outlook.com / 10203040)
- 5 vagas de exemplo em diferentes categorias

## Próximos Passos

Esta API está pronta para ser consumida pelo frontend. Os tokens gerados pelo Sanctum devem ser armazenados no frontend (localStorage, sessionStorage ou cookies) e enviados em cada requisição para rotas protegidas.

## Licença

MIT
