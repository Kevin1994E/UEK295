<?php
use OpenApi\Attributes as OAT;
use ReallySimpleJWT\Token;
use Slim\Psr7\Request;
use Slim\Psr7\Response;

class authenticator
{
    #[OAT\Post(
        path: '/api/v1/authenticate',
        summary: 'Benutzer wird anhand von Benutzername und Passwort verifiziert.',
        tags: ['authenticator'],
        requestBody: new OAT\RequestBody(
            required: true,
            description: 'JSON-Body muss username und password als String enthalten.',
            content: new OAT\JsonContent(
                properties: [
                    new OAT\Property(
                        property: 'username',
                        type: 'string',
                        example: 'Kevin'
                    ),
                    new OAT\Property(
                        property: 'password',
                        type: 'string',
                        example: 'CsBe12'
                    )
                ]
            )
        ),
        responses: [
            new OAT\Response(
                response: 200,
                description: 'Authentifizierung erfolgreich. Cookie ist für eine Stunde gültig.'
            ),
            new OAT\Response(
                response: 401,
                description: 'Authentifizierung nicht erfolgreich. Benutzername oder Passwort ist falsch.'
            )
        ]
    )]

    public static function autenticate(Request $request, Response $response, $args)
    {
        global $config;
        $requestBody = $request->getParsedBody();

        //Check if credentials are not valide.
        if ($requestBody["username"] != $config["username"] || $requestBody["password"] != $config["password"]) {
            //Return error
            return $response->withStatus(401);
        }

        //Generate Token
        $token = Token::create($config["username"], $config["password"], time() + 3600, "localhost");

        //Return Token as cookie
        setcookie("token", $token, time() + 3600);

        $response = $response->withHeader("content-type", "application/json");
        $response->getBody()->write(json_encode(["success" => true]));
        return $response->withStatus(200);
    }
}

