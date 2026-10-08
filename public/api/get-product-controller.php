<?php

use OpenApi\Attributes as OAT;
use ReallySimpleJWT\Token;
use Slim\Psr7\Request;
use Slim\Psr7\Response;

class GetProductController
{
    #[OAT\Get(
        path: '/api/v1/product/{sku}',
        summary: 'Zeigt ein Produkt anhand ihrer sku.',
        tags: ['product'],
        parameters: [
            new OAT\Parameter(
                name: 'sku',
                in: 'path',
                required: true,
                description: 'sku from product',
                schema: new OAT\Schema(
                    type: 'string',
                    example: 'MAR-001'
                )
            )
        ],
        responses: [
        new OAT\Response(
            response: 200,
            description: 'Produkt als JSON.'
        ),
        new OAT\Response(
            response: 400,
            description: 'Ungültige Produkt-ID.'
        ),
        new OAT\Response(
            response: 401,
            description: 'Nicht authentifiziert.'
        ),
        new OAT\Response(
            response: 404,
            description: 'Produkt nicht gefunden.'
        )
        ]
    )]


    public static function getProduct(Request $request, Response $response, array $args)
    {

        global $database;
        global $config;

        $token = $_COOKIE["token"] ?? null;

        //if prüft Token und gibt 401 zurück wenn false
        if ($token == null || !Token::validate($token, $config["password"])) {
            return $response->withStatus(401);
        }
        // Ab hier ist der Token gültig

        $sku = trim($args["sku"]);
        if (strlen($sku) < 1 || strlen($sku) > 100) {
            $response->getBody()->write(json_encode([
                "error" => "SKU muss 1 bis 100 Zeichen enthalten."
            ]));

            return $response
                ->withStatus(400)
                ->withHeader("Content-Type", "application/json");
        }

        $statement = $database->prepare("SELECT * FROM product Where sku = ?");

        $statement->execute([$sku]);

        $result = $statement->get_result();

        // Prüft, ob die Produkt existiert
        $row_count = mysqli_num_rows($result);

        if ($row_count == 0) {
            $response->getBody()->write(json_encode(
                ["error" => "product do not exist :("]
            ));
            return $response
                ->withStatus(404)
                ->withHeader("Content-Type", "application/json");
        }

        // Daten auslesen
        $product = mysqli_fetch_assoc($result);

        $response->getBody()->write(json_encode($product));

        return $response
            ->withStatus(200)
            ->withHeader("Content-Type", "application/json");
    }
}