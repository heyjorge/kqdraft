angular.module('series.ctrl', [])

.controller('seriesCtrl',
    [
        '$scope',
        '$state',
        '$stateParams',
        'kqDraftFactory',
        'seriesService',
        'location',
        'event',
        'players',
        'eventPlayers',
        'availableEventPlayers',
        'series',
        'seriesPlayers',
        'currentSeriesKey',
        'bluePlayers',
        'goldPlayers',
        'preferences',
        'maps',
        'cabsides',
        function (
            $scope,
            $state,
            $stateParams,
            kqDraftFactory,
            seriesService,
            location,
            event,
            players,
            eventPlayers,
            availableEventPlayers,
            series,
            seriesPlayers,
            currentSeriesKey,
            bluePlayers,
            goldPlayers,
            preferences,
            maps,
            cabsides
        ) {
            $scope.preferences = preferences.data;
            $scope.players = players.data;
            $scope.eventPlayers = eventPlayers.data;
            $scope.series = series.data;
            $scope.seriesPlayers = seriesPlayers.data;
            $scope.currentSeriesKey = currentSeriesKey;
            $scope.cabsides = cabsides.data;
            $scope.newPlayer = {};
            $scope.bluePlayers = bluePlayers;
            $scope.goldPlayers = goldPlayers;
            $scope.maps = maps.data;
            $scope.mapWin = {};
            $scope.cabsideWin = {};
            $scope.availableEventPlayers = availableEventPlayers;
            $scope.managePlayersOpen = $stateParams.toggleManagePlayers;
            $scope.firstIncompleteSeries = null;


            angular.forEach($scope.series, function(series, seriesKey) {
                if ($scope.firstIncompleteSeries === null) {
                    if (series.is_active === true) {
                        $scope.firstIncompleteSeries = seriesKey;
                    }
                }
            });

            if ($scope.series.length > 0) {
                $scope.isCurrentSeriesActive = $scope.series[$scope.currentSeriesKey].is_active;
                $scope.currentSeries = $scope.series[$scope.currentSeriesKey];
                $scope.disableNewButton = false;

                $scope.disableNextButton =
                        parseInt($stateParams.seriesId) === $scope.series[$scope.series.length-1].id_draft_series ?
                        true : false;
                $scope.disablePrevButton =
                        parseInt($stateParams.seriesId) === $scope.series[0].id_draft_series ?
                        true : false;
            }
            else {
                $scope.isCurrentSeriesActive = false;
                $scope.currentSeries = {};
                $scope.disableNewButton = $scope.eventPlayers.length < 10 ? true : false;
                $scope.disableNextButton = true;
                $scope.disablePrevButton = true;
            }

            if ($stateParams.toggleManagePlayers === 'true') {
                $scope.isAddPlayerCollapsed = false;
            }
            else {
                $scope.isAddPlayerCollapsed = true;
            }

            if (
                $scope.series.length > 1 &&
                $scope.firstIncompleteSeries !== null &&
                $scope.series[$scope.currentSeriesKey].id_draft_series > $scope.series[$scope.firstIncompleteSeries].id_draft_series
            ) {
                $scope.disableSubmitWin = true;
            }
            else {
                $scope.disableSubmitWin = false;
            }

            $scope.sortableOptions = {
                placeholder: "player",
                connectWith: ".player-container",
                stop: function (event, ui) {
                    angular.forEach($scope.bluePlayers, function(bluePlayer, bluePlayerKey) {
                        if (
                                (bluePlayerKey === 0 && bluePlayer.is_queen === false) ||
                                (bluePlayerKey !== 0 && bluePlayer.is_queen === true) ||
                                (bluePlayer.id_cabinet_side !== $scope.cabsides[0].id_cabinet_side)
                        ) {
                            kqDraftFactory.updateSeriesPlayer(
                                $stateParams.locationId,
                                $stateParams.eventId,
                                $stateParams.seriesId,
                                bluePlayer.id_draft_series_player,
                                bluePlayerKey === 0 ? true : false,
                                $scope.cabsides[0].id_cabinet_side
                            )
                            .then(
                                function(response) {
                                    $scope.refreshPlayers();
                                }
                            );
                        }
                    });

                    angular.forEach($scope.goldPlayers, function(goldPlayer, goldPlayerKey) {
                        if (
                                (goldPlayerKey === 0 && goldPlayer.is_queen === false) ||
                                (goldPlayerKey !== 0 && goldPlayer.is_queen === true) ||
                                (goldPlayer.id_cabinet_side !== $scope.cabsides[1].id_cabinet_side)
                        ) {
                            kqDraftFactory.updateSeriesPlayer(
                                $stateParams.locationId,
                                $stateParams.eventId,
                                $stateParams.seriesId,
                                goldPlayer.id_draft_series_player,
                                goldPlayerKey === 0 ? true : false,
                                $scope.cabsides[1].id_cabinet_side
                            )
                            .then(
                                function(response) {
                                    $scope.refreshPlayers();
                                }
                            );
                        }
                    });
                },
                items: "div:not(.disabled)",
                cancel: ".disabled"
            };

            $scope.addNewPlayer = function() {
                kqDraftFactory.addPlayer(
                    $stateParams.locationId,
                    $scope.newPlayer.name,
                    $scope.newPlayer.id_preference
                )
                .then(
                    function(response) {
                        kqDraftFactory.getPlayers($stateParams.locationId)
                        .then(
                            function(response) {
                                $scope.players = response.data;

                                angular.forEach($scope.players, function(player, playerKey) {
                                    if (player.name === $scope.newPlayer.name) {
                                        kqDraftFactory.addEventPlayer(
                                            $stateParams.locationId,
                                            $stateParams.eventId,
                                            player.id_player
                                        )
                                        .then(
                                            function(response) {
                                                $scope.refreshPlayers();
                                                $scope.newPlayer = {};
                                            }
                                        );
                                    }
                                });
                            }
                        );
                    }
                );
            };

            $scope.addSeriesPlayer = function(draftEventPlayerId) {
                var cabinetSideId;
                var isQueen;

                if ($scope.currentSeries.current_round === 1) {
                    if (goldPlayers.length < bluePlayers.length) {
                        cabinetSideId = $scope.cabsides[1].id_cabinet_side;

                        if (goldPlayers.length === 0) {
                            isQueen = true;
                        }
                        else {
                            isQueen = false;
                        }
                    }
                    else {
                        cabinetSideId = $scope.cabsides[0].id_cabinet_side;

                        if (bluePlayers.length === 0) {
                            isQueen = true;
                        }
                        else {
                            isQueen = false;
                        }
                    }

                    kqDraftFactory.addSeriesPlayer(
                        $stateParams.locationId,
                        $stateParams.eventId,
                        $scope.currentSeries.id_draft_series,
                        draftEventPlayerId,
                        cabinetSideId,
                        isQueen
                    )
                    .then(
                        function(response) {
                            $state.go('.', $stateParams, { reload: true });
                        }
                    );
                }
            };

            $scope.endEvent = function() {
                kqDraftFactory.endEvent($stateParams.locationId, $stateParams.eventId)
                .then(
                    function(response) {
                        $state.go('event', { locationId: $stateParams.locationId });
                    }
                );
            };

            $scope.mapPlayerDraftStatus = function() {
                angular.forEach($scope.players, function(player, playerKey) {
                    $scope.players[playerKey].is_in_event = false;
                    $scope.players[playerKey].id_draft_event_player = null;

                    angular.forEach($scope.eventPlayers, function(eventPlayer, eventPlayerKey) {
                        if (player.id_player === eventPlayer.id_player) {
                            $scope.players[playerKey].is_in_event = eventPlayer.player_active;
                            $scope.players[playerKey].id_draft_event_player  = eventPlayer.id_draft_event_player;
                        }
                    });
                });

                $scope.playerRoleCheck = seriesService.hasEnoughPlayers($scope.players);
            };

            $scope.nextSeries = function() {
                if (!!$stateParams.seriesId) {
                    angular.forEach($scope.series, function(series, seriesKey) {
                        if (parseInt($stateParams.seriesId) === series.id_draft_series
                        ) {
                            $scope.currentSeriesIndex = seriesKey;
                        }
                    });

                    if ($scope.currentSeriesIndex < $scope.series.length-1) {
                        $stateParams.seriesId = $scope.series[$scope.currentSeriesIndex+1].id_draft_series;
                    }
                }
                else {
                    $stateParams.seriesId = $scope.series[0].id_draft_series;
                }

                $state.go('.', $stateParams, { reload: true });
            };

            $scope.prevSeries = function() {
                if (!!$stateParams.seriesId) {
                    angular.forEach($scope.series, function(series, seriesKey) {
                        if (parseInt($stateParams.seriesId) === series.id_draft_series
                        ) {
                            $scope.currentSeriesIndex = seriesKey;
                        }
                    });

                    if ($scope.currentSeriesIndex > 0) {
                        $stateParams.seriesId = $scope.series[$scope.currentSeriesIndex-1].id_draft_series;
                    }
                }
                else {
                    $stateParams.seriesId = $scope.series[0].id_draft_series;
                }

                $state.go('.', $stateParams, { reload: true });
            };

            $scope.newSeries = function() {
                kqDraftFactory.draftSeries($stateParams.locationId, $stateParams.eventId)
                .then(
                    function (response) {
                        $stateParams.seriesId = "";
                        $state.go('.', $stateParams, { reload: true });
                    }
                );
            };

            $scope.managePlayers = function() {
                $state.go('managePlayers', $stateParams);
            };

            $scope.refreshPlayers = function() {
                kqDraftFactory.getPlayers($stateParams.locationId)
                .then(
                    function(response) {
                        $scope.players = response.data;
                        kqDraftFactory.getEventPlayers($stateParams.locationId, $stateParams.eventId)
                        .then(
                            function(response) {
                                $state.go('.', $stateParams, { reload: true });
                            }
                        );
                    }
                );
            };

            $scope.removeSeriesPlayer = function(seriesPlayerId) {
                if ($scope.currentSeries.current_round === 1) {
                    kqDraftFactory.removeSeriesPlayer(
                        $stateParams.locationId,
                        $stateParams.eventId,
                        $scope.currentSeries.id_draft_series,
                        seriesPlayerId
                    )
                    .then(
                        function(response) {
                            $state.go('.', $stateParams, { reload: true });
                        }
                    );
                }
            };

            $scope.submitWin = function() {
                kqDraftFactory.submitWin(
                    $stateParams.locationId,
                    $stateParams.eventId,
                    $scope.currentSeries.id_draft_series,
                    $scope.mapWin.id_map,
                    $scope.cabsideWin.id_cabinet_side
                )
                .then(
                    function(response) {
                        $state.go('.', $stateParams, { reload: true});
                    }
                );
            };

            $scope.toggleManagePlayers = function() {
                $scope.isAddPlayerCollapsed = !$scope.isAddPlayerCollapsed;
                $stateParams.toggleManagePlayers = $stateParams.toggleManagePlayers === 'true' ? 'false' : 'true';
                $state.go('.', $stateParams, { reload: true });
            };

            $scope.updateEventPlayer = function(playerKey) {
                if ($scope.players[playerKey].id_draft_event_player !== null) {
                    kqDraftFactory.updateEventPlayer(
                        $stateParams.locationId,
                        $stateParams.eventId,
                        $scope.players[playerKey].id_player,
                        !$scope.players[playerKey].is_in_event
                    )
                    .then(
                        function(response) {
                            $stateParams.toggleManagePlayers = true;
                            $state.go('.', $stateParams, { reload: true });
                        }
                    );
                }
                else {
                    kqDraftFactory.addEventPlayer(
                        $stateParams.locationId,
                        $stateParams.eventId,
                        $scope.players[playerKey].id_player
                    )
                    .then(
                        function(response) {
                            $stateParams.toggleManagePlayers = true;
                            $state.go('.', $stateParams, { reload: true });
                        }
                    );
                }
            };

            $scope.mapPlayerDraftStatus();
        }
    ]
);