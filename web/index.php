<?php

use DynDns\Security\Services\TokenSignService;
use DynDns\Services\PDNSService;

require_once __DIR__ . '/../vendor/autoload.php';

define("__ROOT__", __DIR__ . "/..");

$app = new Silex\Application();
$app['debug'] = false;

$app->register(
    new GeckoPackages\Silex\Services\Config\ConfigServiceProvider(),
    array(
        'config.dir' => __ROOT__ . '/config',
        'config.format' => '%key%.yml'
    )
);

$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options' => $app['config']->get('database'),
));

$app['token_signer'] = function () use ($app) {
    return new TokenSignService($app);
};
$app['pdns'] = function () use ($app) {
    return new PDNSService($app);
};

$app->mount('/', new DynDns\AppController());

$app->run();