<?php

use OpenApi\Attributes as OAT;
use ReallySimpleJWT\Token;
use Slim\Psr7\Request;
use Slim\Psr7\Response;

class UpdateCategoryController
{
    #[OAT\Patch(
        path: '/api/v1/category/{category_id}',
        summary: 'Update der Felder active und name einer Kategorie.',
        tags: ['upd_cat'],
        parameters: [
        new OAT\Parameter(
            name: 'category_id',
            in: 'path',
            required: true,
            description: 'ID der Kategorie.',
            schema: new OAT\Schema(
                type: 'integer',
                example: 1
            )
        )
        ],
        requestBody: new OAT\RequestBody(
        required: true,
        description: 'JSON-Body muss active und name enthalten.',
        content: new OAT\JsonContent(
            properties: [
                new OAT\Property(
                    property: 'active',
                    type: 'integer',
                    example: 1
                ),
                new OAT\Property(
                    property: 'name',
                    type: 'string',
                    example: 'Dive Masks'
                )   
            ]
        )
        ),
        responses: [
            new OAT\Response(
                response: 200,
                description: 'Kategorie erfolgreich angepasst.'
            ),
            new OAT\Response(
                response: 400,
                description: 'Fehler bei der Anfrage. Ungültige ID oder Eingabedaten.'
            ),
            new OAT\Response(
                response: 401,
                description: 'Nicht Authentifiziert.'
            ),
            new OAT\Response(
            response: 404,
            description: 'Kategorie nicht gefunden. :('
            )
        ]
    )]

    public static function updateCategory(Request $request, Response $response, array $args)
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

        $categoryId = filter_var(
            $args["category_id"],
            FILTER_VALIDATE_INT,
            ["options" => ["min_range" => 1, "max_range" => 2147483647]]
        );

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

        if ($categoryId === false) {
            $response->getBody()->write(json_encode(
                ["error" => "Ungültige Kategorie ID"]
            ));

            return $response
                ->withStatus(400)
                ->withHeader("Content-Type", "application/json");
        }

        $statement = $database->prepare("UPDATE category SET active = ?, name = ? WHERE category_id = ?");

        $requestBody = $request->getParsedBody();

        //Prüft ob alle Pflichtfelder vorhanden sind im Json.
        if (!isset($requestBody['name'], $requestBody['active'])) {
            $response->getBody()->write(json_encode(
                ["error" => "JSON pflichtfelder fehlen"]
            ));
            return $response
                ->withStatus(400)
                ->withHeader("Content-Type", "application/json");
        }

        $name = trim($requestBody['name']);
        $active = $requestBody['active'];

        if ($active !== 0 && $active !== 1) {
            $response->getBody()->write(json_encode(
                ["error" => "Keine gültige Zahl (nur 1 oder 0 möglich)"]
            ));
            return $response
                ->withStatus(400)
                ->withHeader("Content-Type", "application/json");
        }

        if (strlen($name) > 500 || strlen($name) < 1) {
            $response->getBody()->write(json_encode(
                ["error" => "Keine Zeichen oder zu viele Zeichen eingegeben. max.500 Zeichen"]
            ));
            return $response
                ->withStatus(400)
                ->withHeader("Content-Type", "application/json");
        }

        $statement->execute([$active, $name, $categoryId]);

        $response->getBody()->write(json_encode(
            ["succsess" => "Is updated"]
        ));


        return $response
            ->withStatus(201)
            ->withHeader("Content-Type", "application/json");
    }
}