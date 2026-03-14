# Guia de Deploy em Produção — Recruit

Este guia descreve o processo completo para realizar o deploy do Recruit em um ambiente de produção utilizando Docker Compose.

---

## Índice

1. [Requisitos do Servidor](#1-requisitos-do-servidor)
2. [Preparação do Ambiente](#2-preparação-do-ambiente)
3. [Configuração do `.env`](#3-configuração-do-env)
4. [Build e Inicialização](#4-build-e-inicialização)
5. [Configuração do Nginx (Reverse Proxy)](#5-configuração-do-nginx-reverse-proxy)
6. [SSL/TLS com Let's Encrypt](#6-ssltls-com-lets-encrypt)
7. [Primeiro Acesso](#7-primeiro-acesso)
8. [Backup e Restauração](#8-backup-e-restauração)
9. [Atualização da Aplicação](#9-atualização-da-aplicação)
10. [Monitoramento e Logs](#10-monitoramento-e-logs)
11. [Troubleshooting](#11-troubleshooting)

---

## 1. Requisitos do Servidor

### Hardware Mínimo

| Recurso | Mínimo | Recomendado |
|---------|--------|-------------|
| CPU     | 2 vCPUs | 4 vCPUs |
| RAM     | 4 GB    | 8 GB |
| Disco   | 40 GB SSD | 80 GB SSD |

### Software

- **Sistema Operacional**: Ubuntu 22.04 LTS (ou Debian 12)
- **Docker**: 24.0+
- **Docker Compose**: v2.20+
- **Git**: 2.x

### Portas Necessárias

| Porta | Serviço | Descrição |
|-------|---------|-----------|
| 80    | HTTP    | Redirecionamento para HTTPS |
| 443   | HTTPS   | Aplicação principal |
| 8443  | HTTPS   | Jitsi Meet (videoentrevistas) |
| 10000/udp | UDP | Jitsi JVB (tráfego de vídeo) |

---

## 2. Preparação do Ambiente

### Instalar Docker e Docker Compose

```bash
# Atualizar pacotes
sudo apt update && sudo apt upgrade -y

# Instalar dependências
sudo apt install -y ca-certificates curl gnupg

# Adicionar repositório oficial Docker
sudo install -m 0755 -d /etc/apt/keyrings
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /etc/apt/keyrings/docker.gpg
sudo chmod a+r /etc/apt/keyrings/docker.gpg

echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu $(. /etc/os-release && echo "$VERSION_CODENAME") stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null

sudo apt update
sudo apt install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin

# Adicionar usuário ao grupo docker
sudo usermod -aG docker $USER
newgrp docker
```

### Clonar o Repositório

```bash
cd /opt
sudo mkdir -p recruit && sudo chown $USER:$USER recruit
git clone https://github.com/glauberportella/Recruit.git recruit
cd recruit
git checkout main  # ou a tag de release desejada
```

---

## 3. Configuração do `.env`

Copie o arquivo de exemplo e edite as variáveis para produção:

```bash
cp .env.example .env
```

### Variáveis Obrigatórias para Produção

```env
# ── Aplicação ──────────────────────────────────────
APP_NAME="Recruit"
APP_ENV=production
APP_KEY=                          # Será gerado automaticamente no primeiro boot
APP_DEBUG=false
APP_URL=https://recruit.seudominio.com.br

# ── Banco de Dados (PostgreSQL com pgvector) ──────
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=recruit
DB_USERNAME=recruit_user
DB_PASSWORD=SENHA_FORTE_AQUI      # Use: openssl rand -base64 32

# ── Cache & Filas ──────────────────────────────────
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120

REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

# ── E-mail (SMTP) ─────────────────────────────────
MAIL_MAILER=smtp
MAIL_HOST=smtp.seudominio.com.br
MAIL_PORT=587
MAIL_USERNAME=recruit@seudominio.com.br
MAIL_PASSWORD=SENHA_SMTP_AQUI
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=recruit@seudominio.com.br
MAIL_FROM_NAME="${APP_NAME}"

# ── Logging ────────────────────────────────────────
LOG_CHANNEL=stack
LOG_LEVEL=warning

# ── Docker (portas no host) ────────────────────────
APP_PORT=8800
DB_PORT=5433
REDIS_PORT=6380
DOCKER_UID=1000

# ── Jitsi Meet (Videoentrevistas) ──────────────────
JITSI_WEB_PORT=8443
JITSI_WEB_HTTP_PORT=8880
JITSI_JVB_PORT=10000
JITSI_ENABLE_AUTH=1
JITSI_ENABLE_GUESTS=1
JITSI_AUTH_TYPE=jwt
JITSI_JWT_APP_ID=recruit
JITSI_JWT_APP_SECRET=GERE_UM_SECRET_FORTE    # Use: openssl rand -hex 32
JITSI_JWT_ACCEPTED_ISSUERS=recruit
JITSI_JWT_ACCEPTED_AUDIENCES=recruit
JITSI_PUBLIC_URL=https://jitsi.seudominio.com.br
JITSI_JICOFO_AUTH_PASSWORD=GERE_OUTRA_SENHA  # Use: openssl rand -hex 16
JITSI_JVB_AUTH_PASSWORD=GERE_OUTRA_SENHA     # Use: openssl rand -hex 16

# ── OpenAI (AI Matching — opcional) ────────────────
OPENAI_API_KEY=sk-...
OPENAI_ORGANIZATION=
```

> **Importante**: Nunca versione o arquivo `.env`. Ele já está no `.gitignore`.

### Gerar Senhas Seguras

```bash
# Senha do banco de dados
openssl rand -base64 32

# Secrets do Jitsi
openssl rand -hex 32
openssl rand -hex 16
```

---

## 4. Build e Inicialização

### Build das Imagens

```bash
docker compose build --no-cache
```

### Iniciar Todos os Serviços

```bash
docker compose up -d
```

O `entrypoint.sh` executará automaticamente:
1. Aguardar PostgreSQL ficar pronto
2. Detectar primeiro boot e rodar setup (migrações, seed, permissões)
3. Criar storage link e caches

### Verificar Status

```bash
docker compose ps
```

Todos os containers devem estar com status `Up (healthy)` ou `Up`:

| Container | Função |
|-----------|--------|
| `recruit-app` | PHP-FPM (aplicação) |
| `recruit-nginx` | Servidor Web |
| `recruit-postgres` | Banco de dados |
| `recruit-redis` | Cache e filas |
| `recruit-worker` | Processador de filas |
| `recruit-scheduler` | Agendador de tarefas |
| `recruit-jitsi-web` | Jitsi Meet frontend |
| `recruit-jitsi-prosody` | Jitsi XMPP |
| `recruit-jitsi-jicofo` | Jitsi Focus |
| `recruit-jitsi-jvb` | Jitsi Video Bridge |

---

## 5. Configuração do Nginx (Reverse Proxy)

Na máquina host, configure um Nginx como reverse proxy para encaminhar tráfego para os containers.

> **Importante**: Configure o Nginx **apenas com HTTP** nesta etapa. O SSL será configurado automaticamente pelo Certbot na [seção 6](#6-ssltls-com-lets-encrypt).

### Instalar Nginx no Host

```bash
sudo apt install -y nginx
```

### Configuração para a Aplicação

Crie `/etc/nginx/sites-available/recruit`:

```nginx
server {
    listen 80;
    server_name recruit.seudominio.com.br;

    client_max_body_size 100M;

    location / {
        proxy_pass http://127.0.0.1:8800;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_read_timeout 300;
    }
}
```

### Configuração para o Jitsi Meet

Crie `/etc/nginx/sites-available/jitsi`:

```nginx
server {
    listen 80;
    server_name jitsi.seudominio.com.br;

    location / {
        proxy_pass http://127.0.0.1:8880;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;

        # WebSocket support
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
    }
}
```

### Ativar os Sites

```bash
sudo ln -sf /etc/nginx/sites-available/recruit /etc/nginx/sites-enabled/
sudo ln -sf /etc/nginx/sites-available/jitsi /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

---

## 6. SSL/TLS com Let's Encrypt

O Certbot com a flag `--nginx` irá **automaticamente**:
- Obter os certificados SSL
- Adicionar os blocos `listen 443 ssl` nas configurações do Nginx
- Configurar o redirect HTTP → HTTPS (301)
- Configurar protocolos e ciphers seguros

```bash
# Instalar Certbot
sudo apt install -y certbot python3-certbot-nginx

# Gerar certificados (o Certbot modifica automaticamente os arquivos do Nginx)
sudo certbot --nginx -d recruit.seudominio.com.br
sudo certbot --nginx -d jitsi.seudominio.com.br

# Renovação automática (já configurada pelo certbot, mas verifique)
sudo certbot renew --dry-run
```

Após o Certbot, adicione os headers de segurança no bloco `server` HTTPS de `/etc/nginx/sites-available/recruit`:

```nginx
    # Headers de segurança (adicionar dentro do bloco server 443)
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
```

```bash
sudo nginx -t
sudo systemctl reload nginx
```

---

## 7. Primeiro Acesso

1. Acesse `https://recruit.seudominio.com.br`
2. Faça login com as credenciais padrão:
   - **Email**: `superuser@mail.com`
   - **Senha**: `password`
3. **Ações imediatas após o primeiro login**:
   - Altere a senha do super-administrador
   - Configure os dados da empresa em **Configurações > Dados da Empresa**
   - Configure o SMTP de e-mail (se não feito via `.env`)
   - Configure o Jitsi em **Configurações > Entrevistas Online (Jitsi)** com o domínio correto
   - Configure a chave da OpenAI em **Configurações > AI Matching** (se for utilizar)

---

## 8. Backup e Restauração

### Backup Automático do Banco de Dados

Crie o script `/opt/recruit/scripts/backup.sh`:

```bash
#!/bin/bash
set -euo pipefail

BACKUP_DIR="/opt/recruit/backups"
DATE=$(date +%Y%m%d_%H%M%S)
RETENTION_DAYS=30

mkdir -p "$BACKUP_DIR"

# Backup do PostgreSQL
docker exec recruit-postgres pg_dump \
    -U "${DB_USERNAME:-postgres}" \
    -d "${DB_DATABASE:-recruit}" \
    --format=custom \
    --compress=9 \
    > "$BACKUP_DIR/recruit_db_${DATE}.dump"

# Backup dos uploads
tar czf "$BACKUP_DIR/recruit_storage_${DATE}.tar.gz" \
    -C /opt/recruit storage/app/public

# Backup do .env
cp /opt/recruit/.env "$BACKUP_DIR/env_${DATE}.bak"

# Remover backups antigos
find "$BACKUP_DIR" -type f -mtime +${RETENTION_DAYS} -delete

echo "[$(date)] Backup concluído: recruit_db_${DATE}.dump"
```

```bash
chmod +x /opt/recruit/scripts/backup.sh
```

### Agendar via Cron

```bash
# Executar diariamente às 02:00
echo "0 2 * * * /opt/recruit/scripts/backup.sh >> /var/log/recruit-backup.log 2>&1" | crontab -
```

### Restaurar Backup

```bash
# Restaurar banco de dados
docker exec -i recruit-postgres pg_restore \
    -U postgres \
    -d recruit \
    --clean --if-exists \
    < /opt/recruit/backups/recruit_db_XXXXXXXX_XXXXXX.dump

# Restaurar arquivos de upload
tar xzf /opt/recruit/backups/recruit_storage_XXXXXXXX_XXXXXX.tar.gz \
    -C /opt/recruit
```

---

## 9. Atualização da Aplicação

### Procedimento de Atualização

```bash
cd /opt/recruit

# 1. Fazer backup antes de atualizar
./scripts/backup.sh

# 2. Puxar as alterações
git fetch origin
git checkout main
git pull origin main

# 3. Rebuild e restart
docker compose build --no-cache app worker scheduler
docker compose up -d

# 4. Executar migrações (o entrypoint faz isso, mas pode forçar)
docker exec recruit-app php artisan migrate --force

# 5. Limpar caches
docker exec recruit-app php artisan optimize:clear
docker exec recruit-app php artisan icons:cache
docker exec recruit-app php artisan filament:upgrade --no-interaction

# 6. Reiniciar o worker para carregar código novo
docker compose restart worker scheduler
```

### Atualização com Zero Downtime

Para atualizações sem interrupção, use a estratégia de rolling update:

```bash
# Rebuild em background
docker compose build app worker scheduler

# Reiniciar serviço por serviço
docker compose up -d --no-deps app
docker compose up -d --no-deps worker
docker compose up -d --no-deps scheduler
```

---

## 10. Monitoramento e Logs

### Visualizar Logs

```bash
# Todos os serviços
docker compose logs -f

# Serviço específico
docker compose logs -f app
docker compose logs -f worker
docker compose logs -f nginx

# Logs do Laravel (dentro do container)
docker exec recruit-app tail -f storage/logs/laravel.log
```

### Monitorar Filas

```bash
# Status das filas Redis
docker exec recruit-app php artisan queue:monitor redis:default

# Listar jobs com falha
docker exec recruit-app php artisan queue:failed

# Retentar jobs com falha
docker exec recruit-app php artisan queue:retry all
```

### Healthcheck

Verifique periodicamente a saúde dos serviços:

```bash
# Status dos containers
docker compose ps

# Saúde do PostgreSQL
docker exec recruit-postgres pg_isready -U postgres

# Saúde do Redis
docker exec recruit-redis redis-cli ping

# Saúde da aplicação
curl -s -o /dev/null -w "%{http_code}" https://recruit.seudominio.com.br
```

---

## 11. Troubleshooting

### Container não inicia

```bash
# Ver logs detalhados
docker compose logs app --tail=100

# Acessar o container para debug
docker exec -it recruit-app bash
```

### Erro 502 Bad Gateway

- Verifique se o container `recruit-app` está rodando: `docker compose ps`
- Verifique os logs: `docker compose logs app nginx`
- Confirme que o PHP-FPM está escutando na porta 9000

### Filas não processam

```bash
# Verificar se o worker está rodando
docker compose ps worker

# Reiniciar o worker
docker compose restart worker

# Verificar conexão com Redis
docker exec recruit-app php artisan tinker --execute="echo Redis::ping();"
```

### Erro de permissão em storage/

```bash
docker exec recruit-app chmod -R 775 storage bootstrap/cache
docker exec recruit-app chown -R recruit:www-data storage bootstrap/cache
```

### Emails não estão sendo enviados

```bash
# Testar envio de email
docker exec recruit-app php artisan tinker --execute="Mail::raw('Teste', fn(\$m) => \$m->to('teste@email.com')->subject('Teste'));"

# Verificar logs do worker (emails são processados na fila)
docker compose logs worker --tail=50
```

### Jitsi não conecta

- Verifique se a porta UDP 10000 está aberta no firewall
- Confirme que `JITSI_PUBLIC_URL` no `.env` aponta para o domínio correto
- Verifique os logs: `docker compose logs jitsi-web prosody jicofo jvb`

---

## Checklist de Deploy

- [ ] Servidor provisionado com Docker e Docker Compose
- [ ] DNS apontando para o servidor (`recruit.seudominio.com.br`, `jitsi.seudominio.com.br`)
- [ ] Repositório clonado em `/opt/recruit`
- [ ] `.env` configurado com variáveis de produção
- [ ] `APP_DEBUG=false` e `APP_ENV=production`
- [ ] Senhas fortes geradas para DB, Jitsi e demais serviços
- [ ] `docker compose up -d` executado com sucesso
- [ ] Certificados SSL emitidos via Let's Encrypt
- [ ] Nginx reverse proxy configurado e rodando
- [ ] Portais acessíveis via HTTPS
- [ ] Senha do super-administrador alterada
- [ ] Dados da empresa configurados
- [ ] SMTP de e-mail funcionando
- [ ] Backup automático agendado via cron
- [ ] Firewall configurado (portas 80, 443, 8443, 10000/udp)
- [ ] Porta UDP 10000 aberta para o Jitsi JVB
