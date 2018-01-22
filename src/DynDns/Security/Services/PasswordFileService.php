<?php

namespace DynDns\Security\Services;

use RuntimeException;
use Silex\Application;

class PasswordFileService
{
    /**
     * @var Application
     */
    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * @param $domainId
     * @return string
     */
    private function loadDomainPassword($domainId)
    {
        $keyPath = __ROOT__ . '/storage/keys/' . $domainId . ".password";

        $fp = fopen($keyPath, "r");
        $cert = fread($fp, 8192);
        fclose($fp);
        if ($cert === false) {
            throw new RuntimeException("Could not load data from password file.");
        }

        return $cert;
    }

    /**
     * @param int $domainId
     * @return bool
     */
    public function supportsPasswordAuth($domainId)
    {
        $passwordPath = __ROOT__ . '/storage/keys/' . $domainId . ".password";

        return file_exists($passwordPath);
    }

    /**
     * @param string $password
     * @return string
     */
    public function hash($password)
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    /**
     * @param int $domainId
     * @param string $md5Password MD5 hashed password
     * @return bool
     */
    public function verify($domainId, $md5Password)
    {
        $expected = $this->loadDomainPassword($domainId);

        return password_verify($md5Password, $expected);
    }
}