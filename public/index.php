<?php
require_once __DIR__ . '/../vendor/autoload.php';

use GovBr\GovBrAuth;

// Configurações do cliente OAuth2
$client_id = 'CLIENT_ID'; // Identificador único do cliente registrado no GovBR
$client_secret = 'SECRET_ID'; // Segredo do cliente para autenticação
$redirect_uri = 'https://sistema.gov.br/callback'; // URL para onde o usuário será redirecionado após autenticação

// Instancia a classe de autenticação
$auth = new GovBrAuth($client_id, $client_secret, $redirect_uri);

// Verifica se o código de autorização e o estado foram recebidos no callback
if (isset($_GET['code']) && isset($_GET['state'])) {
    try {
        // Trata o callback e obtém as informações do usuário autenticado
        $usuario = $auth->tratarCallback($_GET['code'], $_GET['state']);
        echo "<h2>Usuário autenticado com sucesso:</h2><pre>";
        print_r($usuario); // Exibe os dados do usuário autenticado
        echo "</pre>";

        // Exibe a imagem do usuário se estiver disponível
        if (!empty($usuario['picture'])) {
            echo '<h3>Foto do usuário:</h3>';
            echo '<img src="' . $usuario['picture'] . '" alt="Foto do usuário" style="max-width: 150px; border-radius: 50%;">';
        }
    } catch (Exception $e) {
        // Exibe mensagens de erro caso ocorra algum problema
        echo "<p>Erro: " . $e->getMessage() . "</p>";
    }
    exit;
}

// Inicia o fluxo de autenticação quando o botão de login é clicado
if (isset($_GET['login'])) {
    $auth->iniciarAutenticacao(); // Redireciona o usuário para o endpoint de autorização
    exit;
}
?>

<!doctype html>
<html lang="pt-br">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta http-equiv="X-UA-Compatible" content="ie=edge" />
        
        <!-- Font Rawline -->
        <link rel="stylesheet" href="https://cdngovbr-ds.estaleiro.serpro.gov.br/design-system/fonts/rawline/css/rawline.css" />
        <!-- Font Raleway -->
        <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Raleway:300,400,500,600,700,800,900&amp;display=swap" />
        <!-- Design System GOV.BR CSS -->
        <link rel="stylesheet" href="node_modules/@govbr-ds/core/dist/core.min.css" />

        <title>Login GOV.BR</title>
    </head>

    <body style="display: flex; align-items: center; justify-content: center; height: 100vh;">
        <!-- Formulário para iniciar o fluxo de autenticação -->
        <form method="get">    
            <button type="submit" name="login" value="1" class="br-sign-in primary" type="button">
                Entrar com&nbsp;<span class="text-black">gov.br</span>
            </button>
        </form>
    </body>
</html>
