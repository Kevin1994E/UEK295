<?php

use OpenApi\Attributes as OAT;
use ReallySimpleJWT\Token;
use Slim\Psr7\Request;
use Slim\Psr7\Response;

class CreateUpdateProductController
{
    #[OAT\Put(
        path: '/api/v1/product/{sku}',
        summary: 'Es wird eine neue Produkt erstellt.',
        tags: ['cre_prod'],
        parameters: [
            new OAT\Parameter(
                name: 'sku',
                in: 'path',
                required: true,
                description: 'SKU des Produkts, 1 bis 100 Zeichen.',
                schema: new OAT\Schema(
                    type: 'string',
                    example: 'MAR-001'
                )
            )
        ],
        requestBody: new OAT\RequestBody(
            required: true,
            description: 'active, name, price und stock sind Pflichtfelder.',
            content: new OAT\JsonContent(
                required: ['active', 'name', 'price', 'stock'],
                properties: [
                    new OAT\Property(
                        property: 'active',
                        type: 'integer',
                        example: 1
                    ),
                    new OAT\Property(
                        property: 'id_category',
                        type: 'integer',
                        nullable: true,
                        example: 1
                    ),
                    new OAT\Property(
                        property: 'name',
                        type: 'string',
                        example: 'Mares X-Vision'
                    ),
                    new OAT\Property(
                        property: 'image',
                        type: 'string',
                        nullable: true,
                        example: 'https://test.com/images/mares-x-vision.jpg'
                    ),
                    new OAT\Property(
                        property: 'description',
                        type: 'string',
                        nullable: true,
                        example: 'Mares Scuba diving mask.'
                    ),
                    new OAT\Property(
                        property: 'price',
                        type: 'number',
                        example: 99.90
                    ),
                    new OAT\Property(
                        property: 'stock',
                        type: 'integer',
                        example: 25
                    )
                ]
            )
        ),
        responses: [
            new OAT\Response(
                response: 200,
                description: 'Produkt erfolgreich aktualisiert.'
            ),
            new OAT\Response(
                response: 201,
                description: 'Produkt erfolgreich erstellt.'
            ),
            new OAT\Response(
                response: 400,
                description: 'Fehler bei der Anfrage (falsche Werte). Nicht alle Plicht Eingabedaten eingegeben.'
            ),
            new OAT\Response(
                response: 401,
                description: 'Nicht Authentifiziert.'
            ),
            new OAT\Response(
                response: 404,
                description: 'Die angegebene Kategorie exestiert nicht.'
            )
        ]
    )]

    public static function createUpdateProducts(Request $request, Response $response, array $args)
    {

        global $database;
        global $config;

        $token = $_COOKIE["token"] ?? null;

        //if prüft Token und gibt 401 zurück wenn false
        if ($token == null || !Token::validate($token, $config["password"])) {
            return $response->withStatus(401);
        }
        // Ab hier ist der Token gültig

        $requestBody = $request->getParsedBody();

        //Prüft ob alle Pflichtfelder vorhanden sind im Json.
        if (!isset($requestBody['name'], $requestBody['active'], $requestBody["price"], $requestBody["stock"])) {
            $response->getBody()->write(json_encode(
                ["error" => "JSON pflichtfelder fehlen"]
            ));
            return $response
                ->withStatus(400)
                ->withHeader("Content-Type", "application/json");
        }

        $name = trim($requestBody['name']);
        $active = $requestBody['active'];
        $categoryId = $requestBody["id_category"] ?? null;
        $image = $requestBody["image"] ?? "";
        $description = $requestBody["description"] ?? "";
        $price = $requestBody["price"];
        $stock = $requestBody["stock"];

        //Validierungen

        //Prüfung SKU
        $sku = trim($args["sku"]);
        if (strlen($sku) < 1 || strlen($sku) > 100) {
            $response->getBody()->write(json_encode([
                "error" => "SKU muss 1 bis 100 Zeichen enthalten."
            ]));

            return $response
                ->withStatus(400)
                ->withHeader("Content-Type", "application/json");
        }

        //Prüfung active
        if ($active !== 0 && $active !== 1) {
            $response->getBody()->write(json_encode(
                ["error" => "Keine gültige Zahl (nur 1 oder 0 möglich)"]
            ));
            return $response
                ->withStatus(400)
                ->withHeader("Content-Type", "application/json");
        }

        //Prüfung name
        if (strlen($name) > 500 || strlen($name) < 1) {
            $response->getBody()->write(json_encode(
                ["error" => "Keine Zeichen oder zu viele Zeichen eingegeben. max.500 Zeichen"]
            ));
            return $response
                ->withStatus(400)
                ->withHeader("Content-Type", "application/json");
        }

        //Prüfung Stock
        if (!is_int($stock) || $stock < 0 || $stock > 2147483647) {
            $response->getBody()->write(json_encode([
                "error" => "Bestand muss eine ganze Zahl zwischen 0 und 2147483647 sein."
            ]));

            return $response
                ->withStatus(400)
                ->withHeader("Content-Type", "application/json");
        }

        // Prüfung Image grösse
        if (strlen($image) > 1000) {
            $response->getBody()->write(json_encode([
                "error" => "Image darf maximal 1000 Zeichen enthalten."
            ]));

            return $response
                ->withStatus(400)
                ->withHeader("Content-Type", "application/json");
        }

        //Prüfung Kategorie
        if (!is_int($categoryId) && $categoryId !== null) {
            $response->getBody()->write(json_encode(
                ["error" => "Muss eine nummer sein"]
            ));
            return $response
                ->withStatus(400)
                ->withHeader("Content-Type", "application/json");
        }

        if ($categoryId !== null) {
            $statement = $database->prepare("SELECT * FROM category WHERE category_id = ?");
            $statement->execute([$categoryId]);

            if (mysqli_num_rows($statement->get_result()) == 0) {
                $response->getBody()->write(json_encode(
                    ["error" => "category do not exist :("]
                ));
                return $response
                    ->withStatus(404)
                    ->withHeader("Content-Type", "application/json");
            }
        }

        //Prüfung Kategorie
        if (!is_double($price)) {
            $response->getBody()->write(json_encode(
                ["error" => "Muss eine dezimal zahl sein."]
            ));
            return $response
                ->withStatus(400)
                ->withHeader("Content-Type", "application/json");
        }

        // Find the product.
        $statement = $database->prepare("SELECT * FROM product WHERE sku = ?");
        $statement->execute([$sku]);

        $result = $statement->get_result();


        if (mysqli_num_rows($result) > 0) {
            // Update the product.
            $statement = $database->prepare("UPDATE product SET active = ?, id_category = ?, name = ?, image = ?, description = ?, price = ?, stock = ? WHERE sku = ?");

            $statement->execute([$active, $categoryId, $name, $image, $description, $price, $stock, $sku]);

            $status = 200;
            $message = "Is updated";
        } else {
            // Create the product.
            $statement = $database->prepare("INSERT INTO product (sku, active, id_category, name, image, description, price, stock) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

            $statement->execute([$sku, $active, $categoryId, $name, $image, $description, $price, $stock]);

            $status = 201;
            $message = "Is created";
        }

        $response->getBody()->write(json_encode([
            "success" => $message
        ]));

        return $response
            ->withStatus($status)
            ->withHeader("Content-Type", "application/json");
    }
}