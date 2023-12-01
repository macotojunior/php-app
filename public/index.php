<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Integrado\Database;

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();

$app->addErrorMiddleware(true, true, true);

$db = new Database(
    getenv('DATABASE_HOST'),
    getenv('DATABASE_NAME'),
    getenv('DATABASE_USER'),
    getenv('DATABASE_PASS')    
);

$app->get('/', function (Request $request, Response $response, $args) {
    $response->getBody()->write("Hello world!");
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
});

$app->run();