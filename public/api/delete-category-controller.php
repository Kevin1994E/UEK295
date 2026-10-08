<?php

use OpenApi\Attributes as OAT;
use ReallySimpleJWT\Token;
use Slim\Psr7\Request;
use Slim\Psr7\Response;

/**
 * This class handles the delete of categories.
 */
class DeleteCategoryController
{
    #[OAT\Delete(
        path: '/api/v1/category/{category_id}',
        summary: 'Löscht eine Kategorie anhand ihrer ID.',
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
                response: 204,
                description: 'Kategorie erfolgreich gelöscht.'
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
     * This function validates the category ID and deletes the category.
     * @param Request $request The HTTP request.
     * @param Response $response The HTTP response.
     * @param array $args The route arguments containing the category ID.
     * @return Response The HTTP response.
     */
    public static function deleteCategory(Request $request, Response $response, array $args)
    {

        global $database;
        global $config;

        $token = $_COOKIE["token"] ?? null;

        //if prüft Token und gibt 401 zurück wenn false
        if ($token == null || !Token::validate($token, $config["password"])) {
            return $response->withStatus(401);
        }
        // Ab hier ist der Token gültig

        $categoryId = $args["category_id"];

        //Prüfung der Kategorie (Ganzzahl in der erlaubten Range).
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


        $statement = $database->prepare("DELETE FROM category WHERE category_id = ?");

        $statement->execute([$categoryId]);


        return $response
            ->withStatus(204);
    }
}