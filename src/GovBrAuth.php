<?php
// src/GovBrAuth.php

namespace GovBr;

use Firebase\JWT\JWT;
use Firebase\JWT\JWK;

class GovBrAuth {
    private $client_id;
    private $client_secret;
    private $redirect_uri;
    private $authorization_endpoint = 'https://sso.staging.acesso.gov.br/authorize'; // Endpoint para iniciar o fluxo de autorização
    private $token_endpoint = 'https://sso.staging.acesso.gov.br/token'; // Endpoint para troca de código por tokens
    private $jwks_uri = 'https://sso.staging.acesso.gov.br/jwk'; // Endpoint para obter as chaves públicas do provedor
    private $url_servicos = 'https://api.staging.acesso.gov.br'; // Endpoint para acessar APIs adicionais do GovBR

    public function __construct($client_id, $client_secret, $redirect_uri) {
        $this->client_id = $client_id;
        $this->client_secret = $client_secret;
        $this->redirect_uri = $redirect_uri;

        // Inicia a sessão caso ainda não esteja ativa
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Inicia o fluxo de autenticação OAuth2.
     * Gera os parâmetros necessários para proteger o código de autorização (PKCE).
     * Redireciona o usuário para o endpoint de autorização.
     */
    public function iniciarAutenticacao() {
        $state = bin2hex(random_bytes(8)); // Gera um valor aleatório para proteger contra CSRF
        $nonce = bin2hex(random_bytes(8)); // Gera um valor aleatório para proteger contra replay attacks
        $code_verifier = bin2hex(random_bytes(32)); // Gera o code_verifier para o fluxo PKCE
        $code_challenge = rtrim(strtr(base64_encode(hash('sha256', $code_verifier, true)), '+/', '-_'), '='); // Gera o code_challenge baseado no code_verifier

        // Salva os valores na sessão para validação posterior
        $_SESSION['oauth2state'] = $state;
        $_SESSION['oauth2nonce'] = $nonce;
        $_SESSION['code_verifier'] = $code_verifier;

        // Monta a URL de autorização com os parâmetros necessários
        $url = $this->authorization_endpoint . '?' . http_build_query([
            'response_type' => 'code',
            'client_id' => $this->client_id,
            'redirect_uri' => $this->redirect_uri,
            'scope' => 'openid email profile govbr_confiabilidades',
            'state' => $state,
            'nonce' => $nonce,
            'code_challenge' => $code_challenge,
            'code_challenge_method' => 'S256'
        ]);

        // Redireciona o usuário para o endpoint de autorização
        header('Location: ' . $url);
        exit;
    }

    /**
     * Trata o callback recebido após a autenticação.
     * Valida o estado e troca o código de autorização por tokens.
     * Retorna as informações do usuário autenticado.
     */
    public function tratarCallback($code, $stateRecebido) {
        // Valida o estado recebido para evitar ataques CSRF
        if ($stateRecebido !== $_SESSION['oauth2state']) {
            exit('State inválido');
        }

        // Troca o código de autorização por tokens
        $response = $this->requisitarToken($code);
        $id_token = $response['id_token'] ?? null;
        $access_token = $response['access_token'] ?? null;

        // Verifica se os tokens foram recebidos
        if (!$id_token || !$access_token) {
            exit('Tokens não recebidos');
        }

        // Obtém as informações do usuário a partir dos tokens
        $usuario = $this->getUserInfoFromTokens($access_token, $id_token);
        return $usuario;
    }

    /**
     * Realiza a troca do código de autorização por tokens.
     * Utiliza o endpoint de token do provedor.
     */
    private function requisitarToken($code) {
        $postFields = [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->redirect_uri,
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'code_verifier' => $_SESSION['code_verifier'] ?? '' // Inclui o code_verifier para validação PKCE
        ];

        // Configura a requisição HTTP para o endpoint de token
        $ch = curl_init($this->token_endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postFields));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

        // Executa a requisição e retorna a resposta
        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    /**
     * Decodifica os tokens recebidos e retorna as informações do usuário autenticado.
     */
    public function getUserInfoFromTokens($access_token, $id_token) {
        // Obtém as chaves públicas do provedor
        $jwks = json_decode(file_get_contents($this->jwks_uri), true);
        $keys = JWK::parseKeySet($jwks);

        // Decodifica os tokens usando as chaves públicas
        $accessClaims = (array) JWT::decode($access_token, $keys);
        $idClaims = (array) JWT::decode($id_token, $keys);

        // Retorna as informações do usuário
        return [
            'cpf'                   => $idClaims['sub'] ?? null, // CPF do usuário autenticado
            'name'                  => $idClaims['name'] ?? null, // Nome cadastrado no Gov.br do usuário autenticado.
            'social_name'           => $idClaims['social_name'] ?? null, // Nome Social cadastrado no Gov.br do usuário autenticado.Aparecerá apenas se existir no cadastro
            'email'                 => $idClaims['email'] ?? null, // Endereço de e-mail cadastrado no Gov.br do usuário autenticado. Caso o atributo email_verified do ID_TOKEN tiver o valor false, o atributo email não virá no ID_TOKEN
            'email_verified'        => $idClaims['email_verified'] ?? null, // Confirma se o email foi validado no cadastro do Gov.br. Poderá ter o valor "true" ou "false"
            'phone_number'          => $idClaims['phone_number'] ?? null, // Número de telefone cadastrado no Gov.br do usuário autenticado. Caso o atributo phone_number_verified do ID_TOKEN tiver o valor false, o atributo phone_number não virá no ID_TOKEN
            'phone_number_verified' => $idClaims['phone_number_verified'] ?? null, // Confirma se o telefone foi validado no cadastro do Gov.br. Poderá ter o valor "true" ou "false"
            'picture'               => $idClaims['picture'] ?? null, // URL de acesso à foto do usuário cadastrada no Gov.br. A mesma é protegida e pode ser acessada passando o access token recebido.
            'cnpj'                  => $idClaims['cnpj'] ?? null, // CNPJ vinculado ao usuário autenticado. Atributo será preenchido quando autenticação ocorrer por certificado digital de pessoal jurídica.
            'scope'                 => $accessClaims['scope'] ?? null, // Escopos autorizados pelo provedor de autenticação.
            'amr'                   => $accessClaims['amr'] ?? null, // Listagem dos fatores de autenticação do usuário com detalhamento. Verificar nas observações para os detalhamentos.
            'client_id'             => $accessClaims['aud'] ?? null, // Client ID da aplicação onde o usuário se autenticou
            'iss'                   => $accessClaims['iss'] ?? null, // URL do provedor de autenticação que emitiu o token.
            'exp'                   => $accessClaims['exp'] ?? null, // Data/hora de expiração do token
            'iat'                   => $accessClaims['iat'] ?? null, // Data/hora em que o token foi emitido.
            'jti'                   => $accessClaims['jti'] ?? null // Identificador único do token, reconhecido internamente pelo provedor de autenticação.
        ];
    }

    // Métodos adicionais para acessar APIs do GovBR
    public function getFoto($url, $access_token) {
        return $this->httpGetBinary($url, $access_token);
    }

    public function getNiveis($cpf, $access_token) {
        return $this->httpGetJson("{$this->url_servicos}/confiabilidades/v3/contas/$cpf/niveis?response-type=ids", $access_token);
    }

    public function getCategorias($cpf, $access_token) {
        return $this->httpGetJson("{$this->url_servicos}/confiabilidades/v3/contas/$cpf/categorias?response-type=ids", $access_token);
    }

    public function getConfiabilidades($cpf, $access_token) {
        return $this->httpGetJson("{$this->url_servicos}/confiabilidades/v3/contas/$cpf/confiabilidades?response-type=ids", $access_token);
    }

    public function getEmpresasVinculadas($cpf, $access_token) {
        return $this->httpGetJson("{$this->url_servicos}/empresas/v2/empresas?filtrar-por-participante=$cpf", $access_token);
    }

    public function getDadosEmpresa($cpf, $cnpj, $access_token) {
        return $this->httpGetJson("{$this->url_servicos}/empresas/v2/empresas/$cnpj/participantes/$cpf", $access_token);
    }

    // Métodos auxiliares para requisições HTTP
    private function httpGetJson($url, $access_token) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            "Authorization: Bearer $access_token"
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response, true);
    }

    private function httpGetBinary($url, $access_token) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $access_token"
        ]);
        $data = curl_exec($ch);
        $mime = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);
        return "data:$mime;base64," . base64_encode($data);
    }
}