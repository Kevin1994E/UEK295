<?php

use Slim\Factory\AppFactory;
 
//$database = new mysqli("localhost", "root", "", "uek295_lb1");

require __DIR__ . "/../vendor/autoload.php";
require_once __DIR__ . "/api/api-main.php";
require_once __DIR__ . "/api/authenticator.php";

$config = json_decode(file_get_contents(__DIR__ . "/../config.json"), true);

$app = AppFactory::create();

$app->setBasePath("/api/v1");

$app->addBodyParsingMiddleware();

$app->post("/authenticate", [
    authenticator::class,
    "autenticate"
]);

$app->run();