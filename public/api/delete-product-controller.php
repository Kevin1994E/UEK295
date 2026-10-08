<?php

use OpenApi\Attributes as OAT;
use ReallySimpleJWT\Token;
use Slim\Psr7\Request;
use Slim\Psr7\Response;

/**
 * This class handles the delete of products.
 */
class DeleteProductController
{
    #[OAT\Delete(
        path: '/api/v1/product/{sku}',
        summary: 'Löscht ein Produkt anhand ihrer SKU.',
        tags: ['product'],
        parameters: [
            new OAT\Parameter(
                name: 'sku',
                in: 'path',
                required: true,
                description: 'sku des Produkts',
                schema: new OAT\Schema(
                    type: 'string',
                    example: 'MAR-001'
                )
            )
        ],
        responses: [
            new OAT\Response(
                response: 204,
                description: 'Produkt erfolgreich gelöscht.'
            ),
            new OAT\Response(
                response: 400,
                description: 'Ungültige Produkt sku.'
            ),
            new OAT\Response(
                response: 401,
                description: 'Nicht authentifiziert.'
            ),
            new OAT\Response(
                response: 404,
                description: 'sku nicht gefunden.'
            )
        ]
    )]

    /**
     * This function validates the product SKU and deletes the product.
     * @param Request $request The HTTP request.
     * @param Response $response The HTTP response.
     * @param array $args The route arguments containing the product SKU.
     * @return Response The HTTP response.
     */
    public static function deleteProduct(Request $request, Response $response, array $args)
    {

        global $database;
        global $config;

        $token = $_COOKIE["token"] ?? null;

        //if prüft Token und gibt 401 zurück wenn false
        if ($token == null || !Token::validate($token, $config["password"])) {
            return $response->withStatus(401);
        }
        // Ab hier ist der Token gültig

        //Liest sku aus URL und entfernt Leerzeichen.
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

        // Prüft, ob das Produkt existiert
        $row_count = mysqli_num_rows($result);

        if ($row_count == 0) {
            $response->getBody()->write(json_encode(
                ["error" => "product do not exist :("]
            ));
            return $response
                ->withStatus(404)
                ->withHeader("Content-Type", "application/json");
        }


        $statement = $database->prepare("DELETE FROM product WHERE sku = ?");

        $statement->execute([$sku]);


        return $response
            ->withStatus(204);
    }
}