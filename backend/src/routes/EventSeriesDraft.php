<?php

function getAvgElo($seriesPlayers) {
    $avgElo = 0;

    foreach($seriesPlayers as $seriesPlayerKey => $seriesPlayer) {
        $avgElo = $avgElo + $seriesPlayer['elo'];

    }

    return $avgElo / count($seriesPlayers);
}

function getClosestElo($seriesPlayer, $playerArray) {
    $closestPlayer = null;
    foreach ($playerArray as $player) {
        if (
                (
                        $closestPlayer === null
                    ||  abs($seriesPlayer['elo'] - $closestPlayer['elo']) > abs($player['elo'] - $seriesPlayer['elo'])
                )
            &&  $player['id_player'] !== $seriesPlayer['id_player']
        ) {
            $closestPlayer = $player;
        }
    }
   return $closestPlayer;
}

function arrayOrderByElo()
{
    $args = func_get_args();
    $data = array_shift($args);
    foreach ($args as $n => $field) {
        if (is_string($field)) {
            $tmp = array();
            foreach ($data as $key => $row)
                $tmp[$key] = $row[$field];
            $args[$n] = $tmp;
            }
    }
    $args[] = &$data;
    call_user_func_array('array_multisort', $args);
    return array_pop($args);
}

function pickPlayer(&$seriesPlayers, &$availablePlayers) {
    array_push($seriesPlayers, $availablePlayers[0]);
    array_splice($availablePlayers, 0, 1);

    foreach ($availablePlayers as $availablePlayerKey => $availablePlayer) {
        foreach ($seriesPlayers as $seriesPlayerKey => $seriesPlayer) {
            if (
                    $seriesPlayer['series_count'] > $availablePlayer['series_count']
                &&  $seriesPlayer['last_series'] >= $availablePlayer['last_series']
                ||
                    (
                            $seriesPlayer['series_count'] === $availablePlayer['series_count']
                        &&  $seriesPlayer['last_series'] > $availablePlayer['last_series']
                    )
                &&  !in_array($availablePlayer, $seriesPlayers)
            ) {
                array_splice($seriesPlayers, array_search($seriesPlayer, $seriesPlayers), 1);
                array_splice($availablePlayers, array_search($availablePlayer, $availablePlayers), 1);
                array_push($seriesPlayers, $availablePlayer);
            }
            elseif (
                    $seriesPlayer['series_count'] === $availablePlayer['series_count']
                &&  $seriesPlayer['last_series'] === $availablePlayer['last_series']
                &&  !in_array($availablePlayer, $seriesPlayers)
            ) {
                array_push($seriesPlayers, $availablePlayer);
                array_splice($availablePlayers, array_search($availablePlayer, $availablePlayers), 1);
            }
        }
    }
}

