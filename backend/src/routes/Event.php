<?php

$app->get('/location/{locationId}/event', function($request, $response, $args) {

    if (isset($args['locationId'])) {
        $sth = $this->db->prepare("
            SELECT * FROM kqdraft.draft_event
            WHERE id_location = {$args['locationId']}
            AND date_end IS NULL
        ");

        try {
            $sth->execute();
            $events = $sth->fetchAll();

            return $this->response->WithJson($events, 200);
        }
        catch (Exception $e) {
        }
    }

    $errorResponse = array(
        'error' => 'Invalid id_location supplied.'
    );

    return $this->response->WithJson($errorResponse, 400);
});

$app->get('/location/{locationId}/event/{eventId}', function($request, $response, $args) {

    if (isset($args['locationId']) && isset($args['eventId'])) {
        $sth = $this->db->prepare("
            SELECT * FROM kqdraft.draft_event
            WHERE id_draft_event = {$args['eventId']}
        ");

        try {
            $sth->execute();
            $events = $sth->fetchAll();

            return $this->response->WithJson($events, 200);
        }
        catch (Exception $e) {
        }
    }

    $errorResponse = array(
        'error' => 'Invalid id_location supplied.'
    );

    return $this->response->WithJson($errorResponse, 400);
});

$app->post('/location/{locationId}/event', function($request, $response, $args) {
    if (isset($args['locationId'])) {
        $sth = $this->db->prepare("
            INSERT INTO kqdraft.draft_event (id_location) VALUES ({$args['locationId']})
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
        'error' => 'Invalid id_location supplied.'
    );

    return $this->response->WithJson($errorResponse, 400);
});

$app->put('/location/{locationId}/event/{draftEventId}', function($request, $response, $args) {
    $parsedBody = $request->getParsedBody();

    if (isset($args['locationId']) && isset($args['draftEventId'])) {
        if ($parsedBody['date_end'] === null) {
            $dateEnd = 'null';
        }
        else {
            $dateEnd = "'" . $parsedBody['date_end'] . "'";
        }

        $sth = $this->db->prepare("
            UPDATE kqdraft.draft_event SET date_end = $dateEnd
            WHERE id_draft_event = {$args['draftEventId']}
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
        'error' => 'Invalid id_draft_event or date_end supplied.'
    );

    return $this->response->WithJson($errorResponse, 400);
});

$app->delete('/location/{locationId}/event/{draftEventId}', function($request, $response, $args) {
    if (isset($args['draftEventId'])) {
        $sth = $this->db->prepare("
            DELETE FROM kqdraft.draft_event WHERE id_draft_event = {$args['draftEventId']}
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
        'error' => 'Invalid id_draft_event supplied in URL.'
    );

    return $this->response->WithJson($errorResponse, 400);
});
