<?php

namespace DynDns\Security\Services;

use Silex\Application;

class TokenSignService
{
    /**
     * @var Application
     */
    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function loadDomainKey($domainId)
    {
        $keyPath = __ROOT__ . '/storage/keys/' . $domainId . ".pem";

        $fp = fopen($keyPath, "r");
        $cert = fread($fp, 8192);
        fclose($fp);

        return $cert;
    }

    protected function constructData($domainId, $ipAddress)
    {
        // get token
        $token = $this->getToken($domainId);

        // format: {token}|{domainId}|{ipAddress}
        $data = sprintf("%s|%s|%s", $token, $domainId, $ipAddress);
        return $data;
    }

    /**
     * @param $domainId int
     * @param $signature string base64-encoded signature
     * @param $ipAddress string
     * @return bool
     * @throws \Exception In case of OpenSSL error
     */
    public function verify($domainId, $signature, $ipAddress)
    {
        $domainId = (int)$domainId;
        $signature = base64_decode($signature);
        $key = $this->loadDomainKey($domainId);

        $data = $this->constructData($domainId, $ipAddress);

        $verification = openssl_verify($data . "\n", $signature, $key, OPENSSL_ALGO_SHA256);

        if ($verification === 1) {
            $this->removeToken($domainId);
            return true;
        } elseif ($verification === 0) {
            return false;
        }

        throw new \Exception("Error with OpenSSL library.");
    }

    protected function generateToken($domainId)
    {
        $random = bin2hex(openssl_random_pseudo_bytes(16));

        return base64_encode(hash('sha256', $random . $domainId, true));
    }

    /**
     * @param int $domainId
     * @return string token
     */
    public function getToken($domainId)
    {
        $domainId = (int)$domainId;

        // check if entry exists in database
        $token = $this->app['db']->fetchColumn(
            "SELECT token FROM dyndns_token WHERE domain_id = ?",
            [$domainId],
            0
        );

        if ($token === false) {
            $token = $this->generateToken($domainId);
            $this->app['db']->insert(
                'dyndns_token',
                [
                    'domain_id' => $domainId,
                    'token' => $token,
                    'time' => time(),
                ]
            );
        }

        return $token;
    }

    public function removeToken($domainId)
    {
        $domainId = (int)$domainId;
        $this->app['db']->delete('dyndns_token', ['domain_id' => $domainId]);
    }
}