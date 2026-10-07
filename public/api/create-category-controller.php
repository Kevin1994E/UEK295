<?php

use OpenApi\Attributes as OAT;
use ReallySimpleJWT\Token;
use Slim\Psr7\Request;
use Slim\Psr7\Response;

class CreateCategoryController
{
    #[OAT\Post(
        path: '/api/v1/category',
        summary: 'Es wird eine neue Kategorie erstellt.',
        tags: ['cre_cat'],
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
                response: 201,
                description: 'Kategorie erfolgreich erstellt.'
            ),
            new OAT\Response(
                response: 400,
                description: 'Fehler bei der Anfrage.'
            ),
            new OAT\Response(
                response: 401,
                description: 'Nicht Authentifiziert.'
            )
        ]
    )]

    public static function createCategory(Request $request, Response $response)
    {

        global $database;
        global $config;

        $token = $_COOKIE["token"] ?? null;

        //if prüft Token und gibt 401 zurück wenn false
        if ($token == null || !Token::validate($token, $config["password"])) {
            return $response->withStatus(401);
        }
        // Ab hier ist der Token gültig

        $statement = $database->prepare("INSERT INTO category (active, name) VALUES (?, ?)");

        $requestBody = $request->getParsedBody();

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

        $statement->execute([$active, $name]);

        $response->getBody()->write(json_encode(
            ["succsess" => "Is created"]
        ));


        return $response
            ->withStatus(201)
            ->withHeader("Content-Type", "application/json");
    }
}