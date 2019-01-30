<?php

$app->get('/location/{locationId}/event/{eventId}/player', function($request, $response, $args) {
    if (isset($args['eventId']) && isset($args['locationId'])) {
        $sth = $this->db->prepare("
            SELECT
                dep.id_draft_event_player,
                dep.id_draft_event,
                dep.id_player,
                p.name as player_name,
                pre.name as player_preference,
                dep.is_active as player_active
            FROM
                kqdraft.draft_event_player dep,
                kqdraft.player p,
                kqdraft.preference pre
            WHERE
                dep.id_draft_event = {$args['eventId']}
            AND
                dep.id_player = p.id_player
            AND
                p.id_preference = pre.id_preference
        ");

        try {
            $sth->execute();
            $eventPlayers = $sth->fetchAll();

            if ($sth->rowCount() >= 0) {
                return $this->response->WithJson($eventPlayers, 200);
            }
        }
        catch (Exception $e) {
        }
    }

    $errorResponse = array(
        'error' => 'Invalid event ID supplied.'
    );

    return $this->response->WithJson($errorResponse, 400);
});

$app->post('/location/{locationId}/event/{eventId}/player', function($request, $response, $args) {
    $parsedBody = $request->getParsedBody();

    if (
            isset($args['eventId'])
        &&  isset($args['locationId'])
        &&  isset($parsedBody['id_player'])) {
        $sth = $this->db->prepare("
            INSERT INTO kqdraft.draft_event_player (id_draft_event, id_player)
            VALUES ({$args['eventId']}, {$parsedBody['id_player']})
        ");

        try {
            $sth->execute();

            if ($sth->rowCount() === 1) {
                return $this->response->withStatus(201);
            }
        }
        catch (Exception $e) {
        }
    }

    $errorResponse = array(
        'error' => 'Invalid id_player or event ID supplied.'
    );

    return $this->response->WithJson($errorResponse, 400);
});

$app->put('/location/{locationId}/event/{eventId}/player', function($request, $response, $args) {
    $parsedBody = $request->getParsedBody();

    if (
            isset($args['eventId'])
        &&  isset($args['locationId'])
        &&  isset($parsedBody['is_active'])
        &&  isset($parsedBody['id_player'])
    ) {
        if ($parsedBody['is_active'] === false) {
            $isActive = "false";
        }
        else {
            $isActive = "true";
        }

        $sth = $this->db->prepare("
            UPDATE kqdraft.draft_event_player
            SET is_active = $isActive
            WHERE id_draft_event = {$args['eventId']}
            AND id_player = {$parsedBody['id_player']}
        ");

        try {
            $sth->execute();

            if ($sth->rowCount() === 1) {
                return $this->response->withStatus(201);
            }
        }
        catch (Exception $e) {
        }
    }

    $errorResponse = array(
        'error' => 'Invalid id_player, is_active value or event ID supplied.'
    );

    return $this->response->WithJson($errorResponse, 400);
});
