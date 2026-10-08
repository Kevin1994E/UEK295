<?php

use OpenApi\Attributes as OAT;
use ReallySimpleJWT\Token;
use Slim\Psr7\Request;
use Slim\Psr7\Response;

class ListCategoriesController
{
    #[OAT\Get(
        path: '/api/v1/categories',
        summary: 'Listet alle Kategorien auf.',
        tags: ['list_cat'],
        responses: [
            new OAT\Response(
                response: 200,
                description: 'Alle Kategorien als JSON.'
            ),
            new OAT\Response(
                response: 401,
                description: 'Nicht authentifiziert.'
            )
        ]
    )]


    public static function listCategories(Request $request, Response $response)
    {

        global $database;
        global $config;

        $token = $_COOKIE["token"] ?? null;

        //if prüft Token und gibt 401 zurück wenn false
        if ($token == null || !Token::validate($token, $config["password"])) {
            return $response->withStatus(401);
        }
        // Ab hier ist der Token gültig

        $statement = $database->prepare("SELECT * FROM category");

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