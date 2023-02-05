<?php

/**
 * @var \Psr\Http\Message\ServerRequestInterface|null $request Capture Server request
 */

use Busarm\PhpMini\App;
use Busarm\PhpMini\Config;
use Busarm\PhpMini\Interfaces\RequestInterface;
use Busarm\PhpMini\Request;

require __DIR__ . '/../../vendor/autoload.php';

$config = (new Config())
    ->setAppPath(__DIR__)
    ->setEncryptionKey("asdgkasdfer@jsfrtv453ds!mfo")
    ->setCookieEncrypt(false)
    ->setHttpCheckCors(true)
    ->setHttpAllowAnyCorsDomain(true)
    ->setHttpAllowedCorsHeaders(['*'])
    ->setHttpAllowedCorsMethods(['GET'])
    ->setHttpSendAndContinue(false)
    ->setHttpSessionAutoStart(false);
$app = new App($config);

$app->get('ping')->call(function (App $app) {
    return 'success-callable-' . $app->env;
});

$app->get('test')->call(function (RequestInterface $req) {
    return [
        'headers' => $req->header()->all(),
        'server' => $req->server()->all(),
        'cookies' => $req->cookie()->all(),
    ];
});
return $app->run(Request::capture($request ?? null))->send($config->httpSendAndContinue);
