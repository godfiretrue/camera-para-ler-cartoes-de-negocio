# Ponto de Contacto — Vercel + Supabase

## Configurar Supabase

1. Abra o projeto Supabase e execute [`database/supabase.sql`](./database/supabase.sql) no SQL Editor.
2. Copie o Project URL e a chave secreta de servidor `service_role` (legacy). Não coloque a chave em código de frontend.

## Deploy na Vercel

Configure estas variáveis de ambiente no projeto Vercel:

- `SUPABASE_URL`: URL HTTPS do projeto, por exemplo `https://<project-ref>.supabase.co`.
- `SUPABASE_SERVICE_ROLE_KEY`: chave secreta `service_role` do Supabase, apenas no servidor.
- `APP_ACCESS_PASSWORD`: palavra-passe forte para proteger os pedidos da API.

Depois de configurar as variáveis, faça um novo deployment. A função serverless em `api/contacts.js` acede ao Supabase pela API REST; não requer PHP nem PDO na Vercel. A interface pede a palavra-passe na primeira chamada à API. A chave Supabase nunca é enviada ao navegador.

## Execução local

Com XAMPP, use `http://localhost/PHP/contractos/`. Em `localhost`/`127.0.0.1`, a aplicação utiliza `local-api/contacts.php` com SQLite. Não é necessário definir as variáveis Supabase para desenvolvimento local.

## Se aparecer um erro na aplicação publicada

Confirme que o deployment mais recente está ativo, que executou o SQL no mesmo projeto cujo URL configurou e que as três variáveis Vercel estão definidas no ambiente correto (Production/Preview). Abra os logs da função `api/contacts` na Vercel; o código de erro Supabase também será apresentado pela aplicação. Não publique valores das chaves ou palavras-passe ao pedir ajuda.
