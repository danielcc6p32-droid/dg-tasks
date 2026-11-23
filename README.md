# dg-tasks — DG Tech

Sistema PHP 8.1+ com PDO SQLite e mbstring.

## Instalação local

1. Instale PHP com `pdo_sqlite`, `sqlite3` e `mbstring`.
2. Abra um terminal na pasta `dg-tasks`.
3. Execute `php -S localhost:8000 -t public` (use 8001 para o segundo projeto).
4. Acesse http://localhost:8000.

## Avaliação

Clique em **Entrar na demonstração** para gerar uma conta de teste isolada automaticamente, ou cadastre um usuário e senha próprios (mínimo 12 caracteres). Não existe mais senha padrão admin/admin.

## Hospedagem

Configure a raiz pública (document root) para a pasta `public`. A pasta `private` deve ficar fora do acesso HTTP e gravável pelo PHP. Hospedagem precisa oferecer PHP 8.1+, PDO SQLite e armazenamento persistente. Não envie `private/dados.sqlite` para GitHub. Use HTTPS.

Projeto demonstrativo: não use informações reais/sensíveis. Contas demo criam registros no banco; limpe-os periodicamente em hospedagem pública.
