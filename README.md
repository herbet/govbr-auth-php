# GOV.BR Auth PHP

Este projeto implementa autenticação Login Único do **GOV.BR** utilizando **OAuth2** e **JWT** em PHP.

## Estrutura do Projeto

```
├── .gitignore
├── composer.json
├── docker-compose.yml
├── Dockerfile
├── public/
│   ├── .htaccess
│   ├── index.php
│   ├── package.json
├── src/
│   ├── GovBrAuth.php
```
### Requisitos

- PHP 8.1 ou superior
- Composer
- Node.js e npm (para o Design System do Gov.BR)
- Docker (opcional)

### Principais Arquivos

- **`src/GovBrAuth.php`**: Classe principal que gerencia a autenticação com o GovBR.
- **`public/index.php`**: Ponto de entrada da aplicação, gerencia o fluxo de autenticação.
- **`Dockerfile`**: Configuração para rodar o projeto em um container Docker.
- **`docker-compose.yml`**: Arquivo de configuração para orquestrar o ambiente Docker.
- **`composer.json`**: Gerencia as dependências do projeto.
- **`public/package.json`**: Arquivo de configuração para instalar as dependências do Design System do Gov.BR.

### Passos

1. Clone o repositório:
   ```bash
   git clone https://github.com/herbet/govbr-auth-php.git
   cd govbr-auth-php
   ```

2. Instale as dependências PHP:
   ```bash
   composer install
   ```

3. Instale as dependências do Design System do Gov.BR:
   ```bash
   cd public
   npm install
   ```

4. Configure as variáveis de ambiente no arquivo `public/index.php`:
   ```php
   $client_id = 'CLIENT_ID';
   $client_secret = 'CLIENT_SECRET';
   $redirect_uri = 'https://seu-sistema.gov.br/callback';
   ```

5. Execute o projeto:
   - Com Docker:
     ```bash
     docker-compose up -d
     ```

## Apontamento local do domínio cadastrado no gov.br

Para que o fluxo de autenticação com o Login Único do gov.br funcione corretamente em ambiente de desenvolvimento (como localhost), é **necessário realizar o apontamento local** do domínio que foi cadastrado como *redirect URI* na solicitação de credenciais no gov.br.

### Exemplo (Linux ou WSL)

Edite o arquivo `/etc/hosts` com privilégios administrativos:

```bash
sudo nano /etc/hosts
```

Adicione a seguinte linha (ajuste para o domínio que você cadastrou):

```
127.0.0.1    sistema.gov.br
```

### Exemplo (Windows)

Edite o arquivo `C:\Windows\System32\drivers\etc\hosts` com permissões de administrador e adicione:

```
127.0.0.1    sistema.cgu.gov.br
```

> **Importante:** esse apontamento é necessário apenas em ambiente local.  

---

### Fluxo de Autenticação

1. Acesse a página inicial do projeto.
2. Clique no botão "Entrar com gov.br".
3. Após autenticar no GovBR, você será redirecionado para o callback configurado.
4. O usuário autenticado será exibido na tela.

## Docker

O projeto inclui um `Dockerfile` e um `docker-compose.yml` para facilitar a execução em ambientes isolados. O Apache está configurado para servir os arquivos da pasta `public`.

## Design System do Gov.BR

O projeto utiliza o Design System do Gov.BR para estilização. Certifique-se de instalar as dependências do `package.json` na pasta `public` utilizando o comando `npm install`.

## Referências

- [Roteiro Técnico de Integração do Login Único](https://acesso.gov.br/roteiro-tecnico/index.html)