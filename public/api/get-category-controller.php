<?php

use OpenApi\Attributes as OAT;
use ReallySimpleJWT\Token;
use Slim\Psr7\Request;
use Slim\Psr7\Response;

/**
 * This class handles retrieving categories from the database.
 */
class GetCategoryController
{
    #[OAT\Get(
        path: '/api/v1/category/{category_id}',
        summary: 'Zeigt eine Kategorie anhand ihrer ID.',
        tags: ['categorie'],
        parameters: [
            new OAT\Parameter(
                name: 'category_id',
                in: 'path',
                required: true,
                description: 'ID der Kategorie',
                schema: new OAT\Schema(
                    type: 'integer',
                    example: '1'
                )
            )
        ],
        responses: [
            new OAT\Response(
                response: 200,
                description: 'Kategorie als JSON.'
            ),
            new OAT\Response(
                response: 400,
                description: 'Ungültige Kategorie-ID.'
            ),
            new OAT\Response(
                response: 401,
                description: 'Nicht authentifiziert.'
            ),
            new OAT\Response(
                response: 404,
                description: 'Kategorie nicht gefunden.'
            )
        ]
    )]

    /**
     * This function validates the category ID and returns the category as JSON.
     * @param Request $request The HTTP request.
     * @param Response $response The HTTP response.
     * @param array $args The route arguments containing the category ID.
     * @return Response The HTTP response.
     */
    public static function getCategory(Request $request, Response $response, array $args)
    {

        global $database;
        global $config;

        $token = $_COOKIE["token"] ?? null;

        //if prüft Token und gibt 401 zurück wenn false
        if ($token == null || !Token::validate($token, $config["password"])) {
            return $response->withStatus(401);
        }
        // Ab hier ist der Token gültig

        //Liest die category_id aus der URL.
        $categoryId = $args["category_id"];

        $categoryId = filter_var(
            $args["category_id"],
            FILTER_VALIDATE_INT,
            ["options" => ["min_range" => 1, "max_range" => 2147483647]]
        );

        if ($categoryId === false) {
            $response->getBody()->write(json_encode(
                ["error" => "Ungültige Kategorie ID"]
            ));

            return $response
                ->withStatus(400)
                ->withHeader("Content-Type", "application/json");
        }

        $statement = $database->prepare("SELECT * FROM category Where category_id = ?");

        $statement->execute([$categoryId]);

        $result = $statement->get_result();

        // Prüft, ob die Kategorie existiert
        $row_count = mysqli_num_rows($result);

        if ($row_count == 0) {
            $response->getBody()->write(json_encode(
                ["error" => "category do not exist :("]
            ));
            return $response
                ->withStatus(404)
                ->withHeader("Content-Type", "application/json");
        }

        // Daten auslesen
        $category = mysqli_fetch_assoc($result);

        $response->getBody()->write(json_encode($category));

        return $response
            ->withStatus(200)
            ->withHeader("Content-Type", "application/json");
    }
}