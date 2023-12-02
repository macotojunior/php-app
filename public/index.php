<?php

use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Integrado\Database;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response as Psr7Response;

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();

$app->addErrorMiddleware(true, true, true);

$log = new Logger('php-app');
$log->pushHandler(new StreamHandler('php://stdout'));

$db = new Database(
    getenv('DATABASE_HOST'),
    getenv('DATABASE_NAME'),
    getenv('DATABASE_USER'),
    getenv('DATABASE_PASS')    
);

$client = new \Predis\Client('tcp://172.17.0.5:6379', [
    'parameters' => [
        'password' => '123456'
    ]
]);

$cacheRequest = function (Request $request, RequestHandlerInterface $handler) use ($client, $log) {
    $cacheName = $request->getUri()->getPath();
    $cacheValue = $client->get($cacheName);

    if ($cacheValue !== NULL) {
        $log->info('cache response');
        $response = new Psr7Response();
        $response->getBody()->write($cacheValue);
        return $response->withHeader('Content-Type', 'application/json')
                        ->withStatus(StatusCodeInterface::STATUS_OK);
    } else {
        $log->info('database response');
        $response = $handler->handle($request);
        $client->set($cacheName, (string) $response->getBody());
        $client->expire($cacheName, 10);
    }

    return $response;

};

$logRequest = function (Request $request, RequestHandlerInterface $handler) use ($log) {
    $log->info("Início request ".$request->getUri()->getPath());
    $response = $handler->handle($request);
    $log->info("Fim request ".$request->getUri()->getPath());
    return $response;
};

$app->get('/', function (Request $request, Response $response, $args) {
    $response->getBody()->write("Olá");
    return $response;
});

$app->get('/teste', function (Request $request, Response $response, $args) {
    $response->getBody()->write("Teste!");
    return $response;
});

$app->get('/users', function (Request $request, Response $response, array $args) use ($db) {
    $stmt = $db->prepare("select * from user");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_OBJ);
    $response->getBody()->write(json_encode($users));
    return $response
             ->withHeader('Content-Type', 'application/json')
             ->withStatus(200);
})->add($cacheRequest);

$app->add($logRequest);
$app->run();