$app->post('/location/{locationId}/event/{eventId}/series/draft', function($request, $response, $args) {

    $errorResponse = array(
        'error' => 'Invalid event ID, series ID supplied and/or not enough drones or queens registered to the event.'
    );

    if (    isset($args['locationId'])
        &&  isset($args['eventId'])
    ) {

        // Create new series.
        $sth = $this->db->prepare("
            INSERT INTO kqdraft.draft_series (id_draft_event) VALUES ({$args['eventId']})
        ");

        try {
            $sth->execute();

            if ($sth->rowCount() === 0) {
                return $this->response->WithJson('Failed to create new draft series.', 400);
            }
        }
        catch (Exception $e) {
        }

        $sth = $this->db->prepare("
            SELECT
                id_draft_series
            FROM
                kqdraft.draft_series
            WHERE
                id_draft_event = {$args['eventId']}
            ORDER BY
                id_draft_series
            DESC LIMIT 1
        ");

        try {
            $sth->execute();

            if ($sth->rowCount() !== 1) {
                return $this->response->WithJson('No series could be found', 400);
            }
        }
        catch (Exception $e) {
        }

        $draftSeries = $sth->fetchAll();
        $draftSeriesId = $draftSeries[0]['id_draft_series'];

        // Get queen players sorted by least amount of series played,
        // but have not played again since a full rotation
        $sth = $this->db->prepare("
            SELECT
                p.id_player,
                dep.id_draft_event_player,
                pre.id_preference,
                pre.name as player_preference,
                p.elo,
                COUNT(dsp.id_draft_series_player) as series_count,
                COALESCE(MAX(dsp.id_draft_series), 0) as last_series
            FROM
                kqdraft.preference pre,
                kqdraft.draft_event_player dep
            LEFT JOIN
                kqdraft.draft_series_player dsp
            ON
                dep.id_draft_event_player = dsp.id_draft_event_player
            LEFT JOIN
                kqdraft.player p
            ON
                dep.id_player = p.id_player
            WHERE
                p.id_preference = pre.id_preference
            AND
                dep.id_draft_event = {$args['eventId']}
            AND
                dep.is_active = true
            AND
                pre.id_preference
                IN (
                    SELECT id_preference
                    FROM kqdraft.preference
                    WHERE name = 'queen' OR name = 'queen_flex'
                )
            GROUP BY
                p.id_player,
                dep.id_draft_event_player,
                p.elo,
                pre.name,
                pre.id_preference
            ORDER BY
                last_series ASC, series_count ASC, elo DESC
        ");

        try {
            $sth->execute();

            if ($sth->rowCount() < 2) {
                return $this->response->WithJson('Need at least two queens or queen flex players to auto-draft.', 400);
            }
        }
        catch (Exception $e) {
        }

        $queenPlayers = $sth->fetchAll();
        $seriesQueens = array();

        // Get queens that have played the least amount of series, and haven't played in a while
        pickPlayer($seriesQueens, $queenPlayers);

        if (count($seriesQueens) < 2) {
            pickPlayer($seriesQueens, $queenPlayers);
            $finalQueens = array($seriesQueens[0], getClosestElo($seriesQueens[0], $seriesQueens));
        }
        else {
            $finalQueens = array($seriesQueens[array_rand($seriesQueens)]);
            array_push($finalQueens, getClosestElo($finalQueens[0], $seriesQueens));
        }

        // Get drone players sorted by least amount of series played,
        // but have not played again since a full rotation
        $sth = $this->db->prepare("
            SELECT
                p.id_player,
                dep.id_draft_event_player,
                pre.id_preference,
                pre.name as player_preference,
                p.elo,
                COUNT(dsp.id_draft_series_player) as series_count,
                COALESCE(MAX(dsp.id_draft_series), 0) as last_series
            FROM
                kqdraft.preference pre,
                kqdraft.draft_event_player dep
            LEFT JOIN
                kqdraft.draft_series_player dsp
            ON
                dep.id_draft_event_player = dsp.id_draft_event_player
            LEFT JOIN
                kqdraft.player p
            ON
                dep.id_player = p.id_player
            WHERE
                p.id_preference = pre.id_preference
            AND
                dep.id_draft_event = {$args['eventId']}
            AND
                dep.is_active = true
            AND
                pre.id_preference
                IN (
                    SELECT id_preference
                    FROM kqdraft.preference
                    WHERE name = 'drone'
                    OR name = 'queen_flex'
                )
            AND
                dep.id_draft_event_player != {$finalQueens[0]['id_draft_event_player']}
            AND
                dep.id_draft_event_player != {$finalQueens[1]['id_draft_event_player']}
            GROUP BY
                p.id_player,
                dep.id_draft_event_player,
                p.elo,
                pre.name,
                pre.id_preference
            ORDER BY
                last_series ASC, series_count ASC, elo DESC
        ");

        try {
            $sth->execute();

            if ($sth->rowCount() < 8) {
                return $this->response->WithJson('Need at least 8 drones or flex queens to auto-draft.', 400);
            }
        }
        catch (Exception $e) {
        }

        $dronePlayers = $sth->fetchAll();
        $seriesDrones = array();
        $finalDrones = array();

        while (count($finalDrones) < 8) {
            if (count($dronePlayers) > 0) {
                pickPlayer($seriesDrones, $dronePlayers);
            }

            if ((count($finalDrones) + count($seriesDrones)) <= 8) {
                $finalDrones = array_merge($finalDrones, $seriesDrones);
                $seriesDrones = array();
            }
            else {
                $avgElo = getAvgElo($finalDrones);

                $closestSeriesDrone = getClosestElo(
                    array(
                        'id_player' => null,
                        'elo' => $avgElo
                    ),
                    $seriesDrones
                );

                array_push($finalDrones, $seriesDrones[0]);
                array_splice($seriesDrones, 0, 1);

            }
        }

        $teamOne = array();
        $teamTwo = array();
        $teamOneElo = array();
        $teamTwoElo = array();

        // Randomly choose cab side
        $queenOneCabSideId = rand(1, 2);
        $queenTwoCabSideId = $queenOneCabSideId === 1 ? 2 : 1;
        $queenCabSides = array($queenOneCabSideId, $queenTwoCabSideId);

        foreach ($finalQueens as $cabSide => $finalQueen) {
            $sth = $this->db->prepare("
                INSERT INTO kqdraft.draft_series_player (id_draft_series, id_draft_event_player, id_cabinet_side, is_queen)
                VALUES ($draftSeriesId, {$finalQueen['id_draft_event_player']}, {$queenCabSides[$cabSide]}, true);
            ");

            if ($queenCabSides[$cabSide] === 1) {
                array_push($teamOneElo, $finalQueen['elo']);
            }
            else {
                array_push($teamTwoElo, $finalQueen['elo']);
            }

            try {
                $sth->execute();

                if ($sth->rowCount() !== 1) {
                    return $this->response->WithJson($errorResponse, 400);
                }
            }
            catch (Exception $e) {
                return $this->response->WithJson($errorResponse, 400);
            }
        }

        $finalDronesByElo = arrayOrderByElo($finalDrones, 'elo', SORT_DESC);

        while (count($teamOne) < 4 || count($teamTwo) < 4) {
            if (array_sum($teamOneElo)/count($teamOneElo) <= array_sum($teamTwoElo)/count($teamTwoElo)) {
                $teamOnePlayer = array_shift($finalDronesByElo);
                array_push($teamOne, $teamOnePlayer);
                array_push($teamOneElo, $teamOnePlayer['elo']);

                $teamTwoPlayer = array_pop($finalDronesByElo);
                array_push($teamTwo, $teamTwoPlayer);
                array_push($teamTwoElo, $teamTwoPlayer['elo']);
            }
            else {
                $teamOnePlayer = array_pop($finalDronesByElo);
                array_push($teamOne, $teamOnePlayer);
                array_push($teamOneElo, $teamOnePlayer['elo']);

                $teamTwoPlayer = array_shift($finalDronesByElo);
                array_push($teamTwo, $teamTwoPlayer);
                array_push($teamTwoElo, $teamTwoPlayer['elo']);
            }
        }

        foreach ($teamOne as $teamOneDrone) {
            $sth = $this->db->prepare("
                INSERT INTO kqdraft.draft_series_player (id_draft_series, id_draft_event_player, id_cabinet_side, is_queen)
                VALUES ($draftSeriesId, {$teamOneDrone['id_draft_event_player']}, 1, false);
            ");

            try {
                $sth->execute();

                if ($sth->rowCount() !== 1) {
                    return $this->response->WithJson($errorResponse, 400);
                }
            }
            catch (Exception $e) {
                return $this->response->WithJson($errorResponse, 400);
            }
        }

        foreach ($teamTwo as $teamTwoDrone) {
            $sth = $this->db->prepare("
                INSERT INTO kqdraft.draft_series_player (id_draft_series, id_draft_event_player, id_cabinet_side, is_queen)
                VALUES ($draftSeriesId, {$teamTwoDrone['id_draft_event_player']}, 2, false);
            ");

            try {
                $sth->execute();

                if ($sth->rowCount() !== 1) {
                    return $this->response->WithJson($errorResponse, 400);
                }
            }
            catch (Exception $e) {
                print_r($sth->errorInfo());
                return $this->response->WithJson($errorResponse, 400);
            }
        }

        return $this->response->withStatus(201);
    }

    return $this->response->WithJson($errorResponse, 400);
});