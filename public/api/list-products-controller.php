<?php

use OpenApi\Attributes as OAT;
use ReallySimpleJWT\Token;
use Slim\Psr7\Request;
use Slim\Psr7\Response;

/**
 * This class handles retrieving all products from the database.
 */
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

    /**
     * This function retrieves all products and returns them as JSON.
     * @param Request $request The HTTP request.
     * @param Response $response The HTTP response.
     * @return Response The HTTP response.
     */
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


        // Collect all rows in a list.
        $products = [];

        while ($product = mysqli_fetch_assoc($result)) {
            $products[] = $product;
        }

        $response->getBody()->write(json_encode($products));

        return $response
            ->withStatus(200)
            ->withHeader("Content-Type", "application/json");
    }
}