<?php

$app->get('/location', function ($request, $response, $args) {
    $sth = $this->db->prepare("SELECT * FROM kqdraft.location WHERE is_active = TRUE");
    $sth->execute();
    $locations = $sth->fetchAll();

    return $this->response->withJson($locations);
});

$app->get('/location/{locationId}', function ($request, $response, $args) {
    if (isset($args['locationId'])) {
        $sth = $this->db->prepare("
            SELECT *
            FROM kqdraft.location
            WHERE is_active = TRUE
            AND id_location = {$args['locationId']}
        ");
        $sth->execute();
        $location = $sth->fetchAll();

        return $this->response->withJson($location);
    }

    $errorResponse = array(
        'error' => 'Invalid locationId supplied'
    );

    return $this->response->WithJson($errorResponse, 400);
});

$app->post('/location', function($request, $response, $args) {
    $parsedBody = $request->getParsedBody();

    if (isset($parsedBody['name'])) {
        $sth = $this->db->prepare("
            INSERT INTO kqdraft.location (name) VALUES ('{$parsedBody['name']}')
        ");

        try {
            $sth->execute();
            return $this->response->withStatus(201);
        }
        catch (Exception $e) {
        }
    }

    $errorResponse = array(
        'error' => 'Duplicate location or excessive name length for location.'
    );

    return $this->response->WithJson($errorResponse, 400);
});

$app->put('/location/{locationId}', function ($request, $response, $args) {
    $parsedBody = $request->getParsedBody();

    if (isset($args['locationId']) && isset($parsedBody['name'])) {
        $sth = $this->db->prepare("
            UPDATE kqdraft.location SET name = '{$parsedBody['name']}'
            WHERE id_location = {$args['locationId']}
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
        'error' => 'Invalid locationId supplied or excessive name length for location'
    );

    return $this->response->WithJson($errorResponse, 400);
});

$app->delete('/location/{locationId}', function($request, $response, $args) {
    if (isset($args['locationId'])) {
        $sth = $this->db->prepare("
            UPDATE kqdraft.location SET is_active = FALSE WHERE id_location = {$args['locationId']}
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
        'error' => 'Invalid locationId supplied.'
    );

    return $this->response->WithJson($errorResponse, 400);
});
