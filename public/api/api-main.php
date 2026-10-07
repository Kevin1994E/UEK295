<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use OpenApi\Attributes as OAT;

#[OAT\Info(
    title: "Meine API ÜK LB1",
    version: "1.0.0"
)]

class ApiMain {
public static function index(Request $request, Response $response, $args) {
$response->getBody()->write("Hello, world!");
return $response;
}
}