<?php

namespace DynDns;

use DynDns\Security\Services\TokenSignService;
use Silex\Api\ControllerProviderInterface;
use Silex\Application;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

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
        $factory->get('/myip', 'DynDns\AppController::clientIp');
        $factory->get('/domain/{domainId}', 'DynDns\AppController::getDomain');
        $factory->get('/token/{domainId}', 'DynDns\AppController::getToken');
        $factory->post('/update/{domainId}', 'DynDns\AppController::pushUpdate');
        $factory->post('/update-simple/{domainId}', 'DynDns\AppController::pushUpdateSimple');
        return $factory;
    }

    public function clientIp(Request $request)
    {
        return new Response($request->getClientIp());
    }

    public function getDomain(Application $app, $domainId)
    {
        $result = $app['db']->fetchAll('SELECT domain_id, name, type, content, ttl, change_date FROM records WHERE domain_id = ?',
            [$domainId]);

        return new JsonResponse($result);
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
                    ['type' => 'SOA', 'content' => '%ns% %noreply% %time% 60 60 60 60'],
                    ['type' => 'A', 'content' => $ipAddress]
                ]
            );
        } else {
            throw new \Exception("Invalid signature " . $signature);
        }

        return new Response("ok");
    }

    public function pushUpdateSimple(Application $app, Request $request, $domainId)
    {
        if (false === $app['password_file_service']->supportsPasswordAuth($domainId)) {
            throw new UnauthorizedHttpException("Domain does not support password auth.");
        }

        $password = $request->get('password');
        $ipAddress = $request->get('ip', $request->getClientIp());

        $verified = $app['password_file_service']->verify($domainId, $password);

        if ($verified) {
            $app['pdns']->updateRecord(
                $domainId,
                [
                    ['type' => 'SOA', 'content' => '%ns% %noreply% %time% 60 60 60 60'],
                    ['type' => 'A', 'content' => $ipAddress]
                ]
            );
        } else {
            throw new UnauthorizedHttpException("Invalid password.");
        }

        return new Response("ok" . PHP_EOL);
    }
}