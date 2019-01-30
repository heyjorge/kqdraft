<?php

$app->get('/location/{locationId}/player', function ($request, $response, $args) {
    if (isset($args['locationId'])) {
        $sth = $this->db->prepare("
            SELECT * FROM kqdraft.player
            WHERE is_active = TRUE AND id_location = {$args['locationId']}
            ORDER BY name ASC
        ");
        $sth->execute();
        $players = $sth->fetchAll();

        return $this->response->withJson($players);
    }

    $errorResponse = array(
        'error' => 'Missing locationId.'
    );

    return $this->response->withJson($errorResponse, 400);
});

$app->post('/location/{locationId}/player', function ($request, $response, $args) {
    $parsedBody = $request->getParsedBody();

    if (    isset($args['locationId'])
        &&  isset($parsedBody['name'])
        &&  isset($parsedBody['id_preference'])
    ) {
        $sth = $this->db->prepare("
            INSERT INTO kqdraft.player (name, id_location, id_preference, is_active)
            VALUES ('{$parsedBody['name']}', {$args['locationId']}, {$parsedBody['id_preference']}, true)
        ");

        try {
            $sth->execute();
            return $this->response->withStatus(201);
        }
        catch (Exception $e) {
        }
    }

    $errorResponse = array(
        'error' => 'Missing required fields, bad values for location or preference, or duplicate player.'
    );

    return $this->response->withJson($errorResponse, 400);
});

$app->put('/location/{locationId}/player/{playerId}', function($request, $response, $args) {
    $parsedBody = $request->getParsedBody();

    if (
            isset($args['playerId'])
        &&  isset($args['locationId'])
        &&  isset($parsedBody['id_preference'])
        &&  isset($parsedBody['name'])
    ) {
        $sth = $this->db->prepare("
            UPDATE kqdraft.player
            SET id_location = {$args['locationId']},
                id_preference = {$parsedBody['id_preference']},
                name = '{$parsedBody['name']}'
            WHERE
                id_player = {$args['playerId']}
        ");

        try {
            $sth->execute();

            if ($sth->rowCount() === 1) {
                return $this->response->withStatus(200);
            }
        }
        catch (Exception $e) {
        }
    }

    $errorResponse = array(
        'error' => 'Invalid playerId supplied or missing id_location, id_preference, or name.'
    );

    return $this->response->WithJson($errorResponse, 400);
});

$app->delete('/location/{locationId}/player/{playerId}', function($request, $response, $args) {
    if (isset($args['playerId']) && isset($args['locationId'])) {
        $sth = $this->db->prepare("
            UPDATE kqdraft.player
            SET
                is_active = false
            WHERE
                id_player = {$args['playerId']}
        ");

        try {
            $sth->execute();

            if ($sth->rowCount() === 1) {
                return $this->response->withStatus(200);
            }
        }
        catch (Exception $e) {
        }
    }

    $errorResponse = array(
        'error' => 'Invalid playerId supplied.'
    );

    return $this->response->WithJson($errorResponse, 400);
});
