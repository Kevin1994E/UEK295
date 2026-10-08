<?php

use OpenApi\Attributes as OAT;
use ReallySimpleJWT\Token;
use Slim\Psr7\Request;
use Slim\Psr7\Response;

class ListProductsController
{
    #[OAT\Get(
        path: '/api/v1/products',
        summary: 'Listet alle Produkte auf.',
        tags: ['product'],
        responses: [
            new OAT\Response(
                response: 200,
                description: 'Alle Produkte als JSON.'
            ),
            new OAT\Response(
                response: 401,
                description: 'Nicht authentifiziert.'
            )
        ]
    )]


    public static function listProducts(Request $request, Response $response)
    {

        global $database;
        global $config;

        $token = $_COOKIE["token"] ?? null;

        //if prüft Token und gibt 401 zurück wenn false
        if ($token == null || !Token::validate($token, $config["password"])) {
            return $response->withStatus(401);
        }
        // Ab hier ist der Token gültig

        $statement = $database->prepare("SELECT * FROM product");

        $statement->execute();

        $result = $statement->get_result();


        // Collect all rows.
        $categories = [];

        while ($category = mysqli_fetch_assoc($result)) {
            $categories[] = $category;
        }

        $response->getBody()->write(json_encode($categories));

        return $response
            ->withStatus(200)
            ->withHeader("Content-Type", "application/json");
    }
}