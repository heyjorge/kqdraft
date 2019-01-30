<?php

$app->get('/location/{locationId}/event/{eventId}/series/{seriesId}/player', function($request, $response, $args) {
    if (
            isset($args['locationId'])
        &&  isset($args['eventId'])
        &&  isset($args['seriesId'])
    ) {
        $sth = $this->db->prepare("
             SELECT
                dsp.id_draft_series_player,
                dsp.id_draft_series,
                dsp.id_draft_event_player,
                dsp.id_cabinet_side,
                p.name as player_name,
                pre.name as player_preference,
                dsp.is_queen
            FROM
                kqdraft.draft_series_player dsp,
                kqdraft.player p,
                kqdraft.preference pre,
                kqdraft.draft_event de,
                kqdraft.draft_series ds,
                kqdraft.draft_event_player dep
            WHERE
                de.id_draft_event = {$args['eventId']}
            AND
                ds.id_draft_series = {$args['seriesId']}
            AND
                de.id_draft_event = ds.id_draft_event
            AND
                dsp.id_draft_series = ds.id_draft_series
            AND
                dep.id_draft_event_player = dsp.id_draft_event_player
            AND
                dep.id_draft_event = de.id_draft_event
            AND
                pre.id_preference = p.id_preference
            AND
                p.id_player = dep.id_player
        ");

        try {
            $sth->execute();
            $draftSeriesPlayers = $sth->fetchAll();

            if ($sth->rowCount() >= 0) {
                return $this->response->WithJson($draftSeriesPlayers, 200);
            }
        }
        catch (Exception $e) {
        }
    }

    $errorResponse = array(
        'error' => 'Invalid event ID or series ID supplied.'
    );

    return $this->response->WithJson($errorResponse, 400);
});

$app->post('/location/{locationId}/event/{eventId}/series/{seriesId}/player', function($request, $response, $args) {
    $parsedBody = $request->getParsedBody();

    if (
            isset($args['locationId'])
        &&  isset($args['eventId'])
        &&  isset($args['seriesId'])
        &&  isset($parsedBody['id_draft_event_player'])
        &&  isset($parsedBody['id_cabinet_side'])
        &&  isset($parsedBody['is_queen'])
    ) {
        if ($parsedBody['is_queen'] === true) {
            $isQueen = "true";
        }
        else {
            $isQueen = "false";
        }

        $sth = $this->db->prepare("
            INSERT INTO kqdraft.draft_series_player (id_draft_series, id_draft_event_player, id_cabinet_side, is_queen)
            VALUES ({$args['seriesId']}, {$parsedBody['id_draft_event_player']}, {$parsedBody['id_cabinet_side']}, $isQueen)
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
        'error' => 'Invalid event ID, series ID supplied and/or missing fields id_draft_event_player, id_cabinet_side, is_queen.'
    );

    return $this->response->WithJson($errorResponse, 400);
});

$app->put('/location/{locationId}/event/{eventId}/series/{seriesId}/player/{seriesPlayerId}', function($request, $response, $args) {
    $parsedBody = $request->getParsedBody();

    if (
            isset($args['locationId'])
        &&  isset($args['eventId'])
        &&  isset($args['seriesId'])
        &&  isset($args['seriesPlayerId'])
        &&  (
                    isset($parsedBody['id_cabinet_side'])
                ||  isset($parsedBody['is_queen'])
            )
    ) {
        $setConditionArray = array();

        $cabinetSide = isset($parsedBody['id_cabinet_side']) ? " id_cabinet_side = {$parsedBody['id_cabinet_side']} " : '';

        if (isset($parsedBody['is_queen']) && $parsedBody['is_queen'] === true) {
            $isQueen = " is_queen = true ";
        }
        else {
            $isQueen = " is_queen = false ";
        }

        array_push($setConditionArray, $cabinetSide, $isQueen);
        $setConditionSql = implode(",", $setConditionArray);

        $sth = $this->db->prepare("
            UPDATE
                kqdraft.draft_series_player
            SET
                $setConditionSql
            WHERE
                id_draft_series_player = {$args['seriesPlayerId']}
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
        'error' => 'Invalid event ID, series ID supplied and/or missing fields id_draft_event_player, id_cabinet_side, is_queen.'
    );

    return $this->response->WithJson($errorResponse, 400);
});

$app->delete('/location/{locationId}/event/{eventId}/series/{seriesId}/player/{seriesPlayerId}', function($request, $response, $args) {

    if (
            isset($args['locationId'])
        &&  isset($args['eventId'])
        &&  isset($args['seriesId'])
        &&  isset($args['seriesPlayerId'])
    ) {
        $sth = $this->db->prepare("
            DELETE FROM kqdraft.draft_series_player
            WHERE id_draft_series_player = {$args['seriesPlayerId']}
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
        'error' => 'Invalid event ID, series ID, or series Player ID supplied.'
    );

    return $this->response->WithJson($errorResponse, 400);
});
