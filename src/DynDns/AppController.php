<?php

namespace DynDns;

use DynDns\Security\Services\TokenSignService;
use Silex\Api\ControllerProviderInterface;
use Silex\Application;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AppController implements ControllerProviderInterface
{
    /**
     * Returns routes to connect to the given application.
     *
     * @param Application $app An Application instance
     *
     * @return ControllerCollection A ControllerCollection instance
     */
    public function connect(Application $app)
    {
        $factory = $app['controllers_factory'];

        // Routes are defined here
        $factory->get('/', 'DynDns\AppController::clientIp');
        $factory->get('/{domainId}', 'DynDns\AppController::getDomain');
        $factory->get('/token/{domainId}', 'DynDns\AppController::getToken');
        $factory->post('/update/{domainId}', 'DynDns\AppController::pushUpdate');
        return $factory;
    }

    public function clientIp(Request $request)
    {
        return new Response($request->getClientIp());
    }

    public function getDomain(Application $app, $domainId)
    {
        $result = $app['db']->executeQuery('SELECT `content` FROM records WHERE domain_id = ? AND `type`="A"',
            [$domainId]);

        $ips = [];
        while ($row = $result->fetchColumn()) {
            $ips[] = $row;
        }

        if (count($ips) === 0) {
            return new Response();
        }
        if (count($ips) === 1) {
            return new Response($ips[0]);
        }
        return new JsonResponse($ips);
    }

    public function getToken(Application $app, Request $request, $domainId)
    {
        $tokenSignService = new TokenSignService($app);

        $token = $tokenSignService->getToken($domainId);

        if (!is_null($request->query->get('pure'))) {
            return new Response($token);
        }

        return new JsonResponse(
            ['token' => $token, 'ip' => $request->getClientIp()]
        );
    }

    public function pushUpdate(Application $app, Request $request, $domainId)
    {
        $signature = $request->get('signature');
        $ipAddress = $request->get('ip', $request->getClientIp());

        $verified = $app['token_signer']->verify($domainId, $signature, $ipAddress);

        if ($verified) {
            $app['pdns']->updateRecord(
                $domainId,
                [
                    ['type' => 'SOA', 'content' => 'ns.linetech.hr noreply.linetech.hr %time% 60 60 60 60'],
                    ['type' => 'A', 'content' => $ipAddress]
                ]
            );
        } else {
            throw new \Exception("Invalid signature");
        }

        return new Response("ok");
    }
}