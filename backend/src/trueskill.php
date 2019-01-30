<?php

require __DIR__ . '/../vendor/autoload.php';

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

$dbh = new PDO("pgsql:dbname=kqdraft;host=localhost", 'postgres', '');

$gameInfo = new GameInfo(DEFAULT_BETA, DEFAULT_DRAW_PROBABILITY, DEFAULT_INITIAL_MEAN, DEFAULT_INITIAL_STANDARD_DEVIATION);

$playerOne = new Player('P1');
$defaultRating = new Rating(25.0, DEFAULT_INITIAL_STANDARD_DEVIATION);
$playerTwo = new Player('P2');
$playerThree = new Player('P3');
$playerFour = new Player('P4');

$teamOne = new Team();
$teamTwo = new Team();

$teamOne->addPlayer($playerOne, $defaultRating);
$teamOne->addPlayer($playerTwo, $defaultRating);

$teamTwo->addPlayer($playerThree, $defaultRating);
$teamTwo->addPlayer($playerFour, $defaultRating);

$teams = new Teams($teamOne, $teamTwo);

$calc = new TwoTeamTrueSkillCalculator();

$results = $calc->calculateNewRatings($gameInfo, [$teamOne, $teamTwo], [1, 0]);
$results = $calc->calculateNewRatings($gameInfo, [$teamOne, $teamTwo], [1, 0]);

$sth = $dbh->prepare('SELECT id_player, elo, elo_sigma, name FROM kqdraft.player');
$sth->execute();

$players = $sth->fetchAll();

$sth = $dbh->prepare('
	SELECT
		dsr.id_draft_series,
		dsr.id_cabinet_side,
		dsr.id_map
	FROM
		kqdraft.draft_series ds,
		kqdraft.draft_series_round dsr
	WHERE
		ds.id_draft_event IN (
			SELECT
				de.id_draft_event
			FROM
				kqdraft.draft_event de
			WHERE
				de.id_location = 1
		)
	AND
		dsr.is_winner = true
	AND
		dsr.id_draft_series = ds.id_draft_series
	GROUP BY
		dsr.id_draft_series,
		dsr.id_cabinet_side,
		dsr.id_map
	ORDER BY
		dsr.id_draft_series ASC
');

$sth->execute();

$seriesResults = $sth->fetchAll();

foreach($seriesResults as $series) {
	$blueTeam = new Team();
	$goldTeam = new Team();

	$sth = $dbh->prepare("
		SELECT
			ds.id_draft_series,
			p.id_player,
			p.elo,
			p.elo_sigma,
			p.name,
			dsp.id_cabinet_side
		FROM
			kqdraft.player p,
			kqdraft.draft_event_player dep,
			kqdraft.draft_series_player dsp,
			kqdraft.draft_series ds
		WHERE
			p.id_player = dep.id_player
		AND
			dep.id_draft_event_player = dsp.id_draft_event_player
		AND
			dsp.id_draft_series = ds.id_draft_series
		AND
			ds.id_draft_series = {$series['id_draft_series']}
	");

	$sth->execute();

	$seriesPlayers = $sth->fetchAll();

	foreach($seriesPlayers as $seriesPlayer) {
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

	}

	if ($series['id_cabinet_side'] === 1) {
		$results = $calc->calculateNewRatings($gameInfo, [$blueTeam, $goldTeam], [0, 1]);
	}
	else {
		$results = $calc->calculateNewRatings($gameInfo, [$blueTeam, $goldTeam], [1, 0]);
	}

	foreach ($results->getAllPlayers() as $seriesPlayer) {
		$playerId = $seriesPlayer->getId();
		$playerRating = $results->getRating($seriesPlayer);
		$playerMean = $playerRating->getMean();
		$playerStd = $playerRating->getStandardDeviation();

		echo "$playerId - $playerMean - $playerStd\n";
		$sth = $dbh->prepare("
			UPDATE
				kqdraft.player
			SET
				elo = $playerMean,
				elo_sigma = $playerStd
			WHERE
				id_player = $playerId
		");

		$sth->execute();
	}
}
