<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use OpenApi\Attributes as OAT;

#[OAT\Info(
    title: "Meine API ÜK LB1",
    version: "1.0.0"
)]

/**
 * This class contains the main API functions.
 */
class ApiMain
{
    /**
     * This function returns the API welcome message.
     * @param Request $request The HTTP request.
     * @param Response $response The HTTP response.
     * @param array $args 
     * @return Response the HTTP response.
     */
    public static function index(Request $request, Response $response, $args)
    {
        $response->getBody()->write("Hello, world!");
        return $response;
    }
}