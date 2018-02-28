<?php

use Symfony\Component\HttpFoundation\Request;

require __DIR__.'/../vendor/autoload.php';
if (PHP_VERSION_ID < 70000) {
    include_once __DIR__.'/../var/bootstrap.php.cache';
}

$kernel = new AppKernel('prod', false);
if (PHP_VERSION_ID < 70000) {
    $kernel->loadClassCache();
}
//$kernel = new AppCache($kernel);

// When using the HttpCache, you need to call the method in your front controller instead of relying on the configuration parameter
//Request::enableHttpMethodParameterOverride();
$request = Request::createFromGlobals();
Request::setTrustedProxies(
// trust *all* requests
    array('127.0.0.1', $request->server->get('REMOTE_ADDR')),
    Request::HEADER_X_FORWARDED_ALL
);
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
