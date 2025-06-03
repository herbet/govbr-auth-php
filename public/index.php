<?php
require_once __DIR__ . '/../vendor/autoload.php';

use GovBr\GovBrAuth;

$client_id = 'CLIENT_ID';
$client_secret = 'SECRET_ID';
$redirect_uri = 'https://sistema.gov.br/callback';

$auth = new GovBrAuth($client_id, $client_secret, $redirect_uri);

if (isset($_GET['code']) && isset($_GET['state'])) {
    try {
        $usuario = $auth->tratarCallback($_GET['code'], $_GET['state']);
        echo "<h2>Usuário autenticado com sucesso:</h2><pre>";
        print_r($usuario);
        echo "</pre>";
    } catch (Exception $e) {
        echo "<p>Erro: " . $e->getMessage() . "</p>";
    }
    exit;
}

if (isset($_GET['login'])) {
    $auth->iniciarAutenticacao();
    exit;
}
?>

<!doctype html>
<html lang="pt-br">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta http-equiv="X-UA-Compatible" content="ie=edge" />
        
        <!-- Font Rawline-->
        <link rel="stylesheet" href="https://cdngovbr-ds.estaleiro.serpro.gov.br/design-system/fonts/rawline/css/rawline.css" />
        <!-- Font Raleway-->
        <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Raleway:300,400,500,600,700,800,900&amp;display=swap" />
        <!-- Design System GOV.BR CSS-->
        <link rel="stylesheet" href="node_modules/@govbr-ds/core/dist/core.min.css" />

        <title>Login GOV.BR</title>
    </head>

    <body style="display: flex; align-items: center; justify-content: center; height: 100vh;">

        <form method="get">    
            <button type="submit" name="login" value="1" class="br-sign-in primary" type="button">Entrar com&nbsp;<span class="text-black">gov.br
            </button>
        </form>
  
    </body>
</html>
