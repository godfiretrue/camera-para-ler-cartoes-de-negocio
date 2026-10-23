# Ponto de Contacto — Vercel + Supabase

## Configurar Supabase

1. Abra o projeto Supabase e execute [`database/supabase.sql`](./database/supabase.sql) no SQL Editor.
2. Copie o Project URL e a chave secreta de servidor `service_role` (legacy). Não coloque a chave em código de frontend.

## Deploy na Vercel

Configure estas variáveis de ambiente no projeto Vercel:

- `SUPABASE_URL`: URL HTTPS do projeto, por exemplo `https://<project-ref>.supabase.co`.
- `SUPABASE_SERVICE_ROLE_KEY`: chave secreta `service_role` do Supabase, apenas no servidor.
- `APP_ACCESS_PASSWORD`: palavra-passe forte para proteger os pedidos da API.

Depois de configurar as variáveis, faça um novo deployment. A função serverless em `api/contactos.js` acede ao Supabase pela API REST na rota `/api/contactos`; não requer PHP nem PDO na Vercel. O nome `contactos` evita conflito de rota com `contacts.php`. A interface pede a palavra-passe na primeira chamada à API. A chave Supabase nunca é enviada ao navegador.

Na Vercel, cada variável tem de estar marcada para o ambiente do deployment (Production, Preview ou Development). Adicionar ou alterar variáveis não atualiza deployments já criados: volte a fazer deploy após guardar as variáveis. Se a API indicar variáveis em falta, confira os nomes exatos acima no ambiente correspondente; a função não revela os respetivos valores.

## Execução local

Com XAMPP, use `http://localhost/PHP/contractos/`. Em `localhost`/`127.0.0.1`, a aplicação utiliza `local-api/contacts.php` com SQLite. Não é necessário definir as variáveis Supabase para desenvolvimento local.

## Ler cartões de visita no telemóvel

Na aplicação publicada, escolha **Ler cartão de visita**. Em computadores, a aplicação abre a câmara do navegador; em telemóveis, pode fotografar ou escolher uma imagem. Também pode escolher uma fotografia manualmente. O acesso à câmara requer autorização do navegador e uma origem segura (HTTPS ou `localhost`). O OCR em português é executado no navegador com Tesseract.js; a imagem não é enviada para a API nem guardada. É necessária ligação à Internet para carregar a biblioteca e os dados de idioma. Os campos preenchidos automaticamente devem ser revistos antes de guardar.

O acesso à câmara do telemóvel depende do suporte do navegador e, normalmente, de uma ligação HTTPS. Também é possível selecionar uma fotografia já existente.

## Se aparecer um erro na aplicação publicada

Confirme que o deployment mais recente está ativo, que executou o SQL no mesmo projeto cujo URL configurou e que as três variáveis Vercel estão definidas no ambiente correto (Production/Preview). Abra os logs da função `api/contactos` na Vercel; o código de erro Supabase também será apresentado pela aplicação. Não publique valores das chaves ou palavras-passe ao pedir ajuda.
