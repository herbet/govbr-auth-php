<?php
// src/GovBrAuth.php

namespace GovBr;

use Firebase\JWT\JWT;
use Firebase\JWT\JWK;

class GovBrAuth {
    private $client_id;
    private $client_secret;
    private $redirect_uri;
    private $authorization_endpoint = 'https://sso.staging.acesso.gov.br/authorize';
    private $token_endpoint = 'https://sso.staging.acesso.gov.br/token';
    private $jwks_uri = 'https://sso.staging.acesso.gov.br/jwk';
    private $url_servicos = 'https://api.staging.acesso.gov.br';

    public function __construct($client_id, $client_secret, $redirect_uri) {
        $this->client_id = $client_id;
        $this->client_secret = $client_secret;
        $this->redirect_uri = $redirect_uri;

        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function iniciarAutenticacao() {
        $state = bin2hex(random_bytes(8));
        $nonce = bin2hex(random_bytes(8));
        $_SESSION['oauth2state'] = $state;
        $_SESSION['oauth2nonce'] = $nonce;

        $url = $this->authorization_endpoint . '?' . http_build_query([
            'response_type' => 'code',
            'client_id' => $this->client_id,
            'redirect_uri' => $this->redirect_uri,
            'scope' => 'openid email profile govbr_confiabilidades',
            'state' => $state,
            'nonce' => $nonce
        ]);

        header('Location: ' . $url);
        exit;
    }

    public function tratarCallback($code, $stateRecebido) {
        if ($stateRecebido !== $_SESSION['oauth2state']) {
            exit('State invalido');
        }

        $response = $this->requisitarToken($code);
        $id_token = $response['id_token'] ?? null;
        $access_token = $response['access_token'] ?? null;

        if (!$id_token || !$access_token) {
            exit('Tokens não recebidos');
        }

        $usuario = $this->getUserInfoFromTokens($access_token, $id_token);
        return $usuario;
    }

    private function requisitarToken($code) {
        $postFields = [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->redirect_uri,
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret
        ];

        $ch = curl_init($this->token_endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postFields));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    public function getUserInfoFromTokens($access_token, $id_token) {
        $jwks = json_decode(file_get_contents($this->jwks_uri), true);
        $keys = JWK::parseKeySet($jwks);

        $accessClaims = (array) JWT::decode($access_token, $keys);
        $idClaims = (array) JWT::decode($id_token, $keys);

        return [
            'cpf' => $idClaims['sub'] ?? null,
            'name' => $idClaims['name'] ?? null,
            'social_name' => $idClaims['social_name'] ?? null,
            'email' => $idClaims['email'] ?? null,
            'email_verified' => $idClaims['email_verified'] ?? null,
            'phone_number' => $idClaims['phone_number'] ?? null,
            'phone_number_verified' => $idClaims['phone_number_verified'] ?? null,
            'picture' => $idClaims['picture'] ?? null,
            'cnpj' => $idClaims['cnpj'] ?? null,
            'cnpj_certificate_name' => $idClaims['cnpj_certificate_name'] ?? null,
            'scope' => $accessClaims['scope'] ?? null,
            'amr' => $accessClaims['amr'] ?? null,
            'client_id' => $accessClaims['aud'] ?? null
        ];
    }

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