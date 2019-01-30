<?php

use Moserware\Skills\GameInfo;
use Moserware\Skills\Player;
use Moserware\Skills\Rating;
use Moserware\Skills\Team;
use Moserware\Skills\Teams;
use Moserware\Skills\TrueSkill\TwoTeamTrueSkillCalculator;

const DEFAULT_BETA = 4.1666666666666666666666666666667; // Default initial mean / 6
const DEFAULT_DRAW_PROBABILITY = 0.0;
const DEFAULT_DYNAMICS_FACTOR = 0.083333333333333333333333333333333; // Default initial mean / 300
const DEFAULT_INITIAL_MEAN = 25.0;
const DEFAULT_INITIAL_STANDARD_DEVIATION = 8.3333333333333333333333333333333; // Default initial mean / 3

$app->get('/location/{locationId}/event/{eventId}/series/{seriesId}/round', function($request, $response, $args) {
    if (
            isset($args['locationId'])
        &&  isset($args['eventId'])
        &&  isset($args['seriesId'])
    ) {
        $sth = $this->db->prepare("
             SELECT
                dsr.id_draft_series_round,
                dsr.id_draft_series,
                dsr.id_map,
                dsr.id_cabinet_side,
                dsr.series_round,
                dsr.is_winner
            FROM
                kqdraft.draft_series_round dsr,
                kqdraft.draft_series ds,
                kqdraft.draft_event de
            WHERE
                de.id_draft_event = {$args['eventId']}
            AND
                ds.id_draft_series = {$args['seriesId']}
            AND
                de.id_draft_event = ds.id_draft_event
        ");

        try {
            $sth->execute();
            $draftSeriesRounds = $sth->fetchAll();

            if ($sth->rowCount() >= 0) {
                return $this->response->WithJson($draftSeriesRounds, 200);
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

$app->post('/location/{locationId}/event/{eventId}/series/{seriesId}/round', function($request, $response, $args) {
    $parsedBody = $request->getParsedBody();

    $errorResponse = array(
        'error' => 'Invalid event ID, series ID supplied and/or missing fields id_map, id_cabinet_side, is_winner.'
    );

    if (
            isset($args['locationId'])
        &&  isset($args['eventId'])
        &&  isset($args['seriesId'])
        &&  isset($parsedBody['id_map'])
        &&  isset($parsedBody['id_cabinet_side'])
        &&  isset($parsedBody['is_winner'])
    ) {

        $gameInfo = new GameInfo(
            DEFAULT_BETA,
            DEFAULT_DRAW_PROBABILITY,
            DEFAULT_INITIAL_MEAN,
            DEFAULT_INITIAL_STANDARD_DEVIATION
        );
        $trueSkillCalc = new TwoTeamTrueSkillCalculator();

        $sth = $this->db->prepare("
            SELECT
                series_length, current_round
            FROM
                kqdraft.draft_series
            WHERE
                id_draft_series = {$args['seriesId']}
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

        $draftSeries = $sth->fetch();
        $currentRound = $draftSeries['current_round'];
        $seriesLength = $draftSeries['series_length'];

        if ($seriesLength < $currentRound + 1) {
            $isActive = "false";
            $nextRound = $currentRound;
        }
        else {
            $isActive = "true";
            $nextRound = $currentRound + 1;
        }

        $sth = $this->db->prepare("
            INSERT INTO kqdraft.draft_series_round (id_draft_series, id_map, id_cabinet_side, series_round, is_winner)
            VALUES ({$args['seriesId']}, {$parsedBody['id_map']}, {$parsedBody['id_cabinet_side']}, $currentRound, true);
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

        $losingCabSide = $parsedBody['id_cabinet_side'] === 1 ? 2 : 1;

        $sth = $this->db->prepare("
            INSERT INTO kqdraft.draft_series_round (id_draft_series, id_map, id_cabinet_side, series_round, is_winner)
            VALUES ({$args['seriesId']}, {$parsedBody['id_map']}, $losingCabSide, $currentRound, false);
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

        // Individual players and their Elo
        $sth = $this->db->prepare("
            SELECT
                p.id_player,
                p.elo,
                p.elo_sigma,
                dsp.id_cabinet_side
            FROM
                kqdraft.player p,
                kqdraft.draft_series_player dsp,
                kqdraft.draft_event_player dep
            WHERE
                p.id_player = dep.id_player
            AND
                dep.id_draft_event_player = dsp.id_draft_event_player
            AND
                dsp.id_draft_series = {$args['seriesId']}
        ");

        try {
            $sth->execute();

            if ($sth->rowCount() === 0) {
                return $this->response->WithJson($errorResponse, 400);
            }
        }
        catch (Exception $e) {
            return $this->response->WithJson($errorResponse, 400);
        }

        $seriesPlayers = $sth->fetchAll();

        $blueTeam = new Team();
        $goldTeam = new Team();

        // Update Elo for each player in the series using TrueSkill
        foreach ($seriesPlayers as $seriesPlayer) {

            if ($seriesPlayer['id_cabinet_side'] === 1) {
                $blueTeamPlayer = new Player($seriesPlayer['id_player']);
                $blueTeamPlayerRating = new Rating($seriesPlayer['elo'], $seriesPlayer['elo_sigma']);
                $blueTeam->addPlayer($blueTeamPlayer, $blueTeamPlayerRating);
            }
            else {
                $goldTeamPlayer = new Player($seriesPlayer['id_player']);
                $goldTeamPlayerRating = new Rating($seriesPlayer['elo'], $seriesPlayer['elo_sigma']);
                $goldTeam->addPlayer($goldTeamPlayer, $goldTeamPlayerRating);
            }

            if ($losingCabSide === 1) {
                $results = $trueSkillCalc->calculateNewRatings($gameInfo, [$blueTeam, $goldTeam], [1, 0]);
            }
            else {
                $results = $trueSkillCalc->calculateNewRatings($gameInfo, [$blueTeam, $goldTeam], [0, 1]);
            }

            foreach ($results->getAllPlayers() as $playerTrueSkill) {
                $playerId = $playerTrueSkill->getId();
                $playerRating = $results->getRating($playerTrueSkill);
                $playerMean = $playerRating->getMean();
                $playerStd = $playerRating->getStandardDeviation();

                $sth = $this->db->prepare("
                    UPDATE
                        kqdraft.player
                    SET
                        elo = $playerMean,
                        elo_sigma = $playerStd
                    WHERE
                        id_player = $playerId
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
        }

        // Determine new overall win/loss ratio
        $sth = $this->db->prepare("
            SELECT
                p.id_player,
                p.elo,
                wins.total_wins,
                total_games.games
            FROM
                kqdraft.player p
            LEFT JOIN
                (
                    SELECT
                        COUNT(*) as total_wins,
                        p.id_player
                    FROM
                        kqdraft.draft_series_round dsr,
                        kqdraft.draft_series_player dsp,
                        kqdraft.draft_event_player dep,
                        kqdraft.player p
                    WHERE
                        dsr.id_draft_series = dsp.id_draft_series
                    AND
                        dsp.id_draft_event_player = dep.id_draft_event_player
                    AND
                        dep.id_player = p.id_player
                    AND
                        dsr.is_winner = true
                    AND
                        dsp.id_cabinet_side = dsr.id_cabinet_side
                    GROUP BY
                        p.id_player
                ) wins
            ON p.id_player = wins.id_player,
                (
                    SELECT
                        COUNT(*) as games,
                        p.id_player
                    FROM
                        kqdraft.draft_series_round dsr,
                        kqdraft.draft_series_player dsp,
                        kqdraft.draft_event_player dep,
                        kqdraft.player p
                    WHERE
                        dsr.id_draft_series = dsp.id_draft_series
                    AND
                        dsp.id_draft_event_player = dep.id_draft_event_player
                    AND
                        dep.id_player = p.id_player
                    AND
                        dsp.id_cabinet_side = dsr.id_cabinet_side
                    GROUP BY
                        p.id_player
                ) total_games
            WHERE
                p.id_player
            IN
                (
                    SELECT
                        p.id_player
                    FROM
                        kqdraft.player p,
                        kqdraft.draft_series_player dsp,
                        kqdraft.draft_event_player dep
                    WHERE
                        p.id_player = dep.id_player
                    AND
                        dep.id_draft_event_player = dsp.id_draft_event_player
                    AND
                        dsp.id_draft_series = {$args['seriesId']}
                )
            GROUP BY
                p.id_player, wins.total_wins, total_games.games
        ");

        try {
            $sth->execute();

            if ($sth->rowCount() === 0) {
                return $this->response->WithJson($errorResponse, 400);
            }
        }
        catch (Exception $e) {
            return $this->response->WithJson($errorResponse, 400);
        }

        $playerGames = $sth->fetchAll();

        foreach ($playerGames as $playerGame) {
            $winPercentage = number_format($playerGame['total_wins'] / $playerGame['games'], 2)*100;

            $sth = $this->db->prepare("
                UPDATE
                    kqdraft.player
                SET
                    win_percentage = $winPercentage
                WHERE
                    id_player = {$playerGame['id_player']}
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

        $sth = $this->db->prepare("
            SELECT COUNT(*) AS side_count
            FROM kqdraft.draft_series_round
            WHERE id_draft_series = {$args['seriesId']}
            AND series_round = $currentRound
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

        $totalRecords = $sth->fetch();

        if ($totalRecords['side_count'] === 2) {
            $sth = $this->db->prepare("
                UPDATE
                    kqdraft.draft_series
                SET
                    current_round = $nextRound,
                    is_active = $isActive
                WHERE
                    id_draft_series = {$args['seriesId']}
            ");

            try {
                $sth->execute();

                if ($sth->rowCount() !== 1) {
                    return $this->response->withStatus(201);
                }
            }
            catch (Exception $e) {
                return $this->response->WithJson($errorResponse, 400);
            }
        }

        return $this->response->withStatus(201);
    }

    return $this->response->WithJson($errorResponse, 400);
});
