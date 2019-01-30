<?php

$app->get('/location/{locationId}/event/{eventId}/series', function($request, $response, $args) {
    if (isset($args['locationId']) && isset($args['eventId'])) {
        $sth = $this->db->prepare("
             SELECT
                ds.id_draft_series,
                ds.id_draft_event,
                ds.series_length,
                ds.current_round,
                ds.is_best_of,
                ds.is_active
            FROM
                kqdraft.draft_series ds,
                kqdraft.draft_event de
            WHERE
                de.id_draft_event = {$args['eventId']}
            AND
                ds.id_draft_event = de.id_draft_event
            AND
                de.id_location = {$args['locationId']}
            ORDER BY
                ds.id_draft_series DESC
        ");

        try {
            $sth->execute();
            $draftSeries = $sth->fetchAll();

            if ($sth->rowCount() >= 0) {
                return $this->response->WithJson($draftSeries, 200);
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

$app->post('/location/{locationId}/event/{eventId}/series', function($request, $response, $args) {
    if (isset($args['locationId']) && isset($args['eventId'])) {
        $sth = $this->db->prepare("
            INSERT INTO kqdraft.draft_series (id_draft_event)
            VALUES ({$args['eventId']})
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
        'error' => 'Invalid event ID supplied.'
    );

    return $this->response->WithJson($errorResponse, 400);
});

$app->put('/location/{locationId}/event/{eventId}/series/{seriesId}', function($request, $response, $args) {
    $parsedBody = $request->getParsedBody();
    $setConditionArray = array();

    if (
            isset($args['eventId'])
        &&  isset($args['seriesId'])
        &&  isset($args['locationId'])
        &&  (
                isset($parsedBody['series_length'])
            ||  isset($parsedBody['current_round'])
            ||  isset($parsedBody['is_best_of'])
            ||  isset($parsedBody['is_active'])
            )
    ) {
        $seriesLength = isset($parsedBody['series_length']) ? " series_length = {$parsedBody['series_length']} " : "";
        $currentRound = isset($parsedBody['current_round']) ? " current_round = {$parsedBody['current_round']} " : "";

        if (isset($parsedBody['is_best_of']) && $parsedBody['is_best_of'] === true) {
            $isBestOf = " is_best_of = true ";
        }
        else {
            $isBestOf = " is_best_of = false ";
        }

        if (isset($parsedBody['is_active']) && $parsedBody['is_active'] === true) {
            $isActive = " is_active = true ";
        }
        else {
            $isActive = " is_active = false ";
        }

        array_push($setConditionArray, $seriesLength, $currentRound, $isBestOf, $isActive);
        $setConditionSql = implode(",", $setConditionArray);

        $sth = $this->db->prepare("
            UPDATE kqdraft.draft_series
            SET
                $setConditionSql
            WHERE
                id_draft_series = {$args['seriesId']}
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
        'error' => 'Invalid event ID, series ID supplied and/or no fields'
            . ' (series_length, current_round, is_best_of, is_active) sent to be updated.'
    );

    return $this->response->WithJson($errorResponse, 400);
});

$app->delete('/location/{locationId}/event/{eventId}/series/{seriesId}', function($request, $response, $args) {

    if (
            isset($args['locationId'])
        &&  isset($args['eventId'])
        &&  isset($args['seriesId'])
    ) {
        $sth = $this->db->prepare("
            DELETE FROM kqdraft.draft_series
            WHERE id_draft_series = {$args['seriesId']}
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
        'error' => 'Invalid event ID, or series ID supplied.'
    );

    return $this->response->WithJson($errorResponse, 400);
});
